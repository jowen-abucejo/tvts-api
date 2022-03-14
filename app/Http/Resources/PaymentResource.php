<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'id' => $this->id,
            'or_number' => $this->OR_number,
            'ticket' => new TicketResource($this->ticket),
            'ticket_number' => $this->whenLoaded('ticket', $this->ticket->ticket_number ),
            'date_of_payment' => $this->whenLoaded('ticket', $this->created_at ),
            'penalties' => explode(',', $this->penalties),
            'total_amount' => $this->total_amount,
        ];
    }
}
