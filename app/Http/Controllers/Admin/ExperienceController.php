<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PriceUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExperienceRequest;
use App\Models\BookingAddOn;
use App\Models\Experience;
use App\Support\ContentFieldRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Experiences and extras with prices: the Experiences page and the booking add-ons both come from here.
 */
class ExperienceController extends Controller
{
    public function index(): View
    {
        $experiences = Experience::ordered()->get();
        $sold = BookingAddOn::query()->selectRaw('experience_id, count(*) as total')->groupBy('experience_id')->pluck('total', 'experience_id');

        return view('admin.experiences.index', [
            'experiences' => $experiences,
            'sold' => $sold,
            'counts' => [
                'all' => $experiences->count(),
                'on_page' => $experiences->where('is_active', true)->where('show_on_page', true)->count(),
                'add_ons' => $experiences->where('is_active', true)->where('offer_as_addon', true)->whereNotNull('price')->count(),
                'on_request' => $experiences->whereNull('price')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.experiences.create', [
            'experience' => new Experience([
                'layout' => 'feature',
                'price_unit' => PriceUnit::PerGuest,
                'show_on_page' => true,
                'offer_as_addon' => false,
                'is_active' => true,
                'sort_order' => (int) Experience::max('sort_order') + 1,
            ]),
        ]);
    }

    public function store(ExperienceRequest $request): RedirectResponse
    {
        $experience = Experience::create($request->experienceAttributes());
        $this->savePhoto($request, $experience);

        return redirect()->route('admin.experiences.index')->with('status', "\"{$experience->title}\" was added.");
    }

    public function edit(Experience $experience): View
    {
        return view('admin.experiences.edit', [
            'experience' => $experience,
            'timesBooked' => BookingAddOn::where('experience_id', $experience->id)->count(),
        ]);
    }

    public function update(ExperienceRequest $request, Experience $experience): RedirectResponse
    {
        $experience->update($request->experienceAttributes());
        $this->savePhoto($request, $experience);

        return redirect()->route('admin.experiences.index')->with('status', "\"{$experience->title}\" has been saved.");
    }

    /**
     * Bookings keep their own copy of add-on names and prices, so deleting is safe.
     */
    public function destroy(Experience $experience): RedirectResponse
    {
        abort_unless(config('admin.deletes_enabled'), 403, 'Deleting is switched off for now.');

        if (ContentFieldRules::isUpload($experience->image_path)) {
            Storage::disk('public')->delete($experience->image_path);
        }

        $experience->delete();

        return redirect()->route('admin.experiences.index')->with('status', "\"{$experience->title}\" was deleted.");
    }

    private function savePhoto(ExperienceRequest $request, Experience $experience): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        if (ContentFieldRules::isUpload($experience->image_path)) {
            Storage::disk('public')->delete($experience->image_path);
        }

        $experience->forceFill(['image_path' => $request->file('image')->store('experiences', 'public')])->save();
    }
}
