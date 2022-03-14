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
        $search = ($request->search)? rawurldecode($request->search) : '';

        $like = (env('DB_CONNECTION') == 'pgsql') ? 'ILIKE' : 'LIKE';

        if($request->ticket_ids){
            return ViolationResource::collection(Violation::whereIn('id', $request->ticket_ids)->withTrashed()->get());
        }
        return ViolationResource::collection(Violation::withCount(['violation_types', 'tickets']
            )->with(['violation_types' => function($query) {
                $query->withCount(['violations']);
            }])->withTrashed()->where('violation_code', $like, '%'.$search.'%'
            )->orWhere('violation', $like, '%'.$search.'%'
            )->orderBy('violation', $order
            )->orderBy('violation_code', $order
            )->paginate($limit)
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
        try {
            $new_violation = preg_replace('!\s+!',' ', trim($request->violation));
            $violation_code = $request->violation_code? strtoupper(preg_replace('!\s+!',' ', trim($request->violation_code))) : null;
            $penalties = preg_replace("/([^0-9,]+)/", '', $request->penalties);
            $upper_case_column = env('DB_CONNECTION') == 'pgsql' ? 'UPPER("violation") = ?' : 'UPPER(`violation`) = ?';


            $checkCodeIfExist = $violation_code? Violation::whereRaw($upper_case_column, [ strtoupper($new_violation)
                ])->orWhere('violation_code', $violation_code)->count() : 0;
            if($checkCodeIfExist > 1) 
                return response()->json(["error" => "Violation Already Exist!", "message" => "Please provide a new one"], 400);

            $checkViolation = $checkCodeIfExist && $violation_code != ''
                ? Violation::with(['violation_types'])->whereRaw($upper_case_column, [ strtoupper($new_violation)]
                    )->where('violation_code', $violation_code)->first()
                :  Violation::with(['violation_types'])->whereRaw($upper_case_column, [ strtoupper($new_violation)]
                    )->first();
            
            if($checkViolation) {
                $type = $request->type;
                $vehicle_type = $request->vehicle_type;

                // $violation_type_check = $checkViolation->violation_types->count() > 0 ? $checkViolation->violation_types->first()->type : $type;
                $violation_vehicle_type_check = $checkViolation->violation_types->count() > 0 ? $checkViolation->violation_types->where('vehicle_type', $vehicle_type)->count() : 0;
                // if($violation_type_check != $type)
                //     return response()->json(["error" => "Already Set as $violation_type_check Offense", "message" => "Please set as $violation_type_check or provide new violation"], 400);
                if($violation_vehicle_type_check > 0 )
                    return response()->json(["error" => "Violation Already Exist!", "message" => "This violation is already assigned to $vehicle_type vehicle"], 400);
                    
                $new_type = ViolationType::firstOrNew([
                    "type" => $request->type,
                    "vehicle_type" => $request->vehicle_type,
                    "penalties" => $penalties,
                ],[]);
                $new_type->save();
                $checkViolation->violation_types()->attach($new_type->id);
    
                return new ViolationResource($checkViolation);        
            }

            $violation_model = Violation::create(
                [
                    'violation' => $new_violation,
                ]
            );

            $violation_model->violation_code = $violation_code && $violation_code != ''?? "V".$violation_model->id;
            $violation_model->save();
            
            $new_type = ViolationType::firstOrNew([
                "type" => $request->type,
                "vehicle_type" => $request->vehicle_type,
                "penalties" => $penalties,
            ],[]);
            $new_type->save();
            $violation_model->violation_types()->attach($new_type->id);

            return new ViolationResource($violation_model);
        } catch (\Exception $e) {
            return response()->json(["error" => "Violation Failed to Save!", "message" => "Please try again."], 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  number  $violation_id
     * @param  number  $violation_type_id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $violation_id = null, $violation_type_id = null)
    {
        if(!$violation_id || !intval($violation_id) || !$violation_type_id || !intval($violation_type_id)) return response(null, 404);

        $violation = Violation::withCount(['violation_types', 'tickets'])->with(
            ['violation_types' => 
                function($query) use ($violation_type_id) {
                    $query->find($violation_type_id);
                }
            ]
        )->find($violation_id);
        return new ViolationResource($violation);
    }

    /**
     * Update the status of violation's specific type
     *
     * @param  number $violation_id
     * @param  number $violation_type_id
     * @return \Illuminate\Http\Response
     */
    public function edit($violation_id, $violation_type_id)
    {
        if(!$violation_id || !intval($violation_id) || !$violation_type_id || !intval($violation_type_id))         
            return response()->json(['update_status' => false]);
        try {
            $violation = Violation::find($violation_id);

            if(!$violation)
                return response()->json(['deleted' => false]);
    
            $violation_type = $violation->violation_types()->find($violation_type_id);

            if($violation_type->pivot->deleted_at != null) {
                $violation_type->pivot->deleted_at = null;
                $violation_type->pivot->save();
            } else {
                $violation_type->pivot->deleted_at = now()->format('Y-m-d H:i:s');
                $violation_type->pivot->save();
            }
            return response()->json(['update_status' => true]);

        } catch (\Exception $e) {
            return response()->json(["error" => "Violation Failed to Change Status!", "message" => "Please try again."], 400);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  number  $violation_id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $violation_id = null, $violation_type_id = null)
    {
        if(!$violation_id || !intval($violation_id) || !$violation_type_id || !intval($violation_type_id))  
            return response()->json([
                "update_status" => "Failed"
            ]);

        try {
            $new_violation = preg_replace('!\s+!',' ', trim($request->violation));
            $violation_code = strtoupper(preg_replace('!\s+!',' ', trim($request->violation_code)));
            $penalties = preg_replace("/([^0-9,]+)/", '', $request->penalties);
            $upper_case_column = env('DB_CONNECTION') == 'pgsql' ? 'UPPER("violation") = ?' : 'UPPER(`violation`) = ?';

            $checkCodeIfExist = $violation_code? Violation::where('id', '!=', $violation_id)->where('violation_code', $violation_code)->count() : 0;
            if($checkCodeIfExist > 0)
                return response()->json(["error" => "Violation Code $violation_code Already Exist", "message" => "Please provide a new one."], 400);    

            $checkViolation = Violation::where('id', '!=', $violation_id)->whereRaw($upper_case_column,[ strtoupper($new_violation)
            ])->first();

            if($checkViolation) {
                return response()->json(["error" => "Violation Already Exist!", "message" => "Please try a different one."], 400);
            }

            $violation = Violation::withCount('tickets')->with(
                ['violation_types' => 
                    function($query) use ($violation_type_id) {
                        $query->withCount('violations');
                    }
                ]
            )->find($violation_id);

            $violation_type = $violation->violation_types->firstWhere('id', $violation_type_id);

            if($violation->tickets_count === 0) {
                $violation->violation = $request->violation;
                $violation->violation_code = $request->violation_code;
                $violation->save();
            } 

            $new_type = ViolationType::firstOrNew([
                "type" => $request->type,
                "vehicle_type" => $request->vehicle_type,
                "penalties" => $penalties,
            ],[]);
            $new_type->save();  
            
            if($violation_type->id != $new_type->id ) {
                $checkTypeCount = $violation->violation_types->where('vehicle_type', $request->vehicle_type)->where('type', $request->type)->count();
                
                if($checkTypeCount === 0) {
                    if($violation_type->violations_count === 1)
                        $violation_type->forceDelete();//previous violation type not assigned to other violations
                    $violation->violation_types()->detach($violation_type_id); 
                    $violation->violation_types()->attach($new_type->id);
                }
                return new ViolationResource($violation);
            }
            
            return new ViolationResource($violation);

        } catch (\Exception $e) {
            return response()->json(["error" => "Violation Failed to Update!", "message" => $e->getMessage()], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  number $violation_id
     * @param  number $violation_type_id
     * @return \Illuminate\Http\Response
     */
    public function destroy($violation_id, $violation_type_id)
    {
        if(!$violation_id || !intval($violation_id) || !$violation_type_id || !intval($violation_type_id))         
            return response()->json(['deleted' => false]);
        try {
            $deleted = Violation::withCount(['tickets', 'violation_types'])->with(
                ['violation_types' => 
                    function($query) use ($violation_type_id) {
                        $query->withCount('violations')->where('violation_type_id', $violation_type_id);
                    }
                ]
            )->find($violation_id);

            if(!$deleted)
                return response()->json(['deleted' => false]);
    
            $violation_type = $deleted->violation_types->find($violation_type_id);
    
            if($deleted && $deleted->tickets_count === 0 && $deleted->violation_types_count <= 1){
                if($violation_type && $violation_type->violations_count === 1){
                    $violation_type->forceDelete();
                }
                $deleted->violation_types()->detach($violation_type_id);
                $deleted->forceDelete();
                return response()->json(['deleted' => true]);
            }

            if($deleted && $deleted->tickets_count === 0){
                if($violation_type && $violation_type->violations_count === 1){
                    $violation_type->forceDelete();
                }
                $deleted->violation_types()->detach($violation_type_id);
                return response()->json(['deleted' => true]);
            }

            $violation_type->pivot->deleted_at = now()->format('Y:m:d H:i:s');
            return response()->json(['deleted' => true]);
        } catch (\Exception $e) {
            return response()->json(['deleted' => false]);
        }
        
    }

    public function groupByVehicleType()
    {
        if(Auth::user() && Auth::user()->isAdmin()){
            $vehicle_types = ViolationType::with(['violations'  => function ($query) {
                $query->orderBy('violation', 'ASC');
            }])->orderBy('vehicle_type', 'ASC')->get(["id", "vehicle_type"])->groupBy('vehicle_type');
            return response()->json(
                $vehicle_types
            );
        }

        $vehicle_types = ViolationType::with(['violations'  => function ($query) {
            $query->whereHas('violation_types')->wherePivotNull('deleted_at')->orderBy('violation', 'ASC');
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
