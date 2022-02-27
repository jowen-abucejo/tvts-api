<?php

namespace App\Http\Controllers;

use App\Models\AssignTypes;
use Illuminate\Http\Request;

class AssignTypesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
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
     * @param  \App\Models\AssignTypes  $assignTypes
     * @return \Illuminate\Http\Response
     */
    public function show(AssignTypes $assignTypes)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AssignTypes  $assignTypes
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AssignTypes $assignTypes)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AssignTypes  $assignTypes
     * @return \Illuminate\Http\Response
     */
    public function destroy(AssignTypes $assignTypes, $type_id)
    {
        if($type_id && intval($type_id)) {
            $assignTypes = AssignTypes::find($type_id);
        }
        $assignTypes->delete();
        return response()->json(['deleted' => $assignTypes->trashed()]);
    }
}
