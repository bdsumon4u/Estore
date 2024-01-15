<?php

namespace App\Filament\Resources\Support\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

trait BelongsToOwner
{
    public static function scopeEloquentQueryToTenant(Builder $query, ?Model $tenant): Builder
    {
        return $query->where('owner_id', $tenant->owner_id);
    }

    public static function getTenantRelationship(Model $tenant): Relation
    {
        $relationshipName = static::getTenantRelationshipName();

        $tenant->owner::resolveRelationUsing($relationshipName, function (Model $model): Relation {
            return $model->hasMany(static::getModel(), 'owner_id');
        });

        return $tenant->owner->{$relationshipName}();
    }
}
