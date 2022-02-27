<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ViolationType extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'type',
        'vehicle_type',
        'penalties',
    ];

    /**
     * Get all of the violations for the ViolationType
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function violations()
    {
        return $this->belongsToMany(Violation::class, AssignTypes::class)->withTrashed();
    }
}
