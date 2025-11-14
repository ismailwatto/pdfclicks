<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;

final class ToolsPagesController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        //

        $slug = $request->route('slug');
        if (is_null($slug)) {
            return redirect()->route('home');
        }

        $viewPath = resource_path("views/tools/{$slug}.blade.php");
        if (! file_exists($viewPath)) {
            abort(404, "Tool not found: {$slug}");
        }

        return view("tools.{$slug}");

    }
}
