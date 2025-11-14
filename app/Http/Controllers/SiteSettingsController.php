<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteSettingsRequest;
use App\Http\Requests\UpdateSiteSettingsRequest;
use App\Models\SiteSettings;

final class SiteSettingsController extends Controller
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
    public function store(StoreSiteSettingsRequest $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(SiteSettings $siteSettings): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SiteSettings $siteSettings): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSiteSettingsRequest $request, SiteSettings $siteSettings): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SiteSettings $siteSettings): void
    {
        //
    }
}
