<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketExtraProperty extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'ticket_id',
        'extra_property_id',
        'property_value',
    ];

    /**
     * Get the Ticket that owns the TicketExtraProperty
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the Extra Property that owns the TicketExtraProperty
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function propertyDescription()
    {
        return $this->belongsTo(ExtraProperty::class, 'extra_property_id')->withTrashed();
    }



}
