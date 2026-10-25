<?php

namespace App\Providers;

use App\Actions\Booking\LinkBookingToAccount;
use App\Enums\Role;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use App\Support\GuestDevice;
use App\Support\MailLogo;
use App\Support\WebsiteContent;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WebsiteContent::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function (): Password {
            $rule = Password::min(8)->letters()->numbers();

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        $this->applyResortSettings();

        View::composer('site.*', function (ViewContract $view): void {
            if (Str::contains($view->name(), '.partials.')) {
                return;
            }

            $view->with('content', $this->app->make(WebsiteContent::class)->for(Str::after($view->name(), 'site.')));
        });

        Gate::before(function (User $user): ?bool {
            return $user->hasRole(Role::SuperAdmin->value) ? true : null;
        });

        // A welcome email for guests who create an account on the website (staff accounts are made in the admin).
        Event::listen(Registered::class, function (Registered $event): void {
            if ($event->user instanceof User && ! $event->user->isStaff()) {
                $event->user->notify(new WelcomeNotification);
            }
        });

        ResetPassword::toMailUsing(function (User $user, string $token): MailMessage {
            $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('Reset your '.config('resort.name').' password')
                ->greeting('Hello '.str($user->name)->before(' ').',')
                ->line('We received a request to reset the password for your account.')
                ->action('Choose a new password', url(route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], false)))
                ->line("This link works once and expires in {$minutes} minutes.")
                ->line("If you didn't ask for this, you can ignore this email — your password stays the same.")
                ->salutation("Warm regards,  \n".config('resort.name'));
        });

        VerifyEmail::toMailUsing(function (User $user, string $url): MailMessage {
            $minutes = (int) config('auth.verification.expire', 60);

            return (new MailMessage)
                ->subject('Verify your email address')
                ->greeting('Namaste '.str($user->name)->before(' ').',')
                ->line('Please confirm this is your email address so we can send your booking confirmations and invoices here.')
                ->action('Verify my email', $url)
                ->line("This link expires in {$minutes} minutes. If you didn't create an account, you can ignore this email.")
                ->salutation("Warm regards,  \n".config('resort.name'));
        });

        // The header of every email shows the logo from Site settings, attached inside the email itself.
        Event::listen(MessageSending::class, fn (MessageSending $event) => MailLogo::embedInto($event->message));

        // Choosing a password from an emailed link proves the inbox is theirs, so the email counts as verified.
        Event::listen(PasswordReset::class, function (PasswordReset $event): void {
            if ($event->user instanceof User && ! $event->user->hasVerifiedEmail()) {
                $event->user->markEmailAsVerified();
                GuestDevice::forget();
            }

            if ($event->user instanceof User) {
                LinkBookingToAccount::claimGuestBookings($event->user);
            }
        });

        // Bookings made as a guest with this email appear in the account once the email is verified.
        Event::listen(Verified::class, function (Verified $event): void {
            if ($event->user instanceof User) {
                LinkBookingToAccount::claimGuestBookings($event->user);
            }
        });

        // A delivery record for every email: who, what, and the server's message id (useful when a guest says nothing arrived).
        Event::listen(MessageSent::class, function (MessageSent $event): void {
            if ($this->app->runningUnitTests()) {
                return;
            }

            $message = $event->sent->getOriginalMessage();

            Log::channel('mail')->info('Sent', [
                'mailer' => config('mail.default'),
                'to' => collect($message->getTo())->map->getAddress()->implode(', '),
                'subject' => method_exists($message, 'getSubject') ? $message->getSubject() : null,
                'message_id' => $event->sent->getMessageId(),
                'attachments' => method_exists($message, 'getAttachments') ? count($message->getAttachments()) : 0,
            ]);
        });

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
        });
    }

    /**
     * Resort name, phones, email and address are edited in Admin → Website → Site settings.
     * Before the content tables exist (fresh install, mid-migration) the config file values stay.
     */
    private function applyResortSettings(): void
    {
        rescue(
            fn () => config(['resort' => [...config('resort'), ...$this->app->make(WebsiteContent::class)->resortSettings()]]),
            report: false,
        );
    }
}
