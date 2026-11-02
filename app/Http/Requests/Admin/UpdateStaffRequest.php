<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Enums\Role;
use App\Rules\UniquePhone;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Permission::RolesManage->value);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
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
            'phone' => ['nullable', 'string', 'regex:/^\+[1-9]\d{7,14}$/', UniquePhone::except($this->route('user')?->id)],
            'role' => ['required', Rule::in(Role::staffValues())],
        ];
    }

    /**
     * A super admin cannot remove their own super admin role (that would lock the team out).
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $editingSelf = $this->route('user')?->is($this->user());

                if ($editingSelf && $this->input('role') !== Role::SuperAdmin->value) {
                    $validator->errors()->add('role', 'You cannot remove your own super admin role.');
                }
            },
        ];
    }
}
