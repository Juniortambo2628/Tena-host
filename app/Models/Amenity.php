<?php

namespace App\Models;

use App\Traits\BelongsToHost;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Amenity extends Model
{
    use BelongsToHost, HasFactory;

    protected $fillable = [
        'property_id',
        'name',
        'description',
        'price',
        'image_path',
        'is_active',
    ];

    /**
     * Get the property that offers the amenity.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
