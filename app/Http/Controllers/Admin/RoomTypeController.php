<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoomTypeRequest;
use App\Models\RoomType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Room types are what guests book and what the website's Rooms page shows.
 */
class RoomTypeController extends Controller
{
    public function index(): View
    {
        return view('admin.room-types.index', [
            'roomTypes' => RoomType::query()
                ->withCount(['rooms', 'bookings'])
                ->with('rooms')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.room-types.create', [
            'roomType' => new RoomType(['is_active' => true, 'max_adults' => 2, 'max_children' => 0]),
        ]);
    }

    public function store(RoomTypeRequest $request): RedirectResponse
    {
        $roomType = RoomType::create($request->roomTypeAttributes());
        $this->savePhotos($request, $roomType);

        return redirect()->route('admin.room-types.index')
            ->with('status', "{$roomType->name} was added. Add rooms to it from the Rooms page so guests can book it.");
    }

    public function edit(RoomType $roomType): View
    {
        return view('admin.room-types.edit', [
            'roomType' => $roomType->loadCount(['rooms', 'bookings']),
        ]);
    }

    public function update(RoomTypeRequest $request, RoomType $roomType): RedirectResponse
    {
        $roomType->update($request->roomTypeAttributes());
        $this->savePhotos($request, $roomType);

        return redirect()->route('admin.room-types.index')
            ->with('status', "{$roomType->name} has been updated.");
    }

    /**
     * Replaces the main photo, removes ticked gallery photos and appends new ones.
     */
    private function savePhotos(RoomTypeRequest $request, RoomType $roomType): void
    {
        $disk = Storage::disk('public');
        $folder = "room-types/{$roomType->id}";

        if ($request->hasFile('image')) {
            if ($roomType->image_path) {
                $disk->delete($roomType->image_path);
            }
            $roomType->image_path = $request->file('image')->store($folder, 'public');
        }

        $gallery = $roomType->gallery ?? [];
        $removed = array_intersect($gallery, $request->validated('remove_gallery') ?? []);
        $disk->delete($removed);
        $gallery = array_values(array_diff($gallery, $removed));

        foreach ($request->file('gallery') ?? [] as $photo) {
            if (count($gallery) >= RoomTypeRequest::MAX_GALLERY_PHOTOS) {
                break;
            }
            $gallery[] = $photo->store($folder, 'public');
        }

        $roomType->gallery = $gallery;
        $roomType->save();
    }
}
