<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use App\Rules\UniquePhone;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Permission::RolesManage->value);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'phone' => PhoneNumber::normalize($this->input('phone')) ?? $this->input('phone'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.unique' => 'This mobile number is already used by another account.',
            'phone.regex' => 'Please enter a valid mobile number, e.g. 94320 64521 or +44 20 7946 0958.',
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:191', Rule::unique(User::class)],
            'phone' => ['nullable', 'string', 'regex:/^\+[1-9]\d{7,14}$/', UniquePhone::except()],
            'role' => ['required', Rule::in(Role::staffValues())],
        ];
    }
}
