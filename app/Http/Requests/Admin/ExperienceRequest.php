<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Enums\PriceUnit;
use App\Models\Experience;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Creating or editing an experience / add-on. The price is typed in rupees and stored in paise;
 * an empty price means "on request".
 */
class ExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Permission::CatalogManage->value);
    }

    protected function prepareForValidation(): void
    {
        $price = trim((string) $this->input('price'));

        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('title'))),
            'price' => $price === '' ? null : str_replace(',', '', $price),
            'show_on_page' => $this->boolean('show_on_page'),
            'offer_as_addon' => $this->boolean('offer_as_addon'),
            'is_active' => $this->boolean('is_active'),
            'highlights' => collect(preg_split('/\R/', (string) $this->input('highlights')))
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
        /** @var Experience|null $experience */
        $experience = $this->route('experience');

        return [
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:160', 'alpha_dash', Rule::unique(Experience::class)->ignore($experience)],
            'kicker' => ['nullable', 'string', 'max:80'],
            'badge' => ['nullable', 'string', 'max:80'],
            'layout' => ['required', Rule::in(['feature', 'card'])],
            'categories' => ['array'],
            'categories.*' => [Rule::in(array_keys(Experience::CATEGORIES))],
            'description' => ['nullable', 'string', 'max:3000'],
            'highlights' => ['array', 'max:10'],
            'highlights.*' => ['string', 'max:200'],
            'schedule' => ['nullable', 'string', 'max:160'],
            'link_label' => ['nullable', 'string', 'max:60'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:1000000', 'decimal:0,2'],
            'price_unit' => ['required', Rule::enum(PriceUnit::class)],
            'show_on_page' => ['boolean'],
            'offer_as_addon' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * An add-on must have a price guests can pay.
     *
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->boolean('offer_as_addon') && $this->input('price') === null) {
                $validator->errors()->add('price', 'Set a price (0 for complimentary) to offer this as a booking add-on.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['price_unit' => 'price unit', 'highlights.*' => 'highlight'];
    }

    /**
     * @return array<string, mixed>
     */
    public function experienceAttributes(): array
    {
        $price = $this->validated('price');

        return [
            ...$this->safe()->only(['title', 'slug', 'kicker', 'badge', 'layout', 'description', 'highlights', 'schedule', 'link_label', 'price_unit', 'show_on_page', 'offer_as_addon', 'is_active']),
            'categories' => array_values($this->validated('categories') ?? []),
            'price' => $price === null ? null : (int) round((float) $price * 100),
            'sort_order' => (int) ($this->validated('sort_order') ?? 0),
        ];
    }
}
