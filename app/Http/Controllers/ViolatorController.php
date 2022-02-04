<?php

namespace App\Http\Controllers;

use App\Http\Resources\ViolatorResource;
use App\Models\Ticket;
use App\Models\Violator;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ViolatorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $pluck_id=false)
    {
        $limit = ($request->limit)?? 30;
        $order = ($request->order)?? 'ASC';
        $search = ($request->search)?? '';
        if($pluck_id){
           return Violator::where('last_name', 'LIKE', '%' .$search.'%')->orWhere('first_name', 'LIKE', '%' .$search.'%')->orWhere('middle', 'LIKE', '%' .$search.'%')->pluck('id')->toArray();
        }
        return ViolatorResource::collection(Violator::withCount('tickets')->get());
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
    public function store(Request $request, $violator_id = null)
    {
        $violator = null;
        $l = Str::title(preg_replace('!\s+!',' ', $request->last_name));
        $f = Str::title(preg_replace('!\s+!',' ', $request->first_name));
        $m = ($request->middle_name)? Str::title(preg_replace('!\s+!',' ', $request->middle_name)) : '';
        $birth_date = new DateTime($request->birth_date);
        if($violator_id && intval($violator_id)){
            $violator = Violator::withCount('tickets')->find($violator_id);
            $violator->update([
                'license_number' => $request->license_number,
                'last_name' => $l,
                'first_name' => $f,
                'middle_name' => $m,
                'birth_date' => $birth_date->format('Y-m-d')
            ]);
            $violator->save();
        } else if(!$request->license_number){
            // $violator = Violator::withCount('tickets')->where('last_name', $l)->where('first_name', $f)->where('middle_name', $m)->where('birth_date', $birth_date)->first();
            $violator = Violator::withCount('tickets')->updateOrCreate(
                [
                    'last_name' => $l,
                    'first_name' => $f,
                    'middle_name' => $m,
                    'birth_date' => $birth_date->format('Y-m-d'),
                ],
                [
                    'license_number' => $request->license_number
                ]
            );
        } else if($request->license_number) {
            $violator = Violator::withCount('tickets')->updateOrCreate(
                [
                    'license_number' => $request->license_number
                ],
                [
                    'last_name' => $l,
                    'first_name' => $f,
                    'middle_name' => $m,
                    'birth_date' => $birth_date->format('Y-m-d'),
                ]
            );
        }
        $violator_extra_properties = app('\App\Http\Controllers\ExtraPropertyController')->index($request, 'violator');

        if($violator){
            foreach ($violator_extra_properties as $ext) {
                if($ext->data_type == 'image'){
                    $filepath = ($request->hasFile($ext->property))?$request->file($ext->property)->store($ext->property+$ext->id+'') : null;
                    $violator->extraProperties()->updateOrCreate(
                        [
                            'extra_property_id' => $ext->id,
                        ],
                        [
                            'property_value' => $filepath,
                        ]
                    );
                } else {
                    $violator->extraProperties()->updateOrCreate(
                        [
                            'extra_property_id' => $ext->id,
                        ],
                        [
                            'property_value' => $request->input($ext->property),
                        ]
                    );
                }
            }
        }

        return $violator;
        // return new ViolatorResource($violator);

    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $violator_id = null)
    {
        $violator = null;
        if($violator_id){
            $violator = Violator::withCount('tickets')->find($violator_id);
        } else if (!$request->license_number && ($request->first_name && $request->last_name && $request->birth_date)){
            $l = Str::title(preg_replace('!\s+!',' ', $request->last_name));
            $f = Str::title(preg_replace('!\s+!',' ', $request->first_name));
            $m = ($request->middle_name)? Str::title(preg_replace('!\s+!',' ', $request->middle_name)) : '';
            $birth_date = new DateTime($request->birth_date);
            // $violator = Violator::withCount('tickets')->where('last_name', $l)->where('first_name', $f)->where('middle_name', $m)->where('birth_date', $birth_date->format('Y-m-d'))->first();
            $violator = Violator::withCount('tickets')->where([['last_name', $l],['first_name', $f],['middle_name', $m],['birth_date', $birth_date->format('Y-m-d')]])->first();
        } else if ($request->license_number){
            $violator = Violator::withCount('tickets')->where('license_number', $request->license_number)->first();
        } else {}
        if($violator)
            return new ViolatorResource($violator);
        return response(null, );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Violator  $violator
     * @return \Illuminate\Http\Response
     */
    public function edit(Violator $violator)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Violator  $violator
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Violator $violator)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Violator  $violator
     * @return \Illuminate\Http\Response
     */
    public function destroy(Violator $violator)
    {
        //
    }

    public function groupByAndCount(Request $request)
    {
        $data = (object)['all_violator_ticket_count'=>[], 'violator_ticket_count_within_date'=>[]];
        $all_ids = Ticket::select('id')->get();
        $within_date_ids = $request->ticket_ids?? [1];

        $grouped = Violator::select('id' )->withCount('tickets')->whereHas('tickets', function($query) use($within_date_ids) {
            return $query->whereIn('id', $within_date_ids);
        })->orderBy('tickets_count', 'ASC')->get();

        $data->violator_ticket_count_within_date = $grouped->mapToGroups(function ($item, $key) {
            return ["offense_".$item['tickets_count'] => $item['id']];
        });

        $grouped = Violator::select('id' )->withCount('tickets')->whereHas('tickets', function($query) use($all_ids) {
            return $query->whereIn('id', $all_ids);
        })->orderBy('tickets_count', 'ASC')->get();

        $data->all_violator_ticket_count = $grouped->mapToGroups(function ($item, $key) {
            return ["offense_".$item['tickets_count'] => $item['id']];
        });
        return $data;
    }
}
