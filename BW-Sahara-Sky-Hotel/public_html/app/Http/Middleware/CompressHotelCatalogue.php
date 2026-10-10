<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressHotelCatalogue
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        // Only this public DTO endpoint uses compression; never reflect OTP or session secrets.
        $response->setVary('Accept-Encoding', false);
        if (! function_exists('gzencode') || $response->getStatusCode() !== 200 || $response->headers->has('Content-Encoding')) {
            return $response;
        }
        $gzip = false;
        foreach (explode(',', strtolower($request->header('Accept-Encoding', ''))) as $entry) {
            $parts = array_map('trim', explode(';', $entry));
            if ($parts[0] !== 'gzip') {
                continue;
            }
            $quality = 1.0;
            foreach (array_slice($parts, 1) as $parameter) {
                if (str_starts_with($parameter, 'q=')) {
                    $quality = (float) substr($parameter, 2);
                }
            }
            $gzip = $quality > 0;
        }
        $body = $response->getContent();
        if ($gzip && is_string($body) && strlen($body) >= 1024) {
            $encoded = gzencode($body, 6);
            if ($encoded !== false && strlen($encoded) < strlen($body)) {
                $response->setContent($encoded);
                $response->headers->set('Content-Encoding', 'gzip');
                $response->headers->remove('Content-Length');
            }
        }
        return $response;
    }
}
