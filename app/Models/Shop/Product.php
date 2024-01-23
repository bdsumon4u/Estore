<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    // public function attributes(): BelongsToMany
    // {
    //     return $this->belongsToMany(Attribute::class, 'variations')
    //         ->using(Variation::class)
    //         ->withPivot(['id', 'option_id', 'option_value']);
    // }

    public function attributes(): HasMany
    {
        return $this->hasMany(Variation::class);
    }
}
