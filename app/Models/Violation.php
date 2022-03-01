<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Violation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'violation',
        'violation_code'
    ];

    protected $hidden = ['pivot'];
    /**
     * The tickets that belong to the Violation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tickets()
    {
        return $this->belongsToMany(Ticket::class);
    }


    /**
     * Get all of the violators for the Violation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function violators()
    {
        return $this->hasManyThrough(Violator::class, Ticket::class);
    }


    /**
     * Get the type that owns the Violation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function violation_types()
    {
        return $this->belongsToMany(ViolationType::class, AssignTypes::class)->withPivot(['created_at','updated_at','deleted_at'])->withTrashed();
        
    }
}
