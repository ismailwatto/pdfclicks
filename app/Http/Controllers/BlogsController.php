<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBlogsRequest;
use App\Http\Requests\UpdateBlogsRequest;
use App\Models\Blogs;

final class BlogsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBlogsRequest $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Blogs $blogs): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Blogs $blogs): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBlogsRequest $request, Blogs $blogs): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Blogs $blogs): void
    {
        //
    }
}
