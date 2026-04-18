<?php

namespace SajedZarinpour\Meloquent\Facades;

use Illuminate\Support\Facades\Facade;
use SajedZarinpour\Meloquent\Meloquent as MeloquentClass;

/**
 * @method static RelationFieldInfoDto getKeyNameForRelation($originModel, string $field)
 * @method static string|null getForeignKeyForModelBasedOnField(string $model, string $field) 
 * @method static array getAllRelations(Model $model, $type = null)
 * @method static getFieldValue(Model $model, string $field)
 * @method static determineForeignKeyOnOrigin(Relation $relation, $originModel, $relatedModel)
 * @method static string|null resolveForeignOrPrimary(Model $model, string $field)
 * @method static resolveFieldValue(Model $model, string $field, array &$visited, int $depth)
 * @method static array getSortedRelations($model)
 */
class Meloquent extends Facade
{
    /** Declaring the accessor for this Facade. */
    protected static function getFacadeAccessor()
    {
      return MeloquentClass::class;
    }

    public static function swap($instance)
    {
      return parent::swap($instance);
    }
}
