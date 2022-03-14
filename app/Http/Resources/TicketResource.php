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
            "offense_number" => $this->offense_number,
            "vehicle_type" => $this->vehicle_type,
            "apprehension_datetime" => $this->datetime_of_apprehension,
            "officer_user_id" => $this->issuedBy->id,
            "issued_by" => $this->issuedBy->name,
            "violations" => ViolationResource::collection($this->violations),
            "payment" => $this->when((auth()->user()->isAdmin() && $this->payment_id && $this->whenLoaded('payment', true, false)), new PaymentResource($this->payment), ['or_number'=>'NA', 'total_amount' => 'NA', 'penalties' => []]),
            "status_text" => ($this->payment_id && $this->payment_id !== 0)? 'SETTLED' : 'NOT SETTLED',
            "extra_properties" => TicketExtraPropertyResource::collection($this->extraProperties)
        ];
    }
}
