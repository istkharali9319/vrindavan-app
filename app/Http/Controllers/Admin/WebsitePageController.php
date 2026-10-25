<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePageContentRequest;
use App\Models\ContentItem;
use App\Models\SiteContent;
use App\Support\ContentFieldRules;
use App\Support\MailCheck;
use App\Support\WebsiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Editing the text and photos of each public page (config/site-content.php).
 */
class WebsitePageController extends Controller
{
    public function __construct(private WebsiteContent $content) {}

    public function index(): View
    {
        $counts = ContentItem::query()->selectRaw('collection, count(*) as total')->groupBy('collection')->pluck('total', 'collection');
        $edited = SiteContent::query()->selectRaw('page, max(updated_at) as last_edit')->groupBy('page')->pluck('last_edit', 'page');

        return view('admin.website.index', [
            'pages' => collect($this->content->pages())->map(fn (array $page, string $key): array => [
                ...$page,
                'key' => $key,
                'collections' => collect($this->content->collections($key))
                    ->map(fn (array $collection, string $collectionKey): array => [...$collection, 'count' => (int) ($counts[$collectionKey] ?? 0)])
                    ->all(),
                'last_edit' => $edited[$key] ?? null,
                'fields' => collect($page['sections'])->sum(fn (array $section): int => count($section['fields'])),
                'cover' => isset($page['sections']['hero']['fields']['image'])
                    ? WebsiteContent::imageUrl($this->content->value($key, 'hero.image'))
                    : null,
            ]),
        ]);
    }

    public function edit(string $page): View
    {
        $definition = $this->content->page($page) ?? abort(404);

        return view('admin.website.edit', [
            'pageKey' => $page,
            'page' => $definition,
            'values' => fn (string $key): string => $this->content->value($page, $key),
            'collections' => collect($this->content->collections($page))->map(fn (array $collection, string $key): array => [
                ...$collection,
                'items' => ContentItem::query()->inCollection($key)->get(),
            ]),
        ]);
    }

    public function update(UpdatePageContentRequest $request, string $page): RedirectResponse
    {
        $definition = $this->content->page($page) ?? abort(404);

        DB::transaction(function () use ($request, $page, $definition): void {
            foreach ($definition['sections'] as $sectionKey => $section) {
                foreach ($section['fields'] as $fieldKey => $field) {
                    $key = "{$sectionKey}.{$fieldKey}";

                    $field['type'] === 'image'
                        ? $this->saveImage($request, $page, $key, $field['default'])
                        : $this->saveValue($page, $key, (string) $request->input("fields.{$key}", ''), (string) $field['default']);
                }
            }
        });

        $this->content->flush();

        return redirect()->route('admin.website.edit', $page)
            ->with('status', "{$definition['label']} has been saved. The website shows the changes straight away.");
    }

    /**
     * Sends a test email to the signed-in staff member, to check the mail settings.
     */
    public function testEmail(Request $request): RedirectResponse
    {
        $error = MailCheck::send($request->user()->email);

        return redirect()->route('admin.website.edit', 'site')->with(
            $error === null ? 'status' : 'mail_error',
            $error === null ? 'Test email sent to '.$request->user()->email.'. Check the inbox and the spam folder.' : $error,
        );
    }

    /**
     * Values equal to the default are not stored, so later improvements to the defaults still show.
     */
    private function saveValue(string $page, string $key, string $value, string $default): void
    {
        $value = str_replace("\r\n", "\n", trim($value));

        if ($value === str_replace("\r\n", "\n", trim($default))) {
            SiteContent::where(['page' => $page, 'key' => $key])->delete();

            return;
        }

        SiteContent::updateOrCreate(['page' => $page, 'key' => $key], ['value' => $value]);
    }

    private function saveImage(UpdatePageContentRequest $request, string $page, string $key, string $default): void
    {
        $current = SiteContent::where(['page' => $page, 'key' => $key])->first();

        if ($request->hasFile("images.{$key}")) {
            $this->deleteUpload($current?->value);
            SiteContent::updateOrCreate(
                ['page' => $page, 'key' => $key],
                ['value' => $request->file("images.{$key}")->store("content/{$page}", 'public')],
            );
        } elseif ($request->boolean("reset.{$key}") && $current) {
            $this->deleteUpload($current->value);
            $current->delete();
        }
    }

    private function deleteUpload(?string $path): void
    {
        if (ContentFieldRules::isUpload($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
