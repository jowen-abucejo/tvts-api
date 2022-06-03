<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityLogCollection;
use App\Models\ActivityLog;
use App\Http\Resources\ActivityLogResource;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // $limit = $request->limit ?? 30;
        // $order = $request->order ?? "DESC";
        // $search = $request->search ? rawurldecode($request->search) : "";

        // $like = env("DB_CONNECTION") == "pgsql" ? "ILIKE" : "LIKE";
        // return ActivityLogResource::collection(
        //     ActivityLog::with([
        //         "user" => function ($query) {
        //             $query->select("id", "name");
        //         },
        //     ])
        //         ->where("activity", $like, "%" . $search . "%")
        //         ->orWhereHas("user", function ($query) use ($like, $search) {
        //             $query->where("name", $like, "%" . $search . "%");
        //         })
        //         ->orderBy("time_log", $order)
        //         ->paginate($limit)
        // );

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
     * @param   $activityLog

     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $activityLog)
    {
        $agent = new Agent();
        $from_device =
            $agent->device() != null
                ? " from " . $agent->device() . " " . $agent->platform()
                : "";
        $activity = (object) $activityLog;
        $request
            ->user()
            ->activityLogs()
            ->create([
                "time_log" => $activity->time_log,
                "activity" => $activity->activity . $from_device,
            ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ActivityLog  $activityLog
     * @return \Illuminate\Http\Response
     */
    public function show(ActivityLog $activityLog)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ActivityLog  $activityLog
     * @return \Illuminate\Http\Response
     */
    public function edit(ActivityLog $activityLog)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ActivityLog  $activityLog
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ActivityLog $activityLog)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ActivityLog  $activityLog
     * @return \Illuminate\Http\Response
     */
    public function destroy(ActivityLog $activityLog)
    {
        //
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
        $unpaginated_results = ActivityLogResource::collection(
            ActivityLog::with([
                "user" => function ($query) {
                    $query->select("id", "name");
                },
            ])
                ->where(function ($query) use ($like, $search) {
                    $query
                        ->where("activity", $like, "%" . $search . "%")
                        ->orWhereHas("user", function ($query) use (
                            $like,
                            $search
                        ) {
                            $query->where("name", $like, "%" . $search . "%");
                        });
                })
                ->where("time_log", ">", $max_fetch_date->format("Y-m-d H:i:s"))
                ->orderBy("time_log", "DESC")
                ->get()
        );

        //return tracked records in pagination
        return (new ActivityLogCollection(
            ActivityLog::with([
                "user" => function ($query) {
                    $query->select("id", "name");
                },
            ])
                ->where(function ($query) use ($like, $search) {
                    $query
                        ->where("activity", $like, "%" . $search . "%")
                        ->orWhereHas("user", function ($query) use (
                            $like,
                            $search
                        ) {
                            $query->where("name", $like, "%" . $search . "%");
                        });
                })
                ->where(
                    "time_log",
                    "<=",
                    $max_date_paginated->format("Y-m-d H:i:s")
                )
                ->orderBy("time_log", $order)
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
