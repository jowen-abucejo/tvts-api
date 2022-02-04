<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExtraPropertyResource;
use App\Models\ExtraProperty;
use Illuminate\Http\Request;

class ExtraPropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param string|null $property_owner
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $property_owner = null)
    {
        if($property_owner){
            return ExtraPropertyResource::collection(ExtraProperty::where('property_owner', '=', $property_owner)->where('active', true)->get());
        }
        if(auth()->user() && auth()->user()->isAdmin){
            return ExtraPropertyResource::collection(ExtraProperty::orderBy('active', 'DESC')->get());
        }

        return response(null, 404);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ExtraProperty  $extraProperty
     * @return \Illuminate\Http\Response
     */
    public function show(ExtraProperty $extraProperty)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ExtraProperty  $extraProperty
     * @return \Illuminate\Http\Response
     */
    public function edit(ExtraProperty $extraProperty)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ExtraProperty  $extraProperty
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ExtraProperty $extraProperty)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ExtraProperty  $extraProperty
     * @return \Illuminate\Http\Response
     */
    public function destroy(ExtraProperty $extraProperty)
    {
        //
    }
}
