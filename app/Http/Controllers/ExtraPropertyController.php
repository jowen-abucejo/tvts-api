<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExtraPropertyResource;
use App\Models\ExtraProperty;
use Illuminate\Http\Request;

class ExtraPropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param string|null $property_owner
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $property_owner = null)
    {
        $search = ($request->search)? rawurldecode($request->search) : '';
        $like = (env('DB_CONNECTION') == 'pgsql') ? 'ILIKE' : 'LIKE';

        if($property_owner){
            return ExtraPropertyResource::collection(ExtraProperty::where('property_owner', '=', $property_owner)->orderBy('order_in_form', 'ASC')->get());
        }

        if($property_owner && auth()->user() && auth()->user()->isAdmin()){
            return ExtraPropertyResource::collection(ExtraProperty::withTrashed()->withCount('violatorExtraProperties', 'ticketExtraProperties')->where('property_owner', '=', $property_owner
                )->where('text_label', $like, '%'.$search.'%')->orderBy('order_in_form', 'ASC')->get());
        }

        if(auth()->user() && auth()->user()->isAdmin()){
            return ExtraPropertyResource::collection(ExtraProperty::withTrashed()->withCount('violatorExtraProperties', 'ticketExtraProperties')->where('text_label', $like, '%'.$search.'%')->orderBy('deleted_at', 'ASC')->orderBy('order_in_form', 'ASC')->get());
        }

        return response(null, 404);
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
        $extra_property = ExtraProperty::create([
            "property_owner"  => $request->property_owner,
            "text_label" => $request->text_label,
            "data_type" => $request->data_type,
            'is_multiple_select' => boolval($request->is_multiple_select),
            'options' =>  $request->options ? implode(';', $request->options) : '',
            "is_required" => boolval($request->is_required),
            "order_in_form" => intval($request->order_in_form)
        ]);

        $extra_property->property = "ext_property_".$extra_property->id;
        $extra_property->save();

        return new ExtraPropertyResource($extra_property);
    }

    /**
     * Display the specified resource.
     *
     * @param  number  $extra_property_id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $extra_property_id)
    {
        if(!$extra_property_id) return response()->json(["error" => "No Match Found!", "message" => "No match found for the record specified."], 400);

        $extra_property = ExtraProperty::withTrashed()->withCount('violatorExtraProperties', 'ticketExtraProperties')->find(intval($extra_property_id));

        if(!$extra_property) return response()->json(["error" => "No Match Found!", "message" => "No match found for the record specified."], 400);

        return new ExtraPropertyResource($extra_property);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ExtraProperty  $extraProperty
     * @return \Illuminate\Http\Response
     */
    public function edit(ExtraProperty $extraProperty)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  number  $extra_property_id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $extra_property_id = null)
    {
        if(!$extra_property_id) return response()->json(["error" => "No Match Found!", "message" => "No match found for the record specified."], 400);

        $extra_property = ExtraProperty::withTrashed()->withCount('violatorExtraProperties', 'ticketExtraProperties')->find($extra_property_id);

        if(!$extra_property) return response()->json(["error" => "No Match Found!", "message" => "No match found for the record specified."], 400);

        $total_associated_record = $extra_property->violator_extra_properties_count + $extra_property->ticket_extra_properties_count;

        $extra_property->text_label = $request->text_label;
        $extra_property->order_in_form = intval($request->order_in_form);
        $extra_property->is_required = boolval($request->is_required);

        if($total_associated_record === 0) {
            $extra_property->property_owner = $request->property_owner;
            $extra_property->data_type = $request->data_type;
            $extra_property->is_multiple_select = boolval($request->is_multiple_select);
            $extra_property->options = $request->options ? implode(';', $request->options) : '';        
        }

        $extra_property->save();
        return response()->json(["update_success" => true]);
    }

    /**
     * Remove the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  number  $extra_property_id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $extra_property_id = null)
    {
        if(!$extra_property_id) return response()->json(["error" => "No Match Found!", "message" => "No match found for the record specified."], 400);

        $extra_property = ExtraProperty::withTrashed()->withCount('violatorExtraProperties', 'ticketExtraProperties')->find($extra_property_id);

        if(!$extra_property) return response()->json(["error" => "No Match Found!", "message" => "No match found for the record specified."], 400);

        if(boolval($request->permanentDelete) === false) {            
            if($extra_property->trashed()) {
                $extra_property->restore();
                $extra_property->save();
                return response()->json(['update_status' => true]);
            }

            $extra_property->delete();
            return response()->json(['update_status' => $extra_property->trashed()]);
        }

        $total_associated_record = $extra_property->violator_extra_properties_count + $extra_property->ticket_extra_properties_count;
        if($total_associated_record > 0) {
            return response()->json(["error" => "Unable to Delete", "message" => "You can set it as 'NOT ACTIVE' instead."], 400);
        }
       
        $extra_property->forceDelete();
        return response()->json(['deleted' => true]);
    }
}
