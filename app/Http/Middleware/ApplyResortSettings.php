<?php

namespace App\Http\Middleware;

use App\Support\WebsiteContent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puts the resort details from Admin → Website → Site settings into config('resort') for this request.
 */
class ApplyResortSettings
{
    public function __construct(private WebsiteContent $content) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->content->flush();

        rescue(fn () => config(['resort' => [...config('resort'), ...$this->content->resortSettings()]]), report: false);

        return $next($request);
    }
}
