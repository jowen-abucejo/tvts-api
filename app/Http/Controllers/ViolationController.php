<?php

namespace App\Http\Controllers;

use App\Http\Resources\ViolationResource;
use App\Models\Violation;
use App\Models\ViolationType;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ViolationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return ViolationResource::collection(Violation::all());
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
        $type = 1;
        $new_violation = Violation::create([
            'violation' => 'Disregarding Traffic Sign/Office/MO',
            'violation_type_id' => $type,
        ]);
        return new ViolationResource($new_violation);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Violation  $violation
     * @return \Illuminate\Http\Response
     */
    public function show(Violation $violation)
    {
        $v = Violation::find(1);
        return new ViolationResource($v);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Violation  $violation
     * @return \Illuminate\Http\Response
     */
    public function edit(Violation $violation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Violation  $violation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Violation $violation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Violation  $violation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Violation $violation)
    {
        //
    }

    public function groupByVehicleType()
    {
        $v_group = new Collection();
        $vehicle_types = ViolationType::distinct('vehicle_type')->pluck('vehicle_type');
        foreach ($vehicle_types as $vehicleType) {
            # code...
            $vehicleTypeIds = ViolationType::select('id')->where('vehicle_type', $vehicleType)->get();
            $v_group->put($vehicleType, ViolationResource::collection(Violation::whereIn('violation_type_id', $vehicleTypeIds)->get()));
        }
        return response()->json(
            [
                'violations'=>$v_group,
                'vehicle_types'=>$vehicle_types
            ]
        );
    }
}
