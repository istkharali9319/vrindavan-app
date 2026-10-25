<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Enums\RoomStatus;
use App\Models\Room;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Permission::InventoryManage->value);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['number' => strtoupper(trim((string) $this->input('number')))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Room|null $room */
        $room = $this->route('room');

        return [
            'number' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9-]+$/', Rule::unique(Room::class)->ignore($room)],
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'status' => ['required', Rule::enum(RoomStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'number' => 'room number',
            'room_type_id' => 'room type',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'number.regex' => 'Use letters, numbers and dashes only, e.g. 101 or C-2.',
        ];
    }
}
