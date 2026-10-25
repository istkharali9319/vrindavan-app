<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreEnquiryRequest;
use App\Models\Enquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    /**
     * Saves a Contact page message for the team to answer from Admin → Enquiries.
     */
    public function store(StoreEnquiryRequest $request): RedirectResponse
    {
        $enquiry = new Enquiry($request->safe()->only(['name', 'email', 'phone', 'message']));
        $enquiry->forceFill([
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ])->save();

        return redirect()->to(route('contact').'#contactForm')->with('status', 'enquiry-sent');
    }
}
