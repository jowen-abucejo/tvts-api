<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssignTypes extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'violation_id',
        'violation_type_id',
    ];

     /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the violation that owns the AssignTypes
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function violation()
    {
        return $this->belongsTo(Violation::class, 'violation_id')->withTrashed();
    }

    /**
     * Get the violation_type that owns the AssignTypes
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function violation_type()
    {
        return $this->belongsTo(ViolationType::class, 'violation_type_id')->withTrashed();
    }

    /**
     * Get all of the tickets for the AssignTypes
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function tickets()
    {
        return $this->hasManyThrough(Ticket::class, Violation::class);
    }


}
