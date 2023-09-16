<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MenuPrice;
use App\Http\Requests\V1\StoreMenuPriceRequest;
use App\Http\Requests\V1\UpdateMenuPriceRequest;

class MenuPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMenuPriceRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(MenuPrice $menuPrice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MenuPrice $menuPrice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMenuPriceRequest $request, MenuPrice $menuPrice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MenuPrice $menuPrice)
    {
        //
    }
}
