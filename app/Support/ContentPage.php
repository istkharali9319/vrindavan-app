<?php

namespace App\Support;

use App\Models\ContentItem;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

/**
 * One page's editable content, as handed to its Blade view (as $content).
 */
class ContentPage
{
    public function __construct(
        private WebsiteContent $content,
        public readonly string $page,
    ) {}

    /**
     * Plain text. {placeholders} are swapped for the given replacements.
     *
     * @param  array<string, string|int>  $replace
     */
    public function text(string $key, array $replace = []): string
    {
        $value = $this->content->value($this->page, $key);

        foreach ($replace as $placeholder => $replacement) {
            $value = str_replace('{'.$placeholder.'}', (string) $replacement, $value);
        }

        return $value;
    }

    /**
     * Escaped text with *italics* and line breaks, ready to echo with {{ }}.
     *
     * @param  array<string, string|int>  $replace
     */
    public function html(string $key, array $replace = []): HtmlString
    {
        return WebsiteContent::html($this->text($key, $replace));
    }

    public function has(string $key): bool
    {
        return trim($this->text($key)) !== '';
    }

    /**
     * @return list<string>
     */
    public function list(string $key): array
    {
        return WebsiteContent::lines($this->text($key));
    }

    /**
     * @return list<string>
     */
    public function paragraphs(string $key): array
    {
        return WebsiteContent::paragraphs($this->text($key));
    }

    public function image(string $key): string
    {
        return WebsiteContent::imageUrl($this->text($key));
    }

    /**
     * @return Collection<int, ContentItem>
     */
    public function items(string $collection): Collection
    {
        return $this->content->items($collection);
    }
}
