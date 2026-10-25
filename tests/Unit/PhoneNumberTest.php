<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    /**
     * @return array<string, array{?string, ?string}>
     */
    public static function numbers(): array
    {
        return [
            'indian mobile, no code' => ['94320 64521', '+919432064521'],
            'indian mobile, leading zero' => ['09432064521', '+919432064521'],
            'indian mobile, 91 prefix' => ['919432064521', '+919432064521'],
            'already e164' => ['+91 94320-64521', '+919432064521'],
            'international with 00' => ['0044 20 7946 0958', '+442079460958'],
            'international with plus' => ['+1 (415) 555-0100', '+14155550100'],
            'too short' => ['12345', null],
            'letters' => ['call me', null],
            'empty' => ['', null],
            'null' => [null, null],
        ];
    }

    #[DataProvider('numbers')]
    public function test_normalizes_to_e164(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::normalize($input));
    }
}
