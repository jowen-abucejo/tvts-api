<?php

namespace App\Http\Controllers;

use App\Http\Resources\TicketCollection;
use App\Http\Resources\TicketResource;
use App\Mail\TicketIssued;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Facades\Vonage;
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
        $start_date = $request->start_date
            ? Carbon::createFromFormat("Y-m-d", $request->start_date)
                ->startOfDay()
                ->toDateTimeString()
            : null;
        $end_date = $request->end_date
            ? Carbon::createFromFormat("Y-m-d", $request->end_date)
                ->endOfDay()
                ->toDateTimeString()
            : null;

        $limit = $request->limit ?? 30;
        $order = $request->order ?? "DESC";
        $search = $request->search ? rawurldecode($request->search) : "";

        $like = env("DB_CONNECTION") == "pgsql" ? "ILIKE" : "LIKE";
        if ($search_with_violator && !empty($search)) {
            //if tickets can be search with violator details
            $violator_ids = app(
                "\App\Http\Controllers\ViolatorController"
            )->index($request, true);
            if (!empty($violator_ids)) {
                return TicketResource::collection(
                    Ticket::with("payment")
                        ->where("id", $like, "%" . $search . "%")
                        ->orWhere("ticket_number", $like, "%" . $search . "%")
                        ->orWhereIn("violator_id", $violator_ids)
                        ->orderBy("datetime_of_apprehension", $order)
                        ->paginate($limit)
                );
            }

            //get with new records untracked by paginated results
            return $this->indexWithUnpaginatedRecords($request);
        }

        if ($start_date && $end_date) {
            //if tickets are filtered by date
            return TicketResource::collection(
                Ticket::with("payment")
                    ->where(function ($query) use ($like, $search) {
                        $query
                            ->where("id", $like, "%" . $search . "%")
                            ->orWhere(
                                "ticket_number",
                                $like,
                                "%" . $search . "%"
                            );
                    })
                    ->where("datetime_of_apprehension", ">=", $start_date)
                    ->where("datetime_of_apprehension", "<=", $end_date)
                    ->orderBy("datetime_of_apprehension", $order)
                    ->paginate($limit)
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
        $tnCheck = $request->ticket_number
            ? strtoupper(str_replace(" ", "", $request->ticket_number . ""))
            : null;
        $apprehending_officer = $request->officer_user_id
            ? User::find($request->officer_user_id)
            : $request->user();
        if ($tnCheck != null && $tnCheck != "") {
            $check = Ticket::where("ticket_number", "=", $tnCheck)->count();
            if ($check > 0) {
                return response()->json(
                    [
                        "error" => "Ticket Creation Failed!",
                        "message" => "Ticket $tnCheck already exist.",
                    ],
                    400
                );
            }
        }
        $violator_id = $request->violator_id ?? null;
        $violator = app("\App\Http\Controllers\ViolatorController")->store(
            $request,
            $violator_id
        );
        $ticket_extra_properties = app(
            "\App\Http\Controllers\ExtraPropertyController"
        )->index($request, "ticket");
        $date = $request->apprehension_datetime
            ? new DateTime($request->apprehension_datetime)
            : now();
        if (!$violator) {
            return response("Violator is Null");
        }
        $ticket =
            $violator && $violator->id
                ? $apprehending_officer->ticketIssued()->create([
                    "violator_id" => $violator->id,
                    "offense_number" => intval($violator->tickets_count) + 1,
                    "vehicle_type" => $request->vehicle_type,
                    "datetime_of_apprehension" => $date->format("Y-m-d H:i:s"),
                ])
                : null;
        if ($ticket) {
            $tn =
                $tnCheck &&
                (strpos($tnCheck, "TN") === false || strpos($tnCheck, "TN") > 0)
                    ? $tnCheck
                    : "TN$ticket->id";
            $ticket->ticket_number = $tn;
            $ticket->save();

            $violation_ids = explode(",", $request->committed_violations);
            $ticket->violations()->attach($violation_ids);

            foreach ($ticket_extra_properties as $ext) {
                if ($ext->data_type == "image") {
                    $key = $ext->property . "";
                    // $folder = "/" . $key . "_" . $ext->id . "/";
                    $folder = "/";
                    $file = $request->hasFile($key)
                        ? $request->file($key)
                        : null;

                    $filepath = $file ? $file->store($folder, "spaces") : "NA";
                    $ticket->extraProperties()->create([
                        "extra_property_id" => $ext->id,
                        "property_value" => $filepath,
                    ]);
                } else {
                    $ticket->extraProperties()->create([
                        "extra_property_id" => $ext->id,
                        "property_value" => $request->input($ext->property),
                    ]);
                }
            }

            $err = "";
            try {
                $mobile = $ticket
                    ->violator()
                    ->with([
                        "extraProperties" => function ($query) {
                            $query->whereRelation(
                                "propertyDescription",
                                "property",
                                "mobile_number"
                            );
                        },
                    ])
                    ->first()->extraProperties[0]->property_value;

                if ($mobile && env("APP_ENV") == "production") {
                    Vonage::message()->send([
                        "to" => "63" . $mobile,
                        "from" => "Naic PNP/NTMO",
                        "text" => "Citation Ticket $ticket->ticket_number was issued to you. Please appear at the Naic PNP/NTMO  within 72 hours to answer the stated charges.Failing to settle your case within 15 days from date of apprehension will result to the suspension/revocation of your license.",
                    ]);
                }
            } catch (\Throwable $th) {
                $err = $th->getMessage();
            }

            //store activity log to database
            app("\App\Http\Controllers\ActivityLogController")->store(
                $request,
                [
                    "time_log" => now()->format("Y-m-d H:i:s"),
                    "activity" =>
                        "Created citation ticket " . $ticket->ticket_number,
                ]
            );

            //return Ticket Resource for the created Ticket
            return new TicketResource($ticket);
        } else {
            return response("Ticket is Null", 404);
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
        $ticket_number = trim(strtoupper($ticket_number));
        $ticket = $ticket_number
            ? Ticket::where("ticket_number", "$ticket_number")->first()
            : null;
        if ($ticket) {
            return new TicketResource($ticket);
        }
        if ($request->ticket_id) {
            return new TicketResource(Ticket::find($request->ticket_id));
        }
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
     * @param  number $ticket_id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $ticket_id)
    {
        $status = "Failed";
        if (!$ticket_id || !intval($ticket_id)) {
            return response()->json([
                "update_status" => $status,
            ]);
        }

        try {
            $ticket = Ticket::find($ticket_id);
            $date = new DateTime($request->apprehension_datetime);
            $tnCheck = $request->ticket_number
                ? strtoupper(str_replace(" ", "", $request->ticket_number . ""))
                : null;

            if (
                $tnCheck &&
                (strpos($tnCheck, "TN") === false || strpos($tnCheck, "TN") > 0)
            ) {
                $ticket->ticket_number = $tnCheck;
            }
            $ticket->vehicle_type = $request->vehicle_type;
            $ticket->datetime_of_apprehension = $date->format("Y-m-d H:i:s");
            if ($request->officer_user_id) {
                $ticket->issued_by = $request->officer_user_id;
            }
            $ticket->save();

            $status = "Incomplete";
            if (
                !app("\App\Http\Controllers\ViolatorController")->update(
                    $request,
                    $ticket->violator->id,
                    true
                )
            ) {
                return response()->json([
                    "update_status" => $status,
                ]);
            }

            $violation_ids = explode(",", $request->committed_violations);
            $ticket->violations()->sync($violation_ids);

            foreach ($ticket->extraProperties as $ext) {
                $key = $ext->PropertyDescription->property;
                if ($ext->PropertyDescription->data_type == "image") {
                    $file = $request->hasFile($key)
                        ? $request->file($key)
                        : null;
                    // $folder = "/" . $key . "_" . $ext->id . "/";
                    $folder = "/";
                    $filepath = $file ? $file->store($folder) : null;
                    if ($file && $filepath) {
                        Storage::disk("spaces")->delete($ext->property_value);
                        $ext->property_value = $filepath;
                        $ext->save();
                    }
                } else {
                    $ext->property_value = $request->input($key);
                    $ext->save();
                }
            }

            //store activity log to database
            app("\App\Http\Controllers\ActivityLogController")->store(
                $request,
                [
                    "time_log" => now()->format("Y-m-d H:i:s"),
                    "activity" =>
                        "Updated citation ticket " . $ticket->ticket_number,
                ]
            );

            return new TicketResource(Ticket::find($ticket_id));
        } catch (\Exception $e) {
            return response()->json([
                "update_status" => $status,
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  number $ticket_id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $ticket_id)
    {
        $deleted = Ticket::find($ticket_id);
        $deleted->delete();

        //store activity log to database
        app("\App\Http\Controllers\ActivityLogController")->store($request, [
            "time_log" => now()->format("Y-m-d H:i:s"),
            "activity" =>
                "Soft deleted citation ticket " . $deleted->ticket_number,
        ]);

        return response()->json(["deleted" => $deleted->trashed()]);
    }

    /**
     * Get ticket count for each day within the given period of time
     */
    public function dailyCount(Request $request)
    {
        $data = (object) [
            "daily_ticket" => [],
            "date_range" => [],
            "date" => (object) [],
            "tickets" => [],
            "violation_count" => [],
            "violator_count" => [],
        ];
        $all_ticket_count = Ticket::count();
        $customRange = $request->start_date && $request->end_date;
        if (intval($all_ticket_count) < 1) {
            return response()->json([
                "data" => $data,
            ]);
        }
        $data->all_ticket_count = $all_ticket_count;

        $start_date = !$request->start_date
            ? now()
                ->startOfMonth()
                ->toDateTimeString()
            : Carbon::createFromFormat("Y-m-d", $request->start_date)
                ->startOfDay()
                ->toDateTimeString();
        $end_date = !$request->end_date
            ? now()
                ->endOfMonth()
                ->toDateTimeString()
            : Carbon::createFromFormat("Y-m-d", $request->end_date)
                ->endOfDay()
                ->toDateTimeString();

        $day_format_query =
            env("DB_CONNECTION") == "pgsql"
                ? DB::raw("to_char(datetime_of_apprehension, 'Mon-DD') as day")
                : DB::raw(
                    'date_format(datetime_of_apprehension, "%b-%d") as day'
                );
        $day_order_query =
            env("DB_CONNECTION") == "pgsql"
                ? DB::raw(
                    "to_char(datetime_of_apprehension, 'YYYY-MM-DD') as day_order"
                )
                : DB::raw(
                    'date_format(datetime_of_apprehension, "%Y-%m-%d") as day_order'
                );
        $ticket_count_query = DB::raw('COUNT(*) as "total_tickets"');

        $daily_ticket = Ticket::where(
            "datetime_of_apprehension",
            ">=",
            $start_date
        )
            ->where("datetime_of_apprehension", "<=", $end_date)
            ->groupBy(["day_order", "day"])
            ->orderBy("day_order", "ASC")
            ->get([$day_format_query, $day_order_query, $ticket_count_query]);

        if (count($daily_ticket) >= 5 || $customRange) {
            $data->daily_ticket = $daily_ticket;
            $data->date = $customRange
                ? [
                    "month" =>
                        Carbon::createFromFormat(
                            "Y-m-d",
                            $request->start_date
                        )->toFormattedDateString() . " to",
                    "year" => Carbon::createFromFormat(
                        "Y-m-d",
                        $request->end_date
                    )->toFormattedDateString(),
                ]
                : ["month" => now()->monthName, "year" => now()->year];
            $data->tickets = TicketResource::collection(
                Ticket::where("datetime_of_apprehension", ">=", $start_date)
                    ->where("datetime_of_apprehension", "<=", $end_date)
                    ->orderBy("datetime_of_apprehension", "DESC")
                    ->get()
            );
        } else {
            $data->daily_ticket = Ticket::take(30)
                ->groupBy(["day_order", "day"])
                ->orderBy("day_order", "DESC")
                ->get([
                    $day_format_query,
                    $day_order_query,
                    $ticket_count_query,
                ])
                ->sortBy(["day_order", "ASC"]);

            $all_dates = $data->daily_ticket->pluck("day_order");
            $start_date = Carbon::createFromFormat("Y-m-d", $all_dates[0])
                ->startOfMonth()
                ->toDateTimeString();
            $end_date = Carbon::createFromFormat(
                "Y-m-d",
                $all_dates[count($all_dates) - 1]
            )
                ->endOfMonth()
                ->toDateTimeString();

            $data->date = ["month" => "Latest", "year" => ""];
            $data->tickets = TicketResource::collection(
                Ticket::where("datetime_of_apprehension", ">=", $start_date)
                    ->where("datetime_of_apprehension", "<=", $end_date)
                    ->orderBy("datetime_of_apprehension", "DESC")
                    ->get()
            );
        }

        $request->merge(["ticket_ids" => $data->tickets->pluck("id")]);
        $data->violation_count = app(
            "\App\Http\Controllers\ViolationController"
        )->countEachTickets($request);
        // $data->violator_count = app('\App\Http\Controllers\ViolatorController')->countEachTickets($request);
        $data->violator_count = Ticket::where(
            "datetime_of_apprehension",
            ">=",
            $start_date
        )
            ->where("datetime_of_apprehension", "<=", $end_date)
            ->groupBy("offense_number")
            ->orderBy("offense_number", "DESC")
            ->get([
                DB::raw("offense_number"),
                DB::raw("count(*) as total_violator"),
            ]);
        return response()->json(["data" => $data]);
    }

    public function emailQRCode(Request $request, $ticket_number)
    {
        $ticket = Ticket::with([
            "violator.extraProperties" => function ($query) {
                $query->whereRelation(
                    "propertyDescription",
                    "property",
                    "email_address"
                );
            },
        ])
            ->where("ticket_number", $ticket_number)
            ->first();
        $email_address =
            $ticket && count($ticket->violator->extraProperties) > 0
                ? $ticket->violator->extraProperties[0]->property_value
                : null;
        $hasQR = $request->hasFile("qrImage");
        if ($ticket && $hasQR && $email_address) {
            try {
                $qr_path = $request->file("qrImage")->store("temp");
                $new_email = new TicketIssued($ticket->ticket_number, $qr_path);
                Mail::to(
                    $email_address,
                    $ticket->violator->first_name .
                        " " .
                        $ticket->violator->last_name
                )->send($new_email);
                Storage::delete($qr_path);
                return response()->json(["email_complete" => true]);
            } catch (\Throwable $th) {
                return response()->json(["email_complete" => false]);
            }
        }

        if (!$hasQR) {
            return response()->json([
                "error" => "QR Code Not Found",
                "message" => "No QR Code image received.",
            ]);
        } elseif (!$email_address) {
            return response()->json([
                "error" => "Email Address Not Found",
                "message" => "No receiver email address found.",
            ]);
        } else {
            return response()->json([
                "error" => "Ticket Not Found!",
                "message" => "Ticket $ticket_number not found.",
            ]);
        }
    }

    public function showImage(Request $request, $image_path)
    {
        $real_path = str_replace(" ", "/", $image_path);
        if (Storage::disk("spaces")->exists($real_path)) {
            $metaData = Storage::disk("spaces")->getMetaData($real_path);
            if ($metaData == false) {
                return response()->json(["error" => "File Not Found!"], 404);
            }
            // return response()->file(storage_path('/app').'/'.($real_path));
            return response(Storage::disk("spaces")->get($real_path));
        }
        return response()->json(["error" => "File Not Found!"], 404);
    }

    function indexWithUnpaginatedRecords(Request $request)
    {
        $limit = $request->limit ?? 30;
        $order = $request->order ?? "DESC";
        $search = $request->search ? rawurldecode($request->search) : "";
        $like = env("DB_CONNECTION") == "pgsql" ? "ILIKE" : "LIKE";
        $max_fetch_date = $request->max_fetch_date
            ? new DateTime($request->max_fetch_date)
            : Carbon::now();
        $max_date_paginated = $request->max_date_paginated
            ? new DateTime($request->max_date_paginated)
            : Carbon::now();

        //untracked records in pagination
        $unpaginated_results = TicketResource::collection(
            Ticket::with("payment")
                ->where(function ($query) use ($like, $search) {
                    $query
                        ->where("id", $like, "%" . $search . "%")
                        ->orWhere("ticket_number", $like, "%" . $search . "%");
                })
                ->where(
                    "datetime_of_apprehension",
                    ">",
                    $max_fetch_date->format("Y-m-d H:i:s")
                )
                ->orderBy("datetime_of_apprehension", "DESC")
                ->get()
        );

        //return tracked records in pagination
        return (new TicketCollection(
            Ticket::with("payment")
                ->where(function ($query) use ($like, $search) {
                    $query
                        ->where("id", $like, "%" . $search . "%")
                        ->orWhere("ticket_number", $like, "%" . $search . "%");
                })
                ->where(
                    "datetime_of_apprehension",
                    "<=",
                    $max_date_paginated->format("Y-m-d H:i:s")
                )
                ->orderBy("datetime_of_apprehension", $order)
                ->paginate($limit)
        ))->additional([
            "meta" => [
                "new_records" => $unpaginated_results,
                "max_date_paginated" => $max_date_paginated->format(
                    "Y-m-d H:i:s"
                ),
            ],
        ]);
    }
}
