<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ViolatorExtraPropertyResource extends JsonResource
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
            'violator_id' => $this->violator_id,
            'propertyDescription' => new ExtraPropertyResource($this->propertyDescription),
            'property_value' => $this->property_value,
        ];
    }
}
