<?php

namespace App\Filament\Resources\Support;

class Related
{
    public static function hasMany(string $owner, array|string $relations, ?string $key = null): array
    {
        $relations = is_array($relations) ? $relations : [$relations];

        foreach ($relations as $relation) {
            $related = static::getRelatedModel($owner, $relation);

            $owner::resolveRelationUsing($relation::getRelationshipName(), fn ($model) => $model->hasMany($related, $key ?? $model->getForeignKey()));

            $inverseRelationshipName = str(class_basename($owner))->singular()->camel()->toString();

            $related::resolveRelationUsing($inverseRelationshipName, fn ($model) => $model->belongsTo($owner, $key ?? app($owner)->getForeignKey()));
        }

        return $relations;
    }

    public static function belongsToMany(string $owner, array|string $relations): array
    {
        $relations = is_array($relations) ? $relations : [$relations];

        foreach ($relations as $relation) {
            $related = static::getRelatedModel($owner, $relation);

            $owner::resolveRelationUsing($relation::getRelationshipName(), fn ($model) => $model->belongsToMany($related));

            $inverseRelationshipName = str(class_basename($owner))->plural()->camel()->toString();

            $related::resolveRelationUsing($inverseRelationshipName, fn ($model) => $model->belongsToMany($owner));
        }

        return $relations;
    }

    private static function getRelatedModel(string $owner, string $relation): string
    {
        $relatedModelName = str($relation::getRelationshipName())->singular()->studly();

        return str($owner)->replace(class_basename($owner), $relatedModelName);
    }
}
