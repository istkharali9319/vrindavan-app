<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Experience;
use Illuminate\View\View;

class ExperienceController extends Controller
{
    /**
     * The Experiences page: everything marked "Show on Experiences page" in Admin → Experiences & add-ons.
     */
    public function __invoke(): View
    {
        return view('site.experience', [
            'experiences' => Experience::onPage()->ordered()->get(),
        ]);
    }
}
