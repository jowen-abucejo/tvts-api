<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ViolatorResource extends JsonResource
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
            'last_name' => $this->last_name,
            'first_name'=> $this->first_name,
            'middle_name'=> $this->middle_name,
            'birth_date' => $this->birth_date,
            'license_number' => $this->license_number,
            'extra_properties' => ViolatorExtraPropertyResource::collection($this->extraProperties),
            'tickets_count' => $this->tickets_count
        ];    
    }
}
