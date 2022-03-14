<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExtraProperty extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
            "property",
            "property_owner",
            "text_label",
            "data_type",
            'is_multiple_select',
            'options',
            "is_required",
            "order_in_form"
    ];

    /**
     * Get all of the ticketExtraProperties for the ExtraProperty
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ticketExtraProperties()
    {
        return $this->hasMany(TicketExtraProperty::class, 'extra_property_id');
    }

    /**
     * Get all of the violatorExtraProperties for the ExtraProperty
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function violatorExtraProperties()
    {
        return $this->hasMany(ViolatorExtraProperty::class, 'extra_property_id');
    }


}
