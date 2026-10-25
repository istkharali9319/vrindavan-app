<?php

namespace App\Http\Requests\Booking;

use App\Enums\PaymentMethod;
use App\Support\PhoneNumber;
use App\Support\Razorpay;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The "Personal Details" step. Anyone can book: signed-in customers get the booking on their account,
 * guests get a private confirmation link.
 */
class StoreBookingRequest extends StayRequest
{
    /**
     * @var list<string>
     */
    public const TITLES = ['Mr', 'Ms', 'Mrs', 'Dr', 'Mx'];

    /**
     * Calling codes offered next to the mobile number (any other number can be typed with its own "+").
     *
     * @var array<string, string>
     */
    public const COUNTRY_CODES = [
        '+91' => 'India', '+977' => 'Nepal', '+975' => 'Bhutan', '+880' => 'Bangladesh', '+94' => 'Sri Lanka',
        '+971' => 'UAE', '+65' => 'Singapore', '+44' => 'United Kingdom', '+1' => 'USA / Canada', '+61' => 'Australia',
    ];

    protected function prepareForValidation(): void
    {
        $mobile = trim((string) $this->input('mobile'));
        $code = (string) $this->input('country_code', '+91');
        $phone = str_starts_with($mobile, '+') || $code === '+91'
            ? PhoneNumber::normalize($mobile)
            : PhoneNumber::normalize($code.ltrim(preg_replace('/\D/', '', $mobile), '0'));

        $this->merge([
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'contact_email' => strtolower(trim((string) $this->input('contact_email'))),
            'contact_phone' => $phone ?? $mobile,
            'gst_number' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('gst_number'))) ?: null,
            'special_requests' => trim((string) $this->input('special_requests')) ?: null,
            'payment_method' => $this->input('payment_method') ?: (count(self::paymentMethods()) === 1 ? self::paymentMethods()[0]->value : null),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'title' => ['required', Rule::in(self::TITLES)],
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'contact_email' => ['required', 'email', 'max:191'],
            'country_code' => ['required', Rule::in(array_keys(self::COUNTRY_CODES))],
            'mobile' => ['required', 'string', 'max:20'],
            'contact_phone' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'gst_number' => ['nullable', 'string', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/'],
            'special_requests' => ['nullable', 'string', 'max:500'],
            'terms' => ['accepted'],
            'payment_method' => ['required', Rule::in(array_map(fn (PaymentMethod $method) => $method->value, self::paymentMethods()))],
            'add_ons' => ['nullable', 'array', 'max:20'],
            'add_ons.*' => ['integer', 'distinct', Rule::exists('experiences', 'id')->where('is_active', true)->where('offer_as_addon', true)->whereNotNull('price')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'contact_phone.regex' => 'Please enter a valid mobile number.',
            'contact_phone.required' => 'Please enter your mobile number.',
            'gst_number.regex' => 'Please enter a valid 15-character GSTIN, e.g. 11AAAAA1111A1Z5.',
            'payment_method.required' => 'Please choose how you would like to pay.',
            'payment_method.in' => 'Please choose how you would like to pay.',
            'terms.accepted' => 'Please accept the booking terms and cancellation policy.',
            'add_ons.*.exists' => 'One of the add-ons you chose is no longer available. Please review your selection.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'contact_email' => 'email',
            'mobile' => 'mobile number',
            'gst_number' => 'GST number',
        ];
    }

    /**
     * Experience ids the guest ticked as add-ons. Prices are worked out again when the booking is made.
     *
     * @return list<int>
     */
    public function addOnIds(): array
    {
        return array_values(array_map('intval', $this->validated('add_ons') ?? []));
    }

    /**
     * Contact details in the shape CreateBooking expects.
     *
     * @return array{contact_name: string, contact_email: string, contact_phone: string, special_requests: ?string, guest_title: string, guest_first_name: string, guest_last_name: string, gst_number: ?string}
     */
    public function contact(): array
    {
        return [
            'contact_name' => trim($this->validated('first_name').' '.$this->validated('last_name')),
            'contact_email' => $this->validated('contact_email'),
            'contact_phone' => $this->validated('contact_phone'),
            'special_requests' => $this->validated('special_requests'),
            'guest_title' => $this->validated('title'),
            'guest_first_name' => $this->validated('first_name'),
            'guest_last_name' => $this->validated('last_name'),
            'gst_number' => $this->validated('gst_number'),
        ];
    }

    /**
     * Payment options open to guests right now: online when Razorpay is set up, at the resort when allowed.
     *
     * @return list<PaymentMethod>
     */
    public static function paymentMethods(): array
    {
        return array_values(array_filter([
            Razorpay::enabled() ? PaymentMethod::Online : null,
            config('booking.pay_at_resort') || ! Razorpay::enabled() ? PaymentMethod::PayAtResort : null,
        ]));
    }

    public function paysOnline(): bool
    {
        return $this->validated('payment_method') === PaymentMethod::Online->value;
    }
}
