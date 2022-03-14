<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentCollection;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Ticket;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $start_date = $request->start_date? Carbon::createFromFormat('Y-m-d', $request->start_date)->startOfDay()->toDateTimeString() :  null;
        $end_date = $request->end_date? Carbon::createFromFormat('Y-m-d', $request->end_date)->endOfDay()->toDateTimeString() :  null;

        $limit = ($request->limit)?? 30;
        $order = ($request->order)?? 'DESC';
        $search = ($request->search)? rawurldecode($request->search) : '';
        
        $like = (env('DB_CONNECTION') == 'pgsql') ? 'ILIKE' : 'LIKE';

        if($start_date && $end_date){//if payments are filtered by date
            return PaymentResource::collection(Payment::with('ticket')->whereHas('ticket',
                function ($query) use ($like, $search) {
                    $query->where('ticket_number', $like, '%'.$search.'%'
                    );
                }
                )->orWhere('OR_number', $like, '%'.$search.'%'
                )->where('created_at', '>=', $start_date
                )->where('created_at', '<=', $end_date
                )->orderBy('created_at', $order
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
        $ticket = Ticket::where('ticket_number', $request->ticket_number)->first();
        $date_of_payment = new DateTime($request->date_of_payment);
        $payment = Payment::create([
            'OR_number' => strtoupper($request->or_number),
            'created_at' => $date_of_payment->format('Y:m:d H:i:s'),
            'penalties' => is_array($request->penalties) ? implode(',', $request->penalties) : $request->penalties.',',
            'total_amount' => $request->total_amount
        ]);

        $ticket->payment_id = $payment->id;
        $ticket->save();

        if($ticket->payment())
            return new PaymentResource($payment);
        return response()->json(["error" => "Payment Failed to Save!", "message" => "Please try again."], 400);
    }

    /**
     * Display the specified resource.
     *
     * @param  number $payment_id
     * @return \Illuminate\Http\Response
     */
    public function show($payment_id)
    {
        if(!$payment_id) return response('', 404);

        $payment = Payment::with('ticket')->find($payment_id);

        if(!$payment) return response('', 404);

        return new PaymentResource($payment);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function edit(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  number $payment_id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $payment_id)
    {
        if(!$payment_id) return response('', 404);

        $date_of_payment = new DateTime($request->date_of_payment);

        $payment = Payment::with('ticket')->find($payment_id);

        if(!$payment) return response('', 404);

        $payment->OR_number = strtoupper($request->or_number);
        $payment->created_at = $date_of_payment->format('Y:m:d H:i:s');
        $payment->penalties = is_array($request->penalties) ? implode(',', $request->penalties) : $request->penalties.',';
        $payment->total_amount = $request->total_amount;
        $payment->save();

        return new PaymentResource($payment);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  number $payment_id
     * @return \Illuminate\Http\Response
     */
    public function destroy($payment_id)
    {
        if(!$payment_id) return response()->json(['deleted' => false]);

        $deleted = Payment::with('ticket')->find($payment_id);

        if(!$deleted) return response()->json(['deleted' => false]);
        
        $ticket = $deleted->ticket;
        $ticket->payment_id = null;
        $ticket->save();
        $deleted->delete();
        return response()->json(['deleted' => true]);

    }

    function indexWithUnpaginatedRecords(Request $request)
    {
        $limit = ($request->limit)?? 30;
        $order = ($request->order)?? 'DESC';
        $search = ($request->search)? rawurldecode($request->search) : '';
        $like = (env('DB_CONNECTION') == 'pgsql') ? 'ILIKE' : 'LIKE';
        $max_fetch_date = $request->max_fetch_date?  new DateTime($request->max_fetch_date) : Carbon::now();
        $max_date_paginated = $request->max_date_paginated?  new DateTime($request->max_date_paginated) : Carbon::now();

        //untracked records in pagination
        $unpaginated_results = PaymentResource::collection(Payment::with('ticket')->whereHas('ticket',
                function ($query) use ($like, $search) {
                    $query->where('ticket_number', $like, '%'.$search.'%'
                    );
                }
            )->orWhere(
                function($query) use($max_fetch_date, $like, $search) {
                    $query->where('created_at', '>', $max_fetch_date->format('Y-m-d H:i:s')
                    )->where('OR_number', $like, '%'.$search.'%');
                }
            )->orderBy('created_at', 'DESC')->get()
        );

        //return tracked records in pagination
        return (new PaymentCollection(Payment::with('ticket')->whereHas('ticket',
            function($query) use($like, $search) {
                $query->where('ticket_number', $like, '%'.$search.'%');
            }
        )->orWhere(
            function($query) use($max_date_paginated, $like, $search) {
                $query->where('created_at', '<=', $max_date_paginated->format('Y-m-d H:i:s')
                )->where('OR_number', $like, '%'.$search.'%');
            }
        )->orderBy('created_at', $order
        )->paginate($limit)
        ))->additional(['meta' => [
            'new_records' => $unpaginated_results,
            'max_date_paginated' => $max_date_paginated->format('Y-m-d H:i:s')
        ]]);
    }
}
