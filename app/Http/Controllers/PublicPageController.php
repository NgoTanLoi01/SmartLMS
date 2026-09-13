<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class PublicPageController extends Controller
{
    public function show(string $slug): View
    {
        $page = config("marketing.pages.{$slug}");

        abort_unless(is_array($page), 404);

        return view('marketing.show', [
            'page' => $page,
            'slug' => $slug,
            'pages' => config('marketing.pages', []),
        ]);
    }
}
