<?php

namespace App\Http\Requests\Booking;

use App\Models\RoomType;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Stay dates and guests, checked against the rules in config/booking.php.
 * Used by the booking details page; the search page runs the same rules through stayValidator().
 */
class StayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return self::stayRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return self::stayMessages();
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        $roomType = $this->route('roomType');

        return [self::stayLengthCheck(), self::occupancyCheck($roomType instanceof RoomType ? $roomType : null)];
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function stayRules(): array
    {
        return [
            'check_in' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.self::earliestCheckIn()->toDateString(), 'before_or_equal:'.self::latestCheckIn()->toDateString()],
            'check_out' => ['required', 'date_format:Y-m-d', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1', 'max:12'],
            'children' => ['nullable', 'integer', 'min:0', 'max:8'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function stayMessages(): array
    {
        return [
            'check_in.after_or_equal' => 'The earliest check-in we can take is '.self::earliestCheckIn()->format('j M Y').'.',
            'check_in.before_or_equal' => 'We take bookings up to '.self::latestCheckIn()->format('j M Y').'.',
            'check_out.after' => 'Check-out must be after check-in.',
        ];
    }

    /**
     * Validator for the public search page, which shows errors inline instead of redirecting.
     *
     * @param  array<string, mixed>  $input
     */
    public static function stayValidator(array $input): Validator
    {
        return validator($input, self::stayRules(), self::stayMessages())->after([self::stayLengthCheck()]);
    }

    /**
     * Today in the resort's timezone, or tomorrow once same-day bookings have closed.
     */
    public static function earliestCheckIn(): CarbonImmutable
    {
        $now = CarbonImmutable::now(config('booking.timezone'));
        $cutoff = $now->setTimeFromTimeString(config('booking.same_day_cutoff'));

        return $now->greaterThanOrEqualTo($cutoff) ? $now->addDay()->startOfDay() : $now->startOfDay();
    }

    public static function latestCheckIn(): CarbonImmutable
    {
        return CarbonImmutable::now(config('booking.timezone'))->startOfDay()->addDays(config('booking.max_days_in_advance'));
    }

    /**
     * @return Closure(Validator): void
     */
    protected static function stayLengthCheck(): Closure
    {
        return function (Validator $validator): void {
            if ($validator->errors()->hasAny(['check_in', 'check_out'])) {
                return;
            }

            $data = $validator->getData();
            $nights = (int) CarbonImmutable::parse($data['check_in'])->diffInDays(CarbonImmutable::parse($data['check_out']));

            if ($nights < config('booking.min_nights')) {
                $validator->errors()->add('check_out', 'The minimum stay is '.config('booking.min_nights').' '.str('night')->plural(config('booking.min_nights')).'.');
            } elseif ($nights > config('booking.max_nights')) {
                $validator->errors()->add('check_out', 'Online bookings are limited to '.config('booking.max_nights').' nights. Please contact us for longer stays.');
            }
        };
    }

    /**
     * @return Closure(Validator): void
     */
    protected static function occupancyCheck(?RoomType $roomType): Closure
    {
        return function (Validator $validator) use ($roomType): void {
            if ($roomType === null || $validator->errors()->hasAny(['adults', 'children'])) {
                return;
            }

            $data = $validator->getData();

            if ((int) $data['adults'] > $roomType->max_adults) {
                $validator->errors()->add('adults', "The {$roomType->name} sleeps up to {$roomType->max_adults} adults.");
            }

            if ((int) ($data['children'] ?? 0) > $roomType->max_children) {
                $validator->errors()->add('children', "The {$roomType->name} has room for up to {$roomType->max_children} children.");
            }
        };
    }
}
