<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The signed-in user's notifications: newest first, with read and unread tracking.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';
        $user = $request->user();

        return view('admin.notifications', [
            'notifications' => ($filter === 'unread' ? $user->unreadNotifications() : $user->notifications())->paginate(20)->withQueryString(),
            'unreadCount' => $user->unreadNotifications()->count(),
            'filter' => $filter,
        ]);
    }

    /**
     * Marks one notification read and opens what it is about.
     */
    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        $url = $item->data['url'] ?? null;

        return $url && str_starts_with($url, url('/')) ? redirect()->to($url) : redirect()->route('admin.notifications.index');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'All notifications are marked as read.');
    }
}
