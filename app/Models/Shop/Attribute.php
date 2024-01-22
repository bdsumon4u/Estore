<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use HasFactory;

    public function getTypeFormattedAttribute(): string
    {
        return static::typesFields()[$this->type];
    }

    public static function typesFields(): array
    {
        return [
            'text' => 'Text',
            'number' => 'Number',
            'checkbox' => 'Checkbox',
            'colorpicker' => 'Color picker',
            'datepicker' => 'Date picker',
        ];
    }

    public static function fieldsWithOptions(): array
    {
        return ['checkbox', 'colorpicker'];
    }

    public function hasMultipleOptions(): bool
    {
        return in_array($this->type, static::fieldsWithOptions());
    }

    public function hasTextOption(): bool
    {
        return ! $this->hasMultipleOptions();
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeFilterable(Builder $query): Builder
    {
        return $query->where('is_filterable', true);
    }

    public function scopeSearchable(Builder $query): Builder
    {
        return $query->where('is_searchable', true);
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }

    // public function variations(): BelongsToMany
    // {
    //     return $this->belongsToMany(Variation::class, 'variations');
    // }
}
