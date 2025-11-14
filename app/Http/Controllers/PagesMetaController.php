<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePagesMetaRequest;
use App\Http\Requests\UpdatePagesMetaRequest;
use App\Models\PagesMeta;

final class PagesMetaController extends Controller
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
    public function store(StorePagesMetaRequest $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(PagesMeta $pagesMeta): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PagesMeta $pagesMeta): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePagesMetaRequest $request, PagesMeta $pagesMeta): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PagesMeta $pagesMeta): void
    {
        //
    }
}
