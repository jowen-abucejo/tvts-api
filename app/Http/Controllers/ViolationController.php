<?php

namespace App\Http\Controllers;

use App\Http\Resources\ViolationResource;
use App\Models\Ticket;
use App\Models\Violation;
use App\Models\ViolationType;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViolationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $limit = ($request->limit)?? 30;
        $order = ($request->order)?? 'ASC';
        $search = ($request->search)?? '';

        $like = (env('DB_CONNECTION') == 'pgsql') ? 'ILIKE' : 'LIKE';

        if($request->ticket_ids){
            return ViolationResource::collection(Violation::whereIn('id', $request->ticket_ids)->withTrashed()->get());
        }
        if(Auth::user() && Auth::user()->isAdmin()){
            return ViolationResource::collection(Violation::withTrashed()->where('violation_code', $like, '%'.$search.'%'
                )->orWhere('violation', $like, '%'.$search.'%'
                )->orderBy('violation', $order
                )->orderBy('violation_code', $order
                )->paginate($limit)
            );
        }
        return ViolationResource::collection(Violation::with('violation_types')->whereHas('violation_types')->get()
        );
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
     * @param  \App\Models\Violation  $violation
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $v = Violation::where('id', $id)->withTrashed()->first();
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
     * @param  number  $violation_id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $violation_id)
    {
        $status = "Failed";
        if(!$violation_id || !intval($violation_id))  return response()->json([
            "update_status" => $status
        ]);

        try {
            $violation = Violation::find($violation_id);
            $violation->violation = $request->violation;
            $violation->violation_code = $request->violation_code;
            $violation->save();

            $status = "Incomplete";

            if(!app('\App\Http\Controllers\ViolationTypeController')->update($request, $request->violation_type_id, true)) return response()->json([
                "update_status" => $status
            ]);

            return new ViolationResource(Violation::find($violation_id));

        } catch (\Exception $e) {
            return response()->json([
                "update_status" => $status
            ]);
        }
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
        if(Auth::user() && Auth::user()->isAdmin()){
            $vehicle_types = ViolationType::with('violations')->orderBy('vehicle_type', 'ASC')->get(["id", "vehicle_type"])->groupBy('vehicle_type');
            return response()->json(
                $vehicle_types
            );
        }

        $vehicle_types = ViolationType::with(['violations'  => function ($query) {
            $query->whereHas('violation_types')->wherePivotNull('deleted_at');
        }])->orderBy('vehicle_type', 'ASC')->get(["id", "vehicle_type"])->groupBy('vehicle_type');
        return response()->json(
            $vehicle_types
        );
    }

    public function countEachTickets(Request $request)
    {
        $data = [];
        $within_date_ids = $request->ticket_ids?? [0,];
        $data = Violation::join('ticket_violation', 'violations.id', '=', 'ticket_violation.violation_id'
            )->whereIn('ticket_id', $within_date_ids)->groupBy(['violation',])->orderBy('total_tickets', 'DESC'
            )->get( 
                array(
                    DB::raw('violation'),
                    DB::raw('COUNT(*) as "total_tickets"')
                )
            );
        return $data;
    }
}
