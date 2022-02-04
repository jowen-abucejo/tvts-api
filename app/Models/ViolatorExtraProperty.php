<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViolatorExtraProperty extends Model
{
    use HasFactory;

     /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'violator_id',
        'extra_property_id',
        'property_value',
    ];

    /**
     * Get the Violator that owns the ViolatorExtraProperty
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function violator()
    {
        return $this->belongsTo(Violator::class);
    }

    /**
     * Get the Extra Property that owns the ViolatorExtraProperty
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function propertyDescription()
    {
        return $this->belongsTo(ExtraProperty::class, 'extra_property_id');
    }
}
