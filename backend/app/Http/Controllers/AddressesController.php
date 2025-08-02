<?php

namespace App\Http\Controllers;

use App\Models\Addresses;
use App\Http\Requests\StoreAddressesRequest;
use App\Http\Requests\UpdateAddressesRequest;

class AddressesController extends Controller
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
    public function store(StoreAddressesRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Addresses $addresses)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Addresses $addresses)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAddressesRequest $request, Addresses $addresses)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Addresses $addresses)
    {
        //
    }
}
