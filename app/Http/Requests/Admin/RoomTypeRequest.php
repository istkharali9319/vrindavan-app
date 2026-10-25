<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Models\RoomType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Creating and editing a room type. The tariff is typed in rupees and stored in paise.
 */
class RoomTypeRequest extends FormRequest
{
    public const MAX_GALLERY_PHOTOS = 8;

    public function authorize(): bool
    {
        return $this->user()->can(Permission::CatalogManage->value);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name'))),
            'is_active' => $this->boolean('is_active'),
            'amenities' => collect(preg_split('/\R/', (string) $this->input('amenities')))
                ->map(fn (string $line): string => trim($line))
                ->filter()
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var RoomType|null $roomType */
        $roomType = $this->route('roomType');

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique(RoomType::class)->ignore($roomType)],
            'tagline' => ['nullable', 'string', 'max:120'],
            'badge' => ['nullable', 'string', 'max:40'],
            'description' => ['required', 'string', 'max:2000'],
            'amenities' => ['array', 'max:12'],
            'amenities.*' => ['string', 'max:160'],
            'price' => ['required', 'numeric', 'min:1', 'max:1000000', 'decimal:0,2'],
            'max_adults' => ['required', 'integer', 'min:1', 'max:12'],
            'max_children' => ['required', 'integer', 'min:0', 'max:8'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'gallery' => ['array', 'max:'.self::MAX_GALLERY_PHOTOS],
            'gallery.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_gallery' => ['array'],
            'remove_gallery.*' => ['string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'price' => 'nightly tariff',
            'max_adults' => 'maximum adults',
            'max_children' => 'maximum children',
            'gallery.*' => 'gallery photo',
            'amenities.*' => 'amenity',
        ];
    }

    /**
     * Attributes for the model itself (photos are handled by the controller).
     *
     * @return array<string, mixed>
     */
    public function roomTypeAttributes(): array
    {
        return [
            ...$this->safe()->only(['name', 'slug', 'tagline', 'badge', 'description', 'amenities', 'max_adults', 'max_children', 'is_active']),
            'sort_order' => (int) ($this->validated('sort_order') ?? 0),
            'nightly_price' => (int) round((float) $this->validated('price') * 100),
        ];
    }
}
