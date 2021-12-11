<?php

namespace App\Http\Controllers;

use App\Http\Resources\ViolatorResource;
use App\Models\Ticket;
use App\Models\Violator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ViolatorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return ViolatorResource::collection(Violator::all());
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
        $name = "$request->first_name, $request->middle_name, $request->last_name";
        $violator = (!$request->license_number)? Violator::where('name', $name)->first(): null;
        $violator = ($violator)?? Violator::updateOrCreate(
            [
                'license_number' => $request->license_number
            ],
            [
                'name' => $name,
                'address' => $request->address,
                'birth_date' => date('Y-m-d', strtotime($request->birth_date)),
                'mobile_number' => $request->mobile_number,
                'parent_and_license' => $request->parent_and_license,
            ]
        );
        return $violator;
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $violator_id = null)
    {
        $violator = ($violator_id)? Violator::find($violator_id) : Violator::where('license_number', $request->license_number)->first();
        if($violator)
            return new ViolatorResource($violator);
        return response(null, );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Violator  $violator
     * @return \Illuminate\Http\Response
     */
    public function edit(Violator $violator)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Violator  $violator
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Violator $violator)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Violator  $violator
     * @return \Illuminate\Http\Response
     */
    public function destroy(Violator $violator)
    {
        //
    }

    public function groupByAndCount(Request $request)
    {
        $data = (object)['all_violator_ticket_count'=>[], 'violator_ticket_count_within_date'=>[]];
        $all_ids = Ticket::select('id')->get();
        $within_date_ids = $request->ticket_ids?? [1];

        $grouped = Violator::select('id' )->withCount('tickets')->whereHas('tickets', function($query) use($within_date_ids) {
            return $query->whereIn('id', $within_date_ids);
        })->get();

        $data->violator_ticket_count_within_date = $grouped->mapToGroups(function ($item, $key) {
            return ["offense_".$item['tickets_count'] => $item['id']];
        });

        $grouped = Violator::select('id' )->withCount('tickets')->whereHas('tickets', function($query) use($all_ids) {
            return $query->whereIn('id', $all_ids);
        })->get();

        $data->all_violator_ticket_count = $grouped->mapToGroups(function ($item, $key) {
            return ["offense_".$item['tickets_count'] => $item['id']];
        });
        return $data;
    }
}
