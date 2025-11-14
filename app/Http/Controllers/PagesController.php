<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Blogs;
use Illuminate\Http\Request;

final class PagesController extends Controller
{
    public function __invoke(Request $request, $slug = null)
    {
        // If no slug is provided, redirect to the home page
        if (is_null($slug)) {
            return redirect()->route('home');
        }

        if ($slug === 'blogs') {
            $viewPath = resource_path("views/{$slug}.blade.php");
            if (! file_exists($viewPath)) {
                abort(404, "Page not found: {$slug}");
            }
            cache()->forget('blogs');
            $blogs = cache()->rememberForever('blogs', fn () => Blogs::orderBy('created_at', 'desc')
                ->get());

            return view($slug, [
                'blogs' => $blogs,
            ]);
        }

        $viewPath = resource_path("views/{$slug}.blade.php");
        if (! file_exists($viewPath)) {
            abort(404, "Page not found: {$slug}");
        }

        return view($slug);
    }
}
