<?php

namespace App\Filament\Resources\Support\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

trait BelongsToTenant
{
    public static function getTenantRelationship(Model $tenant): Relation
    {
        $relationshipName = static::getTenantRelationshipName();

        $tenant::resolveRelationUsing($relationshipName, function (Model $model) use ($tenant): Relation {
            return $model->hasMany(static::getModel(), $tenant->getForeignKey());
        });

        return $tenant->{$relationshipName}();
    }
}
