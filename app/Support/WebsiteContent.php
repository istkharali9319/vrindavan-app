<?php

namespace App\Support;

use App\Models\ContentItem;
use App\Models\SiteContent;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Reads the editable website content described in config/site-content.php.
 *
 * Stored values win; anything never edited falls back to the default in the config file.
 * Registered as a singleton, so each request loads the stored fields once.
 */
class WebsiteContent
{
    /**
     * @var array<string, string|null>|null
     */
    private ?array $values = null;

    /**
     * @var array<string, Collection<int, ContentItem>>
     */
    private array $items = [];

    /**
     * @return array<string, array{label: string, description: string, icon: string, route: ?string, sections: array<string, array{label: string, fields: array<string, array<string, mixed>>}>}>
     */
    public function pages(): array
    {
        return collect(config('site-content.pages'))
            ->map(fn (array $page, string $key): array => $this->normalisePage($page))
            ->all();
    }

    /**
     * @return array{label: string, description: string, icon: string, route: ?string, sections: array<string, array{label: string, fields: array<string, array<string, mixed>>}>}|null
     */
    public function page(string $key): ?array
    {
        $page = config("site-content.pages.{$key}");

        return is_array($page) ? $this->normalisePage($page) : null;
    }

    /**
     * @return array<string, array{page: string, label: string, singular: string, max: ?int, fields: array<string, array<string, mixed>>, items: list<array<string, mixed>>}>
     */
    public function collections(?string $page = null): array
    {
        return collect(config('site-content.collections'))
            ->map(fn (array $collection): array => $this->normaliseCollection($collection))
            ->when($page, fn (Collection $collections) => $collections->where('page', $page))
            ->all();
    }

    /**
     * @return array{page: string, label: string, singular: string, max: ?int, fields: array<string, array<string, mixed>>, items: list<array<string, mixed>>}|null
     */
    public function collection(string $key): ?array
    {
        $collection = config("site-content.collections.{$key}");

        return is_array($collection) ? $this->normaliseCollection($collection) : null;
    }

    /**
     * @return array<string, string>
     */
    public function icons(): array
    {
        return config('site-content.icons');
    }

    /**
     * The stored value of a field, or its default. Key is "section.field".
     */
    public function value(string $page, string $key): string
    {
        $this->values ??= SiteContent::query()
            ->get(['page', 'key', 'value'])
            ->mapWithKeys(fn (SiteContent $row): array => ["{$row->page}.{$row->key}" => $row->value])
            ->all();

        if (array_key_exists("{$page}.{$key}", $this->values) && $this->values["{$page}.{$key}"] !== null) {
            return $this->values["{$page}.{$key}"];
        }

        [$section, $field] = explode('.', $key, 2);

        return (string) ($this->page($page)['sections'][$section]['fields'][$field]['default'] ?? '');
    }

    /**
     * Active items of a collection, in display order.
     *
     * @return Collection<int, ContentItem>
     */
    public function items(string $collection): Collection
    {
        return $this->items[$collection] ??= ContentItem::query()
            ->where('collection', $collection)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function for(string $page): ContentPage
    {
        return new ContentPage($this, $page);
    }

    /**
     * Forget what was loaded, after an admin saves changes.
     */
    public function flush(): void
    {
        $this->values = null;
        $this->items = [];
    }

    /**
     * Resort details for config('resort'), so every page, title and email uses the admin's values.
     *
     * @return array{name: string, tagline: string, altitude: string, address: string, email: string, phones: list<array{display: string, dial: string}>}
     */
    public function resortSettings(): array
    {
        return [
            'name' => $this->value('site', 'identity.name'),
            'tagline' => $this->value('site', 'identity.tagline'),
            'altitude' => $this->value('site', 'identity.altitude'),
            'address' => Str::squish($this->value('site', 'contact.address')),
            'email' => $this->value('site', 'contact.email'),
            'phones' => array_map(
                fn (string $phone): array => ['display' => $phone, 'dial' => preg_replace('/[^\d+]/', '', $phone)],
                self::lines($this->value('site', 'contact.phones')),
            ),
        ];
    }

    /**
     * Bundled website images ("images/…") and full URLs are used as they are; uploads live on the public disk.
     */
    public static function imageUrl(?string $path): string
    {
        return match (true) {
            blank($path) => asset('images/hero-accommodation.jpg'),
            Str::startsWith($path, ['http://', 'https://']) => $path,
            Str::startsWith($path, 'images/') => asset($path),
            default => asset('storage/'.$path),
        };
    }

    /**
     * Escapes the text, turns *words* into italics and keeps line breaks.
     */
    public static function html(string $text): HtmlString
    {
        $escaped = e($text);
        $escaped = preg_replace('/\*([^*\n]+)\*/', '<em>$1</em>', $escaped);

        return new HtmlString(nl2br($escaped, false));
    }

    /**
     * @return list<string>
     */
    public static function lines(string|array|null $text): array
    {
        $lines = is_array($text) ? $text : preg_split('/\R/', (string) $text);

        return array_values(array_filter(array_map('trim', $lines), fn (string $line): bool => $line !== ''));
    }

    /**
     * @return list<string>
     */
    public static function paragraphs(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', $text)), fn (string $paragraph): bool => $paragraph !== ''));
    }

    /**
     * @param  array<string, mixed>  $page
     * @return array{label: string, description: string, icon: string, route: ?string, sections: array<string, array{label: string, fields: array<string, array<string, mixed>>}>}
     */
    private function normalisePage(array $page): array
    {
        return [
            'label' => $page['label'],
            'description' => $page['description'] ?? '',
            'icon' => $page['icon'] ?? 'ti-file',
            'route' => $page['route'] ?? null,
            'sections' => collect($page['sections'])->map(fn (array $section): array => [
                'label' => $section['label'],
                'fields' => collect($section['fields'])->map(fn (array $field): array => $this->normaliseField($field))->all(),
            ])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $collection
     * @return array{page: string, label: string, singular: string, max: ?int, fields: array<string, array<string, mixed>>, items: list<array<string, mixed>>}
     */
    private function normaliseCollection(array $collection): array
    {
        return [
            'page' => $collection['page'],
            'label' => $collection['label'],
            'singular' => $collection['singular'] ?? 'item',
            'max' => $collection['max'] ?? null,
            'fields' => collect($collection['fields'])->map(fn (array $field): array => $this->normaliseField($field))->all(),
            'items' => $collection['items'] ?? [],
        ];
    }

    /**
     * @param  array{0: string, 1: string, 2?: mixed, 3?: array<string, mixed>}  $field
     * @return array{type: string, label: string, default: mixed, help: ?string, options: array<string, string>}
     */
    private function normaliseField(array $field): array
    {
        $extra = $field[3] ?? [];

        return [
            'type' => $field[0],
            'label' => $field[1],
            'default' => $field[2] ?? '',
            'help' => $extra['help'] ?? null,
            'options' => $field[0] === 'icon' ? $this->icons() : ($extra['options'] ?? []),
        ];
    }
}
