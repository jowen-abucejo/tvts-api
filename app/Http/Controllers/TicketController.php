<?php

namespace App\Http\Controllers;

use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
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
        $ticket->ticket_number = "#$ticket->id";
        $ticket->save();
        $ticket->violations()->attach(explode(',',$request->committed_violations));
        
        return new TicketResource($ticket);
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
        Nexmo::message()->send([
            'to'=>"639457833077",
            'from'=>'TVTS_API',
            'text'=>'Test SMS from api',
        ]);
        return "HELLO SMS";
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
}
