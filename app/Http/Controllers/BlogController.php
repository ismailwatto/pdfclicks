<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Blogs;
use Illuminate\Http\Request;

final class BlogController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // If no slug is provided, redirect to the home page
        $slug = $request->route('slug');

        $blog = Blogs::where('slug', $slug)->firstOrFail();

        if (is_null($slug)) {
            return redirect()->route('home');
        }

        return view('single-blog', ['blog' => $blog]);
    }
}
