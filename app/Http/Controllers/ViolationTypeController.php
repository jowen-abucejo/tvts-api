<?php

namespace App\Http\Controllers;

use App\Http\Resources\ViolationTypeResource;
use App\Models\Violation;
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
     * @param  number  $violation_type_id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $violation_type_id, $status_response = false)
    {
        $status = "Failed";
        
        if(!$violation_type_id)  return response()->json([
            "update_status" => $status
        ]);
        
        try {
            $violation_type = ViolationType::find($violation_type_id);
            $penalties = str_replace(' ', '', $request->penalties);

            $violation_type->type = $request->license_number;
            $violation_type->vehicle_type = $request->vehicle_type;
            $violation_type->penalties = $penalties;
            $violation_type->save();

            $status = "Incomplete";
            if($status_response) return true;
            return new ViolationTypeResource(ViolationType::find($violation_type_id));
        } catch (\Exception $err) {
            if($status_response) return false;
            return response()->json([
                "update_status" => $status
            ]);
        }
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
