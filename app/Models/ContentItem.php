<?php

namespace App\Models;

use App\Support\WebsiteContent;
use Database\Factories\ContentItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * One entry in a repeatable website list (an experience, a gallery photo, a review…).
 * Its fields live in `data`, as described by the collection in config/site-content.php.
 */
#[Fillable(['collection', 'data', 'sort_order', 'is_active'])]
class ContentItem extends Model
{
    /** @use HasFactory<ContentItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<ContentItem>  $query
     */
    public function scopeInCollection(Builder $query, string $collection): void
    {
        $query->where('collection', $collection)->orderBy('sort_order')->orderBy('id');
    }

    public function get(string $field): string
    {
        $value = $this->data[$field] ?? '';

        return is_array($value) ? implode("\n", $value) : (string) $value;
    }

    public function has(string $field): bool
    {
        return trim($this->get($field)) !== '';
    }

    public function html(string $field): HtmlString
    {
        return WebsiteContent::html($this->get($field));
    }

    /**
     * @return list<string>
     */
    public function list(string $field): array
    {
        return WebsiteContent::lines($this->data[$field] ?? '');
    }

    public function image(string $field = 'image'): string
    {
        return WebsiteContent::imageUrl($this->get($field));
    }

    /**
     * An id for links such as /experiences#artisan-momo-thukpa-masterclass.
     */
    public function anchor(): string
    {
        return Str::slug($this->get('short_title') ?: $this->get('title')) ?: 'item-'.$this->id;
    }

    public function initials(string $field = 'name'): string
    {
        return Str::of($this->get($field))
            ->replace('&', ' ')
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
    }
}
