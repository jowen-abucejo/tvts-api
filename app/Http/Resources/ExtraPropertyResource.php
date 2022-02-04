<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExtraPropertyResource extends JsonResource
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
            "property" => $this->property,
            "property_owner" => $this->when(auth()->user() && auth()->user()->isAdmin(), $this->property_owner),
            "text_label" => $this->text_label,
            "data_type" => $this->data_type,
            "is_multiple_select" => $this->is_multiple_select,
            "options" => explode(';', $this->options),
            "is_required" => $this->is_required,
            "active" => $this->active
        ];
    }
}
