<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Support\ContentFieldRules;
use App\Support\WebsiteContent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Creating or editing one item of a website list. Inputs are data[field] and, for photos, images[field].
 */
class ContentItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Permission::ContentManage->value);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $collection = app(WebsiteContent::class)->collection((string) $this->route('collection'));
        $isNew = $this->route('item') === null;
        $rules = [
            'is_active' => ['boolean'],
        ];

        foreach ($collection['fields'] ?? [] as $key => $field) {
            if ($field['type'] === 'image') {
                $rules["images.{$key}"] = [$isNew ? 'required' : 'nullable', ...array_slice(ContentFieldRules::image(), 1)];

                continue;
            }

            $rules["data.{$key}"] = ContentFieldRules::value($field);

            if ($field['type'] === 'tags') {
                $rules["data.{$key}.*"] = ContentFieldRules::tag($field);
            }
        }

        if (isset($collection['fields']['title'])) {
            $rules['data.title'] = ['required', ...array_slice($rules['data.title'], 1)];
        } elseif (isset($collection['fields']['name'])) {
            $rules['data.name'] = ['required', ...array_slice($rules['data.name'], 1)];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $collection = app(WebsiteContent::class)->collection((string) $this->route('collection'));

        return collect($collection['fields'] ?? [])
            ->flatMap(fn (array $field, string $key): array => [
                "data.{$key}" => strtolower($field['label']),
                "images.{$key}" => strtolower($field['label']),
            ])
            ->all();
    }
}
