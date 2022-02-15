<?php

namespace App\Http\Controllers;

use App\Http\Resources\ViolationTypeResource;
use App\Models\ViolationType;
use Illuminate\Http\Request;

class ViolationTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->user() && $request->user()->isAdmin()){
            return ViolationTypeResource::collection(ViolationType::withTrashed()->get());
        }
        return ViolationTypeResource::collection(ViolationType::all());
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
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ViolationType  $violationType
     * @return \Illuminate\Http\Response
     */
    public function show(ViolationType $violationType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ViolationType  $violationType
     * @return \Illuminate\Http\Response
     */
    public function edit(ViolationType $violationType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ViolationType  $violationType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ViolationType $violationType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ViolationType  $violationType
     * @return \Illuminate\Http\Response
     */
    public function destroy(ViolationType $violationType)
    {
        //
    }

    public function getVehicleTypes()
    {
        $vehicle_types = ViolationType::select('vehicle_type')->pluck('vehicle_type')->toArray();
        return $vehicle_types;
    }
}
