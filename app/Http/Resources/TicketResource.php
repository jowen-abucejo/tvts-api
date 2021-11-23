<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
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
            "number" => $this->ticket_number,
            "violator" => new ViolatorResource($this->violator),
            "vehicle_type" => $this->vehicle_type,
            "plate_number" => $this->plate_number,
            "vehicle_owner" => $this->vehicle_owner,
            'owner_address'=>$this->owner_address,
            "apprehension_datetime" => $this->datetime_of_apprehension,
            "apprehension_place" => $this->place_of_apprehension,
            'vehicle_is_impounded' => $this->vehicle_is_impounded,
            'is_under_protest' => $this->is_under_protest,
            'license_is_confiscated' => $this->license_is_confiscated,
            "issued_by" => $this->issuedBy->name,
            "violations" => ViolationResource::collection($this->violations),
            // "payment_id" => $this->when(Auth::user()->isAdmin()), $this->payment_id,

        ];
    }
}
