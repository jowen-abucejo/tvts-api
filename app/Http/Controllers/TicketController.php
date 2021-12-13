<?php

namespace App\Http\Controllers;

use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Nexmo\Laravel\Facade\Nexmo;
class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $limit = ($request->limit)?? 30;
        $order = ($request->order)?? 'DESC';
        return TicketResource::collection(Ticket::orderBy('datetime_of_apprehension', $order)->paginate($limit));
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
                'to'=>"63$ticket->violator->mobile_number",
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

    public function groupByDateAndCount(Request $request)
    {
        $data = (object)["ticket_count"=>[], "date"=>(object)[], "tickets"=>(object)[], "violation_count"=>[], "violator_count"=>[]];

        if(!$request->month || !$request->year){
            $start_date = now()->startOfMonth()->toDateString();
            $end_date = now()->endOfMonth()->toDateString();
            $ticket_count  = Ticket::where(
                'datetime_of_apprehension', '>=', $start_date
            )->where('datetime_of_apprehension', '<=', $end_date
            )->groupBy(['day_order', 'day'])->orderBy('day_order', 'ASC')->get(array(
                // DB::raw('date_format(datetime_of_apprehension, "%b-%d") as day'),//for mysql
                DB::raw("to_char(datetime_of_apprehension, 'Mon-DD') as day"),//for posgresql
                DB::raw('COUNT(*) as "total_tickets"'),
                //  DB::raw("date_format(datetime_of_apprehension, '%Y-%m-%d') as day_order")//for mysql
                 DB::raw("to_char(datetime_of_apprehension, 'YYYY-MM-DD') as day_order")//for posgresql
                )
            );
            if(count($ticket_count) >= 5){
                $data->ticket_count = $ticket_count;
                $data->date = ["month"=>now()->monthName, "year"=>now()->year];
                $data->tickets = TicketResource::collection(Ticket::where(
                        'datetime_of_apprehension', '>=', $start_date
                    )->where(
                        'datetime_of_apprehension', '<=', $end_date
                    )->orderBy('datetime_of_apprehension', 'DESC')->get()
                );
            }             
         } else if($request->month && $request->year) {
            $next_month = ($request->month<12)? $request->month + 1: 1;
            $start_date =  Carbon::createFromFormat('Y-m-d', $request->year.'-'.$request->month.'-01');
            $end_date = Carbon::createFromFormat('Y-m-d', $request->year.'-'.$next_month.'-01');
            $ticket_count  = Ticket::where(
                'datetime_of_apprehension', '>=', $start_date
            )->where(
                'datetime_of_apprehension', '<', $end_date
            )->groupBy(['day_order','day'])->orderBy('day_order', 'ASC')->get(
                array(
                    // DB::raw('date_format(datetime_of_apprehension, "%b-%d") as day'),//for mysql
                    DB::raw("to_char(datetime_of_apprehension, 'Mon-DD') as day"),//for posgresql
                    DB::raw('COUNT(*) as "total_tickets"'),
                    // DB::raw("date_format(datetime_of_apprehension, '%Y-%m-%d') as day_order")//for mysql
                    DB::raw("to_char(datetime_of_apprehension, 'YYYY-MM-DD') as day_order")//for posgresql

                )
            );
            if(count($ticket_count) >= 5){
                $data->ticket_count = $ticket_count;
                $data->date = ["month"=>$start_date->monthName, "year"=>$start_date->year];
                $data->tickets = TicketResource::collection(Ticket::where(
                        'datetime_of_apprehension', '>=', $start_date
                    )->where(
                        'datetime_of_apprehension', '<', $end_date
                    )->orderBy('datetime_of_apprehension', 'DESC')->get()
                );
            }
        }

        if(count($data->ticket_count) < 5){
            $data->ticket_count = Ticket::take(30)->groupBy(['day_order', 'day'])->orderBy('day_order', 'DESC')->get(
                array(
                    // DB::raw('date_format(datetime_of_apprehension, "%b-%d-%Y") as day'),//for mysql
                    DB::raw("to_char(datetime_of_apprehension, 'Mon-DD-YYYY') as day"),//for posgresql
                    DB::raw('COUNT(*) as "total_tickets"'),
                    // DB::raw("date_format(datetime_of_apprehension, '%Y-%m-%d') as day_order")//for mysql
                    DB::raw("to_char(datetime_of_apprehension, 'YYYY-MM-DD') as day_order")//for posgresql
                )
            )->sortBy(['day_order', 'ASC']);
            $data->date = ["month"=>"Latest", "year"=>''];
            $data->tickets = TicketResource::collection(Ticket::latest(
                )->take(30)->orderBy('datetime_of_apprehension', 'DESC')->get()
            );
        }
    
        $request->merge(['ticket_ids'=>$data->tickets->pluck('id')]);
        $data->violation_count = app('\App\Http\Controllers\ViolationController')->groupByAndCount($request);
        $data->violator_count = app('\App\Http\Controllers\ViolatorController')->groupByAndCount($request);
        return response()->json([
            "data" => $data,
            "all_ticket_count" => Ticket::count()
        ]);
    }

}
