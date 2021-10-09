<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Violator extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'address',
        'birth_date',
        'license_number',
        'parent_and_license',
    ];

    /**
     * Get all of the tickets for the Violator
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }


    /**
     * Get all of the violations for the Violator
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function violations()
    {
        return $this->hasManyThrough(Violation::class, Ticket::class);
    }


    /**
     * Get the violationType that owns the Violator
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function violationType()
    {
        return $this->belongsTo(ViolationType::class);
    }
}
