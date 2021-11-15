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
            "violator" => $this->violator->name,
            "plate_number" => $this->plate_number,
            "vehicle_owner" => $this->vehicle_owner,
            "apprehension_date" => $this->datetime_of_apprehension,
            "apprehension_place" => $this->place_of_apprehension,
            "issued_by" => $this->issuedBy->name,
            "violations" => ViolationResource::collection($this->violations),
        ];
    }
}
