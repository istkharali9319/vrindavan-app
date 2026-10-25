<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnquiryChannel;
use App\Enums\EnquiryMessageType;
use App\Enums\EnquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\EnquiryMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Messages from the website's Contact page.
 */
class EnquiryController extends Controller
{
    /**
     * @var list<int>
     */
    public const PAGE_SIZES = [10, 25, 50, 100];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(EnquiryStatus::class)],
            'per_page' => ['nullable', 'integer', Rule::in(self::PAGE_SIZES)],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 25);

        $enquiries = Enquiry::query()
            ->with('latestMessage.author')
            ->withCount(['messages' => fn ($query) => $query->where('type', '!=', EnquiryMessageType::StatusChange)])
            ->when(trim($filters['search'] ?? '') !== '', function ($query) use ($filters) {
                // "!" escapes the LIKE wildcards; it behaves the same on MySQL and SQLite.
                $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($filters['search'])).'%';

                $query->where(function ($query) use ($like) {
                    foreach (['name', 'email', 'phone', 'message'] as $column) {
                        $query->orWhereRaw("{$column} LIKE ? ESCAPE '!'", [$like]);
                    }
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.enquiries.index', [
            'enquiries' => $enquiries,
            'filters' => $filters,
            'perPage' => $perPage,
            'counts' => [
                'all' => Enquiry::count(),
                'unread' => Enquiry::unread()->count(),
                ...collect(EnquiryStatus::cases())->mapWithKeys(fn (EnquiryStatus $status) => [
                    $status->value => Enquiry::where('status', $status)->count(),
                ])->all(),
            ],
        ]);
    }

    /**
     * Opening an enquiry marks it as read.
     */
    public function show(Enquiry $enquiry): View
    {
        if ($enquiry->isUnread()) {
            $enquiry->forceFill(['read_at' => now()])->save();
        }

        return view('admin.enquiries.show', [
            'enquiry' => $enquiry->load(['handler', 'messages.author']),
            'previous' => Enquiry::where('email', $enquiry->email)->whereKeyNot($enquiry->id)->latest()->limit(5)->get(),
        ]);
    }

    /**
     * Adds to the conversation and/or changes the status. A status change is recorded in the thread too.
     */
    public function storeMessage(Request $request, Enquiry $enquiry): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_map(fn (EnquiryMessageType $type) => $type->value, EnquiryMessageType::writable()))],
            'channel' => ['nullable', Rule::enum(EnquiryChannel::class)],
            'body' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(EnquiryStatus::class)],
        ]);

        $body = trim((string) ($data['body'] ?? ''));
        $status = EnquiryStatus::from($data['status']);
        $statusChanged = $status !== $enquiry->status;

        if ($body === '' && ! $statusChanged) {
            return back()->withErrors(['body' => 'Write something for the conversation, or change the status.']);
        }

        DB::transaction(function () use ($request, $enquiry, $data, $body, $status, $statusChanged): void {
            if ($body !== '') {
                $type = EnquiryMessageType::from($data['type']);
                $message = new EnquiryMessage([
                    'type' => $type,
                    'channel' => $type === EnquiryMessageType::Note ? null : ($data['channel'] ?? null),
                    'body' => $body,
                ]);
                $message->user_id = $request->user()->id;
                $enquiry->messages()->save($message);
            }

            if ($statusChanged) {
                $event = new EnquiryMessage(['type' => EnquiryMessageType::StatusChange, 'status' => $status]);
                $event->user_id = $request->user()->id;
                $enquiry->messages()->save($event);
            }

            $enquiry->forceFill(['status' => $status, 'handled_by' => $request->user()->id])->save();
        });

        return redirect()->to(route('admin.enquiries.show', $enquiry).'#conversation')
            ->with('status', $statusChanged ? "Saved. The enquiry is now marked {$status->label()}." : 'Added to the conversation.');
    }

    public function destroy(Enquiry $enquiry): RedirectResponse
    {
        abort_unless(config('admin.deletes_enabled'), 403, 'Deleting is switched off for now.');

        $enquiry->delete();

        return redirect()->route('admin.enquiries.index')->with('status', 'Enquiry from '.$enquiry->name.' was deleted.');
    }
}
