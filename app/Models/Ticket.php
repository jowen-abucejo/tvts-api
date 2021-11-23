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
        'vehicle_type',
        'plate_number',
        'vehicle_owner',
        'owner_address',
        'datetime_of_apprehension',
        'place_of_apprehension',
        'vehicle_is_impounded',
        'is_under_protest',
        'license_is_confiscated',
        'document_signature',
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
        return $this->belongsToMany(Violation::class);
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
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Get the payment associated with the Ticket
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
