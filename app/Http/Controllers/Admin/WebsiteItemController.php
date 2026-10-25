<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContentItemRequest;
use App\Models\ContentItem;
use App\Support\ContentFieldRules;
use App\Support\WebsiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Repeatable website lists: experiences, gallery photos, reviews, nearby places…
 */
class WebsiteItemController extends Controller
{
    public function __construct(private WebsiteContent $content) {}

    public function index(string $page, string $collection): View
    {
        $definition = $this->definition($page, $collection);

        return view('admin.website.items.index', [
            'pageKey' => $page,
            'page' => $this->content->page($page),
            'collectionKey' => $collection,
            'collection' => $definition,
            'items' => ContentItem::query()->inCollection($collection)->get(),
        ]);
    }

    public function create(string $page, string $collection): View
    {
        $definition = $this->definition($page, $collection);

        return view('admin.website.items.create', [
            'pageKey' => $page,
            'page' => $this->content->page($page),
            'collectionKey' => $collection,
            'collection' => $definition,
            'item' => new ContentItem([
                'is_active' => true,
                'data' => collect($definition['fields'])->map(fn (array $field) => $field['type'] === 'image' ? '' : $field['default'])->all(),
            ]),
        ]);
    }

    public function store(ContentItemRequest $request, string $page, string $collection): RedirectResponse
    {
        $definition = $this->definition($page, $collection);

        if ($definition['max'] && ContentItem::where('collection', $collection)->count() >= $definition['max']) {
            return back()->withInput()->withErrors(['data' => "This list holds up to {$definition['max']} {$definition['singular']}s. Remove one first."]);
        }

        $item = ContentItem::create([
            'collection' => $collection,
            'data' => $this->data($request, $definition, []),
            'sort_order' => (int) ContentItem::where('collection', $collection)->max('sort_order') + 1,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.website.items.index', [$page, $collection])
            ->with('status', ucfirst($definition['singular'])." \"{$this->title($item)}\" was added.");
    }

    public function edit(string $page, string $collection, ContentItem $item): View
    {
        $definition = $this->definition($page, $collection, $item);

        return view('admin.website.items.edit', [
            'pageKey' => $page,
            'page' => $this->content->page($page),
            'collectionKey' => $collection,
            'collection' => $definition,
            'item' => $item,
        ]);
    }

    public function update(ContentItemRequest $request, string $page, string $collection, ContentItem $item): RedirectResponse
    {
        $definition = $this->definition($page, $collection, $item);

        $item->update([
            'data' => $this->data($request, $definition, $item->data ?? []),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.website.items.index', [$page, $collection])
            ->with('status', ucfirst($definition['singular'])." \"{$this->title($item)}\" has been saved.");
    }

    public function destroy(string $page, string $collection, ContentItem $item): RedirectResponse
    {
        abort_unless(config('admin.deletes_enabled'), 403, 'Deleting is switched off for now.');

        $definition = $this->definition($page, $collection, $item);

        foreach ($definition['fields'] as $key => $field) {
            if ($field['type'] === 'image') {
                $this->deleteUpload($item->data[$key] ?? null);
            }
        }

        $item->delete();

        return redirect()->route('admin.website.items.index', [$page, $collection])
            ->with('status', ucfirst($definition['singular'])." \"{$this->title($item)}\" was removed.");
    }

    /**
     * Swaps the item with its neighbour above or below.
     */
    public function move(Request $request, string $page, string $collection, ContentItem $item): RedirectResponse
    {
        $this->definition($page, $collection, $item);
        $direction = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]])['direction'];

        DB::transaction(function () use ($collection, $item, $direction): void {
            $items = ContentItem::query()->inCollection($collection)->lockForUpdate()->get()->values();
            $index = $items->search(fn (ContentItem $candidate): bool => $candidate->is($item));
            $target = $direction === 'up' ? $index - 1 : $index + 1;

            if ($index === false || ! $items->has($target)) {
                return;
            }

            $ordered = $items->all();
            [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];

            foreach (array_values($ordered) as $position => $entry) {
                $entry->updateQuietly(['sort_order' => $position + 1]);
            }
        });

        return back();
    }

    /**
     * @return array{page: string, label: string, singular: string, max: ?int, fields: array<string, array<string, mixed>>, items: list<array<string, mixed>>}
     */
    private function definition(string $page, string $collection, ?ContentItem $item = null): array
    {
        $definition = $this->content->collection($collection);

        abort_if($definition === null || $definition['page'] !== $page, 404);
        abort_if($item && $item->collection !== $collection, 404);

        return $definition;
    }

    /**
     * @param  array{fields: array<string, array<string, mixed>>}  $definition
     * @param  array<string, mixed>  $current
     * @return array<string, mixed>
     */
    private function data(ContentItemRequest $request, array $definition, array $current): array
    {
        $data = [];

        foreach ($definition['fields'] as $key => $field) {
            if ($field['type'] === 'image') {
                $data[$key] = $current[$key] ?? '';

                if ($request->hasFile("images.{$key}")) {
                    $this->deleteUpload($current[$key] ?? null);
                    $data[$key] = $request->file("images.{$key}")->store("content/{$request->route('collection')}", 'public');
                }

                continue;
            }

            $value = $request->input("data.{$key}");
            $data[$key] = $field['type'] === 'tags'
                ? array_values((array) ($value ?? []))
                : str_replace("\r\n", "\n", trim((string) $value));
        }

        return $data;
    }

    private function title(ContentItem $item): string
    {
        return $item->get('title') ?: $item->get('name') ?: '#'.$item->id;
    }

    private function deleteUpload(?string $path): void
    {
        if (ContentFieldRules::isUpload($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
