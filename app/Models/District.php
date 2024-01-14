<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected static function booted()
    {
        static::deleting(function (District $district) {
            $district->thanas()->delete();
        });
    }

    public function thanas(): HasMany
    {
        return $this->hasMany(Thana::class);
    }
}
