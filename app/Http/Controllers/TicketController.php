<?php

namespace App\Http\Controllers;

use App\Http\Resources\TicketCollection;
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
        $start_date = $request->start_date? Carbon::createFromFormat('Y-m-d', $request->start_date)->startOfDay() :  null;
        $end_date = $request->end_date? Carbon::createFromFormat('Y-m-d', $request->end_date)->endOfDay() :  null;

        $limit = ($request->limit)?? 30;
        $order = ($request->order)?? 'DESC';
        $search = ($request->search)?? '';
        
        $like = (env('DB_CONNECTION') == 'pgsql') ? 'ILIKE' : 'LIKE';
        if($search_with_violator && !empty($search)){//if tickets can be search with violator details
            $violator_ids = app('\App\Http\Controllers\ViolatorController')->index($request, true);
            if(!empty($violator_ids)){
                return TicketResource::collection(Ticket::where('id', $like, '%'.$search.'%'
                    )->orWhere('ticket_number', $like, '%'.$search.'%'
                    )->orWhereIn('violator_id', $violator_ids
                    )->orderBy('datetime_of_apprehension', $order)->paginate($limit)
                );
                
            }

            //get with new records untracked by paginated results
            return $this->indexWithUnpaginatedRecords($request);      
        }

        if($start_date && $end_date){//if tickets are filtered by date
            return TicketResource::collection(Ticket::where(
                function ($query) use ($like, $search) {
                    $query->where('id', $like, '%'.$search.'%'
                        )->orWhere('ticket_number', $like, '%'.$search.'%'
                    );
                }
                )->where('datetime_of_apprehension', '>=', $start_date
                )->where('datetime_of_apprehension', '<=', $end_date
                )->orderBy('datetime_of_apprehension', $order
                )->paginate($limit)
            );
        }

        //get with new records untracked by paginated results
        return $this->indexWithUnpaginatedRecords($request);

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
        if(!$violator) return response('Violator is Null');
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
    public function dailyCount(Request $request)
    {
        $data = (object)[
            "daily_ticket"=>[],
            "date_range"=>[],
            "date"=>(object)[], 
            "tickets"=>[], 
            "violation_count"=>[], 
            "violator_count"=>[],
        ];
        $all_ticket_count = Ticket::count();

        if (intval($all_ticket_count) < 1)
            return response()->json([
                "data" => $data,
            ]);
        $data->all_ticket_count = $all_ticket_count;

        $start_date = (!$request->month || !$request->year)? now()->startOfMonth()->toDateString() : Carbon::createFromFormat('Y-m-d', $request->year.'-'.$request->month.'-01')->startOfMonth()->toDateString();
        $end_date = (!$request->month || !$request->year)? now()->endOfMonth()->toDateString() : Carbon::createFromFormat('Y-m-d', $request->year.'-'.$request->month.'-01')->endOfMonth()->toDateString();

        $day_format_query = env('DB_CONNECTION') == 'pgsql' 
            ? DB::raw("to_char(datetime_of_apprehension, 'Mon-DD') as day")
            : DB::raw('date_format(datetime_of_apprehension, "%b-%d") as day');
        $day_order_query = env('DB_CONNECTION') == 'pgsql' 
            ? DB::raw("to_char(datetime_of_apprehension, 'YYYY-MM-DD') as day_order") 
            : DB::raw('date_format(datetime_of_apprehension, "%Y-%m-%d") as day_order');
        $ticket_count_query = DB::raw('COUNT(*) as "total_tickets"');

        $daily_ticket  = Ticket::where(
            'datetime_of_apprehension', '>=', $start_date
        )->where('datetime_of_apprehension', '<=', $end_date
        )->groupBy(['day_order', 'day'])->orderBy('day_order', 'ASC')->get(array(
                $day_format_query,
                $day_order_query,
                $ticket_count_query,
            )
        );
        $data->daily_ticket = $daily_ticket;

        if(count($daily_ticket) >= 5){
            $data->date = ["month"=>now()->monthName, "year"=>now()->year];
            $data->tickets = TicketResource::collection(Ticket::where(
                    'datetime_of_apprehension', '>=', $start_date
                )->where(
                    'datetime_of_apprehension', '<=', $end_date
                )->orderBy('datetime_of_apprehension', 'DESC')->get()
            );
        } else {
            $daily_ticket = Ticket::take(30)->groupBy(['day_order', 'day'])->orderBy('day_order', 'DESC')->get(
                array(
                    $day_format_query,
                    $day_order_query,
                    $ticket_count_query
                )
            )->sortBy(['day_order', 'ASC']);
            
            $all_dates = $data->daily_ticket->pluck('day_order');
            $start_date =  Carbon::createFromFormat('Y-m-d', $all_dates[0])->startOfMonth()->toDateString();
            $end_date = Carbon::createFromFormat('Y-m-d', $all_dates[count($all_dates)-1])->endOfMonth()->toDateString();

            $data->date = ["month"=>"Latest", "year"=>''];
            $data->tickets = TicketResource::collection(Ticket::where(
                    'datetime_of_apprehension', '>=', $start_date
                )->where(
                    'datetime_of_apprehension', '<=', $end_date
                )->orderBy('datetime_of_apprehension', 'DESC')->get()
            );
        }
    
        $request->merge(['ticket_ids'=>$data->tickets->pluck('id')]);
        $data->violation_count = app('\App\Http\Controllers\ViolationController')->countEachTickets($request);
        $data->violator_count = app('\App\Http\Controllers\ViolatorController')->countEachTickets($request);
        return response()->json(["data" => $data]);
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
    
    public function showImage(Request $request, $image_path)
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

    function indexWithUnpaginatedRecords(Request $request)
    {
        $limit = ($request->limit)?? 30;
        $order = ($request->order)?? 'DESC';
        $search = ($request->search)?? '';
        $like = (env('DB_CONNECTION') == 'pgsql') ? 'ILIKE' : 'LIKE';
        $max_fetch_date = $request->max_fetch_date?  new DateTime($request->max_fetch_date) : Carbon::now();
        $max_date_paginated = $request->max_date_paginated?  new DateTime($request->max_date_paginated) : Carbon::now();

        //untracked records in pagination
        $unpaginated_results = TicketResource::collection(Ticket::where(
                function ($query) use ($like, $search) {
                    $query->where('id', $like, '%'.$search.'%'
                        )->orWhere('ticket_number', $like, '%'.$search.'%'
                    );
                }
            )->where('datetime_of_apprehension', '>', $max_fetch_date->format('Y-m-d H:i:s')
            )->orderBy('datetime_of_apprehension', 'DESC')->get()
        );

        //return tracked records in pagination
        return (new TicketCollection(Ticket::where(
            function($query) use($like, $search) {
                $query->where('id', $like, '%'.$search.'%'
                )->orWhere('ticket_number', $like, '%'.$search.'%');
            }
        )->where('datetime_of_apprehension', '<=', $max_date_paginated->format('Y-m-d H:i:s')
        )->orderBy('datetime_of_apprehension', $order
        )->paginate($limit)
        ))->additional(['meta' => [
            'new_records' => $unpaginated_results,
            'max_date_paginated' => $max_date_paginated->format('Y-m-d H:i:s')
        ]]);
    }

}
