<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ViolationTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "type" => $this->type,
            "vehicle_type" => $this->vehicle_type,
            "penalties" =>explode("," , $this->penalties),
            "deleted_at" => $this->deleted_at,
            "active" => $this->whenPivotLoaded('assign_types', function () { return $this->pivot->deleted_at? false: true;}, true),
            "violations_count" => $this->when((!auth()->user()->isEnforcer()), $this->violations_count?? $this->violations->count())
        ];
    }
}
