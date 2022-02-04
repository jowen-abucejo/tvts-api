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
        'last_name',
        'first_name',
        'middle_name',
        'birth_date',
        'license_number',
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
     * Get all of the extra properties for the Violator
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function extraProperties()
    {
        return $this->hasMany(ViolatorExtraProperty::class);
    }
}
