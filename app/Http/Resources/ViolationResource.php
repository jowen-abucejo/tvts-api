<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ViolationResource extends JsonResource
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
            "violation" => $this->violation,
            "violation_code" => $this->violation_code,
            "violation_types" => ViolationTypeResource::collection($this->violation_types),
            "deleted_at" => $this->deleted_at,
            "tickets_count" => $this->when((!auth()->user()->isEnforcer()),$this->tickets_count??$this->tickets->count()),
            "violation_types_count" => $this->when((!auth()->user()->isEnforcer()), $this->violation_types_count??$this->violation_types->count())
        ];
    }
}   
