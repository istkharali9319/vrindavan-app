<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Support\ContentFieldRules;
use App\Support\WebsiteContent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Saving one website page's fields. Inputs are fields[section][field] and, for photos, images[section][field].
 */
class UpdatePageContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Permission::ContentManage->value);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $page = app(WebsiteContent::class)->page((string) $this->route('page'));
        $rules = [];

        foreach ($page['sections'] ?? [] as $sectionKey => $section) {
            foreach ($section['fields'] as $fieldKey => $field) {
                if ($field['type'] === 'image') {
                    $rules["images.{$sectionKey}.{$fieldKey}"] = ContentFieldRules::image();
                    $rules["reset.{$sectionKey}.{$fieldKey}"] = ['nullable', 'boolean'];
                } else {
                    $rules["fields.{$sectionKey}.{$fieldKey}"] = ContentFieldRules::value($field);
                }
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $page = app(WebsiteContent::class)->page((string) $this->route('page'));
        $attributes = [];

        foreach ($page['sections'] ?? [] as $sectionKey => $section) {
            foreach ($section['fields'] as $fieldKey => $field) {
                $name = strtolower($section['label'].' → '.$field['label']);
                $attributes["fields.{$sectionKey}.{$fieldKey}"] = $name;
                $attributes["images.{$sectionKey}.{$fieldKey}"] = $name;
            }
        }

        return $attributes;
    }
}
