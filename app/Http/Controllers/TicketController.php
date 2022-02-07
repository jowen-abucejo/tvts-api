<?php

namespace App\Http\Controllers;

use App\Http\Resources\TicketResource;
use App\Mail\TicketIssued;
use App\Models\Ticket;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Nexmo\Laravel\Facade\Nexmo;
class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param boolean $search_with_violator
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $search_with_violator = true)
    {
        $limit = ($request->limit)?? 30;
        $order = ($request->order)?? 'DESC';
        $search = ($request->search)?? '';
        if($search_with_violator && !empty($search)){
            $violator_ids = app('\App\Http\Controllers\ViolatorController')->index($request, true);
            if(!empty($violator_ids)){
                return TicketResource::collection(Ticket::where('id', 'LIKE', '%' .$search.'%'
                    )->orWhere('ticket_number', 'LIKE', '%' .$search.'%'
                    )->orWhereIn('violator_id', $violator_ids
                    )->orderBy('datetime_of_apprehension', $order)->paginate($limit)
                );
                
            }
            return TicketResource::collection(Ticket::where('id', 'LIKE', '%' .$search.'%'
                )->orWhere('ticket_number', 'LIKE', '%' .$search.'%'
                )->orderBy('datetime_of_apprehension', $order)->paginate($limit)
            );
        } else {
            return TicketResource::collection(Ticket::where('id', 'LIKE', '%' .$search.'%'
                )->orWhere('ticket_number', 'LIKE', '%' .$search.'%'
                )->orderBy('datetime_of_apprehension', $order)->paginate($limit)
            );
        }
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
        $violator_id = $request->violator_id ?? null;
        $violator = app('\App\Http\Controllers\ViolatorController')->store($request, $violator_id);
        // $filepath = ($request->hasFile('drivers_id'))?$request->file('drivers_id')->store('ids'):'';
        $ticket_extra_properties = app('\App\Http\Controllers\ExtraPropertyController')->index($request, 'ticket');
        $date = $request->apprehension_datetime? new DateTime($request->apprehension_datetime): now();
        if(!$violator) return response('Violator is Null'+$violator);
        $ticket = 
            $violator && $violator->id ? 
            auth()->user()->ticketIssued()->create(
                [
                    'violator_id' => $violator->id,
                    'offense_number' => intval($violator->tickets_count) + 1,
                    'vehicle_type' => $request->vehicle_type,
                    'datetime_of_apprehension' => $date->format('Y-m-d H:i:s'),
                ]
            ) 
            : null;
        if($ticket){
            $ticket->ticket_number = "TN$ticket->id";
            $ticket->save();

            $violation_ids = explode(',',$request->committed_violations);
            $ticket->violations()->attach($violation_ids);

            foreach ($ticket_extra_properties as $ext) {
                if($ext->data_type == 'image'){
                    $key = $ext->property.'';
                    $file = ($request->hasFile($key))? $request->file($key) : null;
                    $filepath = ($file)? $file->store($key.'_'.$ext->id) : 'NA';
                    $ticket->extraProperties()->create([
                        'extra_property_id' => $ext->id,
                        'property_value' => $filepath,
                    ]);
                } else {
                    $ticket->extraProperties()->create([
                        'extra_property_id' => $ext->id,
                        'property_value' => $request->input($ext->property),
                    ]);
                }
            }
            
            $err='';
            try {
                $mobile = $ticket->with(['violator.extraProperties' => function ($query) {
                    $query->whereRelation('propertyDescription','property', 'mobile_number');
                }])->first()->violator->extraProperties[0]->property_value;
                
                if($mobile){
                    // Nexmo::message()->send([
                    //     'to'=>"63".$mobile,
                    //     'from'=>'Naic PNP/NTMO',
                    //     'text'=>"Citation Ticket $ticket->ticket_number was issued to you. Please appear at the Naic PNP/NTMO  within 72 hours to answer the stated charges.Failing to settle your case within 15 days from date of apprehension will result to the suspension/revocation of your license.",
                    // ]);
                }
            } catch (\Throwable $th) {
                $err = $th->getMessage();
            }

            try {
                $email = $ticket->with(['violator.extraProperties' => function ($query) {
                    $query->whereRelation('propertyDescription','property', 'email_address');
                }])->first()->violator->extraProperties[0]->property_value;

            } catch (\Throwable $th) {
                $err = $th->getMessage();
            }
           
            return new TicketResource($ticket);
        } else {
            return response('Ticket is Null');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  string|null $ticket_number
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $ticket_number = null)
    {
        $ticket = ($ticket_number)? Ticket::where('ticket_number', "$ticket_number")->first() : null;
        if($ticket)
            return new TicketResource($ticket);

        return response(null);
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

    }

    /**
     * Get ticket count for each day within the given period of time
     */
    public function groupByDateAndCount(Request $request)
    {
        $data = (object)["ticket_count"=>[], "date"=>(object)[], "tickets"=>(object)[], "violation_count"=>[], "violator_count"=>[]];
        $start_date = now()->startOfMonth()->toDateString();
        $end_date = now()->endOfMonth()->toDateString();
        if(!$request->month || !$request->year){
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
        }else{}

        if(count($data->ticket_count) < 5){
            if(env('DB_CONNECTION') == 'pgsql'){
                $data->ticket_count = Ticket::take(30)->groupBy(['day_order', 'day'])->orderBy('day_order', 'DESC')->get(
                    array(
                        DB::raw("to_char(datetime_of_apprehension, 'Mon-DD-YYYY') as day"),//for posgresql
                        DB::raw('COUNT(*) as "total_tickets"'),
                        DB::raw("to_char(datetime_of_apprehension, 'YYYY-MM-DD') as day_order")//for posgresql
                    )
                )->sortBy(['day_order', 'ASC']);
            } else {
                    $data->ticket_count = Ticket::take(30)->groupBy(['day_order', 'day'])->orderBy('day_order', 'DESC')->get(
                        array(
                            DB::raw('date_format(datetime_of_apprehension, "%b-%d-%Y") as day'),//for mysql
                            DB::raw('COUNT(*) as "total_tickets"'),
                            DB::raw("date_format(datetime_of_apprehension, '%Y-%m-%d') as day_order")//for mysql
                        )
                    )->sortBy(['day_order', 'ASC']);
            }
            $data->date = ["month"=>"Latest", "year"=>''];

            $all_dates= $data->ticket_count->pluck('day_order');
            $start_date =  Carbon::createFromFormat('Y-m-d', $all_dates[0]);
            $end_date = Carbon::createFromFormat('Y-m-d', $all_dates[count($all_dates)-1]);
            $data->tickets = TicketResource::collection(Ticket::where(
                    'datetime_of_apprehension', '>=', $start_date
                )->where(
                    'datetime_of_apprehension', '<=', $end_date
                )->orderBy('datetime_of_apprehension', 'DESC')->get()
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

    public function emailQRCode(Request $request, $ticket_number)
    {
        $ticket = Ticket::with(['violator.extraProperties' => function ($query) {
            $query->whereRelation('propertyDescription','property', 'email_address');
        }])->where('ticket_number', $ticket_number)->first();
        $email_address = ($ticket && count($ticket->violator->extraProperties) > 0)? $ticket->violator->extraProperties[0]->property_value : null;
        $hasQR = $request->hasFile('qrImage');
        if($ticket && $hasQR && $email_address){
           try {
            $qr_path = $request->file('qrImage')->store('temp');
            $new_email = new TicketIssued($ticket->ticket_number, $qr_path);
            Mail::to($email_address, $ticket->violator->first_name.' '. $ticket->violator->last_name)->send($new_email);
            Storage::delete($qr_path);
            return response()->json(["email_complete" => true]);           
            } catch (\Throwable $th) {
                return response()->json(["email_complete" => false]); 
            }
        }
        
        if(!$hasQR)
            return response()->json(["error" => "QR Code Not Found", "message" => "No QR Code image received."],); 
        else if(!$email_address)
            return response()->json(["error" => "Email Address Not Found", "message" => "No receiver email address found."],); 
        else
            return response()->json(["error" => "Ticket Not Found!", "message" => "Ticket $ticket_number not found."],); 
    }
    
    public function testShowImage(Request $request, $image_path)
    {
        $real_path = str_replace(' ','/', $image_path);
        if (Storage::exists($real_path)) {
            $metaData = Storage::getMetaData($real_path);
            if($metaData == false) {
                return response()->json(["error" => "File Not Found!"], 404);
            }
            return response()->file(storage_path('/app').'/'.($real_path));
        }
        return response()->json(["error" => "File Not Found!"], 404);

    }

}
