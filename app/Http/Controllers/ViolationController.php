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
        $model = Violation::create([
            'violation' => 'Disregarding Traffic Sign/Office/MO',
            'violation_code' => 'V1',
        ]);
        $model->violation_types()->attach([1,2]);
        $model =Violation::create([
            'violation' => 'Colorum',
            'violation_code' => 'V2',
        ]);
        $model->violation_types()->attach([3,4]);
        
    
        return ViolationResource::collection(Violation::all());
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
        // $vehicle_types = ViolationType::select('id', 'vehicle_type')->get()->groupBy('vehicle_type');
        $vehicle_types = ViolationType::with('violations')->get(["id", "vehicle_type"])->groupBy('vehicle_type');
        // foreach ($vehicle_types as $vehicleType) {
        //     # code...
        //     $v_group->put($vehicleType, ViolationResource::collection(ViolationType::where('vehicle_type', $vehicleType)->get()));
        // }
        // return response()->json(
        //     [
        //         'violations'=>$v_group,
        //         'vehicle_types'=>$vehicle_types
        //     ]
        // );
        // $vtest=[];
        // foreach ($vehicle_types as $vehicle) {
        //     $vtest = $vehicle->violations;
        // }
            // foreach ($vehicle_types as $type => $value) {
            //     $v_group->put($type, ViolationResource::collection(Violation::wh))
            // }
        return response()->json(
            // ViolationType::where('vehicle_type', '2-3-wheel')->get()
            $vehicle_types
        );
    }
}
