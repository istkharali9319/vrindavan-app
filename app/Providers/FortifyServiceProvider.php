<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\AuthenticatedRedirectResponse;
use App\Http\Responses\LogoutResponse;
use App\Http\Responses\PasswordResetResponse;
use App\Http\Responses\RegisteredRedirectResponse;
use App\Models\User;
use App\Support\ReturnTo;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Contracts\PasswordResetResponse as PasswordResetResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponse::class, AuthenticatedRedirectResponse::class);
        $this->app->singleton(TwoFactorLoginResponse::class, AuthenticatedRedirectResponse::class);
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
        $this->app->bind(PasswordResetResponseContract::class, PasswordResetResponse::class);
        $this->app->singleton(RegisterResponse::class, RegisteredRedirectResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::where('email', Str::lower($request->input(Fortify::username())))->first();

            if (! $user || ! Hash::check($request->input('password'), $user->password)) {
                return null;
            }

            if (! $user->isActive()) {
                throw ValidationException::withMessages([
                    Fortify::username() => 'This account has been suspended. Please contact us if you think this is a mistake.',
                ]);
            }

            $isStaffPortal = $request->input('portal') === 'admin';

            if ($isStaffPortal && ! $user->isStaff()) {
                throw ValidationException::withMessages([
                    Fortify::username() => 'This sign-in is for resort staff. Guests can sign in on the website.',
                ]);
            }

            if (! $isStaffPortal && $user->isStaff()) {
                throw ValidationException::withMessages([
                    Fortify::username() => 'Staff accounts sign in at '.route('admin.login').'.',
                ]);
            }

            $request->session()->put('login.portal', $isStaffPortal ? 'admin' : 'site');

            return $user;
        });

        $this->registerViews();
        $this->registerRateLimiters();
    }

    protected function registerViews(): void
    {
        Fortify::loginView(function (Request $request) {
            ReturnTo::remember($request);

            return view('auth.login', ['returnTo' => ReturnTo::pending($request)]);
        });
        Fortify::registerView(function (Request $request) {
            ReturnTo::remember($request);

            return view('auth.register', ['returnTo' => ReturnTo::pending($request)]);
        });
        Fortify::requestPasswordResetLinkView(fn (Request $request) => $request->query('portal') === 'admin'
            ? view('admin.auth.forgot-password')
            : view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));
        Fortify::twoFactorChallengeView(fn (Request $request) => $request->session()->get('login.portal') === 'admin'
            ? view('admin.auth.two-factor-challenge')
            : view('auth.two-factor-challenge'));
    }

    protected function registerRateLimiters(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
