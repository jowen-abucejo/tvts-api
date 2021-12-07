<?php

namespace App\Http\Controllers;

use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Nexmo\Laravel\Facade\Nexmo;
class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return TicketResource::collection(Ticket::all());
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
        $violator = app('\App\Http\Controllers\ViolatorController')->store($request);
        $filepath = ($request->hasFile('drivers_id'))?$request->file('drivers_id')->store('ids'):'';
        $ticket = auth()->user()->ticketIssued()->create(
            [
                'violator_id' => $violator->id,
                'vehicle_type' => $request->vehicle_type,
                'plate_number' => $request->plate_number,
                'vehicle_owner' => $request->vehicle_owner,
                'owner_address' => $request->owner_address,
                'datetime_of_apprehension' => date('Y-m-d H:m:s', strtotime($request->apprehension_date_time)),
                'place_of_apprehension' => $request->apprehension_place,
                'vehicle_is_impounded' => ($request->vehicleIsImpounded && $request->vehicleIsImpounded == 'true' )? 1:0,
                'is_under_protest' => ($request->driverIsUnderProtest && $request->driverIsUnderProtest == 'true')? 1:0,
                'license_is_confiscated' => ($request->licenseIsConfiscated && $request->licenseIsConfiscated == 'true')? 1:0,
                'document_signature' => $filepath,
            ]
        );
        if($ticket){
            $ticket->ticket_number = "#$ticket->id";
            $ticket->save();

            $violation_ids = explode(',',$request->committed_violations);
            $ticket->violations()->attach($violation_ids);
            
            Nexmo::message()->send([
                'to'=>$ticket->mobile_number,
                'from'=>'Naic PNP/NTMO',
                'text'=>"Citation Ticket $ticket->ticket_number was issued to you. Please appear at the Naic PNP/NTMO  within 72 hours to answer the stated charges. 
                Failing to settle your case within 15 days from date of apprehension will result to the suspension/revocation of your license.",
            ]);
            return new TicketResource($ticket);
        } else {
            return null;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Ticket  $ticket
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $ticket_id = null)
    {
        $ticket_number = str_replace('#', '', $request->ticket_number);
        $ticket = ($ticket_id)? Ticket::find($ticket_id) : Ticket::where('ticket_number', "#$ticket_number")->first();
        if($ticket)
            return new TicketResource($ticket);
        return response(null, );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Ticket  $ticket
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
       
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Ticket  $ticket
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Ticket $ticket)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Ticket  $ticket
     * @return \Illuminate\Http\Response
     */
    public function destroy(Ticket $ticket)
    {
        //
    }

    public function countByGroupDate(Request $request)
    {
        if(!$request->month || !$request->year){
           $tickets  = Ticket::where(
                'datetime_of_apprehension', '>=', now()->startOfMonth()->toDateString()
            )->where('datetime_of_apprehension', '<=', now()->endOfMonth()->toDateString()
            )->groupBy('day')->orderBy('day', 'ASC')->get(array(
                    DB::raw('date_format(datetime_of_apprehension, "%b-%d") as day'),
                    DB::raw('COUNT(*) as "total_tickets"')
                )
            );
            return response()->json([
                "data" => $tickets,
                "date" => ["month"=>now()->monthName, "year"=>now()->year]
            ]);
        } else if($request->month && $request->year) {
            $next_month = ($request->month<12)? $request->month + 1: 1;
            $tickets  = Ticket::where(
                'datetime_of_apprehension', '>=', Carbon::createFromFormat('Y-m-d', $request->year.'-'.$request->month.'-01')
            )->where(
                'datetime_of_apprehension', '<', Carbon::createFromFormat('Y-m-d', $request->year.'-'.$next_month.'-01')
            )->groupBy('day')->orderBy('day', 'ASC')->get(
                array(
                    DB::raw('date_format(datetime_of_apprehension, "%b-%d") as day'),
                    DB::raw('COUNT(*) as "total_tickets"')
                )
            );
            return response()->json([
                "data" => $tickets,
                "date" => ["month"=>Carbon::createFromFormat('Y-m-d', $request->year.'-'.$request->month.'-01')->monthName, "year"=>Carbon::createFromFormat('Y-m-d', $request->year.'-'.$request->month.'-01')->year]
            ]);
        } else {
            return null;
        }
    }
}
