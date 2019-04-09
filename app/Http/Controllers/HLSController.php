<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SergeyHartmann\StreamingLinkGenerator\Generator;
use SergeyHartmann\StreamingLinkGenerator\StreamingLink;
use SergeyHartmann\StreamingLinkGenerator\CookieLoader\SimpleCookieLoader;

class HLSController extends Controller
{
    public function redirectToStreamLink($fileId) {
        $cookieLoader = new SimpleCookieLoader(public_path('g.cookie'));
        $generator    = new Generator($cookieLoader);
        $streamingLink = $generator->generate($fileId);
        return redirect($streamingLink->getStreamingLink());
        // return $fileId;
    }
}
