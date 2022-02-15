<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'ticket_number',
        'violator_id',
        'offense_number',
        'vehicle_type',
        'datetime_of_apprehension',
        'issued_by',
        'payment_id',
    ];
    
    /**
     * The violations that belong to the Ticket
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function violations()
    {
        return $this->belongsToMany(Violation::class)->withTrashed();
    }

    
    /**
     * Get the violator that owns the Ticket
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function violator()
    {
        return $this->belongsTo(Violator::class);
    }


    /**
     * Get the user that issue the Ticket
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by')->withTrashed();
    }

    /**
     * Get the payment that owns the Ticket
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
    
    /**
     * Get all of the extra properties for the Ticket
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function extraProperties()
    {
        return $this->hasMany(TicketExtraProperty::class);
    }
}
