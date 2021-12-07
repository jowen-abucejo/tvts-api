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
            'name'=>explode(',', $this->name),
            'address'=>$this->address,
            'birth_date' =>$this->birth_date,
            'license_number' =>$this->license_number,
            'mobile_number' =>$this->mobile_number,
            'parent_and_license' =>$this->parent_and_license,
            'number_of_offenses' => count($this->tickets)
        ];    
    }
}
