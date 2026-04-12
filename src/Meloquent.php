<?php
namespace SajedZarinpour\Meloquent;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

use ReflectionMethod;
use Exception;
use ReflectionObject;
use SajedZarinpour\Meloquent\Attributes\MarkedAs;
use SajedZarinpour\Meloquent\DTOs\RelationFieldInfoDto;
use SajedZarinpour\Meloquent\Attributes\MarkedAsBelongsTo;
use SajedZarinpour\Meloquent\Attributes\MarkedAsBelongsToMany;
use SajedZarinpour\Meloquent\Attributes\MarkedAsHasMany;
use SajedZarinpour\Meloquent\Attributes\MarkedAsHasManyThrough;
use SajedZarinpour\Meloquent\Attributes\MarkedAsHasOne;
use SajedZarinpour\Meloquent\Attributes\MarkedAsHasOneOrMany;
use SajedZarinpour\Meloquent\Attributes\MarkedAsHasOneOrManyThrough;
use SajedZarinpour\Meloquent\Attributes\MarkedAsHasOneThrough;
use SajedZarinpour\Meloquent\Attributes\MarkedAsMorphMany;
use SajedZarinpour\Meloquent\Attributes\MarkedAsMorphOne;
use SajedZarinpour\Meloquent\Attributes\MarkedAsMorphOneOrMany;
use SajedZarinpour\Meloquent\Attributes\MarkedAsMorphTo;
use SajedZarinpour\Meloquent\Attributes\MarkedAsMorphToMany;

/**
 * wrote with a decent amount of heavy metal
 */
class Meloquent {

    public protected(set) int $maxDepth;
    public protected(set) array $sortNonPersistantRelationsMarkedByAttributesPriorityTable;

    public function __construct(public ?array $priorityTable)
    {
      $this->maxDepth = 6;//config('meloquent.depth', 6);
      
      if (empty($priorityTable)) {
        $this->priorityTable = [
            HasOne::class,
            HasMany::class,
            HasOneThrough::class,
            HasManyThrough::class,
            BelongsTo::class,
            BelongsToMany::class,
            MorphMany::class,
            MorphToMany::class,
        ];
      }
    }

    /**
     * Public API: returns array or null
     */
    public function getKeyNameForRelation($originModel, string $field): RelationFieldInfoDto
    {
        return RelationFieldInfoDto::fromArray($this->findFieldOwnerClass($originModel, $field));
    }

    /**
     * helper function to get a foreign key based on a provided field which is not neccessarily is in the model
     */
    public function getForeignKeyForModelBasedOnField(string $model, string $field) : string|null
    {
        if (!class_exists($model)) {
            throw new Exception("class not found");
        }

        $instance = new $model;

        $reflectionDto = $this->getKeyNameForRelation($instance, $field);

        $foreignKey = null;
        if (!empty($reflectionDto->foreignKeyOnOrigin)) {
            $foreignKey = $reflectionDto->foreignKeyOnOrigin;
        } else {
            $foreignKey = $this->resolveForeignOrPrimary($instance, $field);
        } 

        return $foreignKey;
    }

    /**
     * gets all relations on the model, not just the eager loaded ones
     * give me vanilla PHP babe!
     */
    public function getAllRelations(Model $model, $type = null) : array
    {
        $relationsList = $this->initAllRelations($model);
        return $type ? ($relationsList[$type] ?? []) : $relationsList;
    }

    /**
     * gets the value of the field on the model similar to data_get,
     * but acts on object and all it's relations.
     * 
     * @param Model $model
     * @param string $field
     */
    public function getFieldValue(Model $model, string $field)
    {
        $visited = [];

        return $this->resolveFieldValue(
            $model,
            $field,
            $visited,
            0
        );
    }

    /**
     * searches for the owner class of the field, if the field could be found in the model,
     * return it, otherwise goes through its relations intill it finds a match
     */
    private function findFieldOwnerClass($model, string $field) 
    {
        // check main model table
        if (Schema::hasColumn($model->getTable(), $field)) {
            return [
                'owner_model_class' => get_class($model),
                'owner_primary_key' => app(get_class($model))->getKeyName(),
                'foreign_key_on_origin' => null,
                'relation_path' => [],
            ];
        }

        $visited = [];
        return $this->recurseRelations($model, $field, $visited, 0);
    }

    /**
     * recursively goes though layers of relations for a given model until it finds the field or reach a certain depth
     */
    private function recurseRelations($originModel, string $field, array &$visited, int $depth)
    {
        if ($depth >= $this->maxDepth) {
            return null;
        }

        $originClass = get_class($originModel);

        // the devil is in the detail! 
        // so, when a field is provided from both belongs to and belongs to many for example
        // which one do we choose?! 
        // by the way, the bug in this section was resulted from runtime randomely indexing the relations :) 
        // don't ask me how I know :)))

        $methods = $this->getSortedRelations($originModel);

        foreach ($methods as $method) {
            // track visited to avoid loops: combination of origin class + method
            $visitKey = $originClass . '::' . $method;
            if (isset($visited[$visitKey])) {
                continue;
            }
            $visited[$visitKey] = true;

            try {
                // since I used the new function which strictly type checks the relations, this call is safe
                $relation = $originModel->{$method}();
                if (! $relation instanceof Relation) continue; // this is not even needed, but anyway let it be here for good measure!
                $related = $relation->getRelated();
                $relatedClass = get_class($related);

                // If the field exists on the related model table, we've found the owner
                if (Schema::hasColumn($related->getTable(), $field)) {
                    $ownerPk = app($relatedClass)->getKeyName();
                    $foreignKeyOnOrigin = $this->determineForeignKeyOnOrigin($relation, $originModel, $related);

                    return [
                        'owner_model_class' => $relatedClass,
                        'owner_primary_key' => $ownerPk,
                        'foreign_key_on_origin' => $foreignKeyOnOrigin,
                        'relation_path' => [$method],
                    ];
                }

                // Recurse into the related model's relations (nested)
                $subResult = $this->recurseRelations($related, $field, $visited, $depth + 1);
                if ($subResult) {
                    // prepend current relation method to path and, if appropriate, compute foreign key from origin -> first relation
                    array_unshift($subResult['relation_path'], $method);

                    // Only compute foreign key on origin if the owner is not the same as origin.
                    if ($depth === 0 && $subResult['foreign_key_on_origin'] === null) {
                        // try to compute foreign key from the immediate relation if that relation points (directly or indirectly) to the owner
                        $fk = $this->determineForeignKeyOnOrigin($relation, $originModel, app($subResult['owner_model_class']));
                        $subResult['foreign_key_on_origin'] = $fk;
                    }

                    return $subResult;
                }

                // Special handling for MorphTo: the related model type is dynamic and may be the origin's morph type
                if (get_class($relation) === \Illuminate\Database\Eloquent\Relations\MorphTo::class) {
                    // MorphTo is resolved by model state; try possible morph map entries + related polymorphic types
                    $morphType = $relation->getMorphType(); // e.g., commentable_type
                    $morphId   = $relation->getForeignKeyName(); // e.g., commentable_id

                    // Try morph map if set, otherwise try all possible related classes by scanning known relation methods on origin
                    $map = Relation::morphMap() ?: [];
                    $candidates = !empty($map) ? array_values($map) : [$relatedClass];

                    foreach ($candidates as $candidateClass) {
                        // instantiate candidate to check its table for the field
                        try {
                            $candidate = app($candidateClass);
                            if (Schema::hasColumn($candidate->getTable(), $field)) {
                                $ownerPk = app($candidateClass)->getKeyName();
                                // For morph, the foreign key lives on origin as morph id (e.g., commentable_id)
                                return [
                                    'owner_model_class' => $candidateClass,
                                    'owner_primary_key' => $ownerPk,
                                    'foreign_key_on_origin' => $morphId,
                                    'relation_path' => [$method],
                                ];
                            }
                        } catch (\Throwable $e) {
                            // skip bad candidate
                        }
                    }
                }

            } catch (\Throwable $e) {
                // continue on errors
                continue;
            }
        }

        return null;
    }

    /**
     * Determine the foreign key field name that lives on the origin model for a given relation.
     * Returns the foreign key column name on the origin model (e.g. 'b_id') when applicable, or null.
     */
    public function determineForeignKeyOnOrigin(Relation $relation, $originModel, $relatedModel)
    {
        $relationClass = get_class($relation);

        // BelongsTo: foreign key is on origin (e.g., a.b_id)
        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
            
            if (method_exists($relation, 'getForeignKeyName')) {
                return $relation->getForeignKeyName();
            }

        }

        // MorphTo: the id part (e.g., commentable_id) is on origin
        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\MorphTo) {
            
            if (method_exists($relation, 'getForeignKeyName')) {
                return $relation->getForeignKeyName();
            }

            if (method_exists($relation, 'getMorphType')) {
                // morphological id is getForeignKeyName(); morph type is getMorphType()
                return $relation->getForeignKeyName();
            }
        }

        // BelongsToMany: pivot table holds both keys; origin may not have direct foreign key
        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
            // The origin model's key on the pivot can be obtained via ->getForeignPivotKeyName() or ->getQualifiedForeignPivotKeyName()
            if (method_exists($relation, 'getForeignPivotKeyName')) {
                return $relation->getForeignPivotKeyName(); // column name on pivot referencing origin
            }
            return null;
        }

        // HasOne / HasMany: foreign key is on related model (not on origin)
        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasOne
            || $relation instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
            // The key on the related model which references origin:
            if (method_exists($relation, 'getForeignKeyName')) {
                // this returns column name on related model (not on origin)
                return null;
            }
        }

        // HasManyThrough: through model has FK to final related; origin does not have direct FK
        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasManyThrough) {
            return null;
        }

        $stripTablePrefix = static function($qualified) {
            if (!is_string($qualified)) return $qualified;
            if (strpos($qualified, '.') !== false) {
                return substr($qualified, strpos($qualified, '.') + 1);
            }
            return $qualified;
        };

        // Fallback attempts:
        foreach (['getForeignKeyName','foreignKey','getQualifiedForeignKeyName'] as $m) {
            if (method_exists($relation, $m)) {
                try {
                    $val = $relation->{$m}();
                    if (is_string($val)) {
                        // If returned contains a table-qualified name, strip table prefix and see if that column exists on origin
                        $col = $stripTablePrefix($val);
                        if (Schema::hasColumn($originModel->getTable(), $col)) {
                            return $col;
                        }
                        // If it's on related model, we don't expose it as origin foreign key
                        if (Schema::hasColumn($relatedModel->getTable(), $col)) {
                            return null;
                        }
                    }
                } catch (\Throwable $e) {
                    // continue
                    Log::error("determining the fk failed: {$e->getMessage()}.");
                }
            }
        }

        return null;
    }

    /**
     * if the field is in this model, return pk, else, find where it is and return the proper fk to there
     * from this model
     */
    public function resolveForeignOrPrimary(Model $model, string $field): ?string
    {
        // normalize
        $field = (string) $field;

        // If the field is exactly the model primary key, return it
        $pk = $model->getKeyName();
        if ($field === $pk) {
            return $pk;
        }

        // if this was a fk in the table
        $dto = $this->getKeyNameForRelation($model, $field);
        if(!empty($dto->foreignKeyOnOrigin))
        {
            return $dto->foreignKeyOnOrigin;
        }

        // do not trust $model->getAttributes(), if the function is called on an empty model
        // which I know it would, it does not give you a damn column name
        // well I learned not to trust AI the hard way! salut StackOverflow
        $attributes = Schema::getColumnListing($model->getTable());

        if (in_array($field, $attributes)) {
            return $pk;
        }

        // throw
        Log::error("column is not found on either the model or its relations");
        return null;
    }

    /**
     * tries to find the field value in the model, if not found, visits all it's relations till
     * it find a hit or null
     * 
     * @param Model $model
     * @param string $field
     * @param array &$visited
     * @param int $depth
     */
    public function resolveFieldValue(
        Model $model,
        string $field,
        array &$visited,
        int $depth
    ) 
    {
        if ($depth >= $this->maxDepth) {
            return null;
        }

        $class = get_class($model);

        // Prevent loops
        $key = $class . ':' . $model->getKey();
        if (isset($visited[$key])) {
            return null;
        }

        $visited[$key] = true;

        // Check local field
        if ($model->offsetExists($field)) {
            return $model->getAttribute($field);
        }

        // Traverse relations
        $methods = $this->getSortedRelations($model);

        foreach ($methods as $method) {

            try {
                $relation = $model->{$method}();

                if (! $relation instanceof Relation) {
                    // was continue;
                    $results = $relation;
                } else {
                    $results = $model->{$method};
                }

                if (! $results) {
                    continue;
                }

                // Collection relations (hasMany, many-to-many)
                if ($results instanceof \Illuminate\Support\Collection) {

                    $values = [];

                    foreach ($results as $related) {

                        $val = $this->resolveFieldValue(
                            $related,
                            $field,
                            $visited,
                            $depth + 1
                        );

                        if ($val !== null) {
                            $values[] = $val;
                        }
                    }

                    if (! empty($values)) {
                        return $values;
                    }
                }

                // Single model relations
                if ($results instanceof Model) {

                    $val = $this->resolveFieldValue(
                        $results,
                        $field,
                        $visited,
                        $depth + 1
                    );

                    if ($val !== null) {
                        return $val;
                    }
                }

            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    /** loads all the relations declared in an elloquent model, returns as an associative array grouped by relation type
     * 
     * I want to credit a real human here, based on:
     * Source - https://stackoverflow.com/a/47286417
     * Posted by Eternal1
     * Retrieved 2026-02-24, License - CC BY-SA 3.0
     * 
     * @param Model $model
     * @return array
     */
    protected function initAllRelations(Model $model): array
    {
        $reflect = new \ReflectionClass(get_class($model));
        $relationsList = [];

        $relationClasses = $this->priorityTable;

        foreach($reflect->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            /** @var ReflectionMethod $method */
            if ($method->hasReturnType() && in_array((string)$method->getReturnType(), $relationClasses)) {
                $relationsList[(string)$method->getReturnType()][] = $method->getName();
            }
        }

        return $relationsList;
    }

    /**
     * controlls how the relations are managed,
     * if two relation could provide the required field, we toss the first based on the priority set here.
     */
    public function getSortedRelations($model): array
    {
        
        $relations = $this->getAllRelations($model);

        $keys = array_intersect_key(array_flip($this->priorityTable), $relations);

        $flatList = [];
        foreach ($keys as $key => $null) {
            $flatList = array_merge($flatList, $relations[$key]??[]);
        }

        // also virtual relations if any
        $virtualRelations = $this->getModelRelationAttributes($model);

        if (sizeof($virtualRelations)) {
            $this->sortNonPersistantRelationsMarkedByAttributes($virtualRelations);
            $flatList = array_merge($flatList, $this->extractMethods($virtualRelations));
        }

        return $flatList;
    }

    public function getModelRelationAttributes(Model $model): array
    {
        $reflection = new ReflectionObject($model);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $list = [];
        
        foreach ($methods as $method) {
            $attributes = $method->getAttributes();
            if (count($attributes)>0) {

                $attrs = array_filter($attributes, static function ($attribute) {
                    return match ($attribute->name) {
                        MarkedAsBelongsTo::class,
                        MarkedAsBelongsToMany::class,
                        MarkedAsHasMany::class,
                        MarkedAsHasManyThrough::class,
                        MarkedAsHasOne::class,
                        MarkedAsHasOneOrMany::class,
                        MarkedAsHasOneOrManyThrough::class,
                        MarkedAsHasOneThrough::class,
                        MarkedAsMorphMany::class,
                        MarkedAsMorphOne::class,
                        MarkedAsMorphOneOrMany::class,
                        MarkedAsMorphTo::class,
                        MarkedAsMorphToMany::class=>true,
                        default => false,
                    };
                });

                if (sizeof($attrs)>0) {
                    $list [] = [
                        'method' => $method,
                        'relations'=>$attrs
                    ];
                }
                
            }
        }

        return $list;
    }

    protected function extractMethods(array $list)
    {
        $extracted = [];
        foreach ($list as $item) {
            $extracted []= $item['method']->name;
        }
        return $extracted;
    }

    public function sortByPriority($a, $b)
    {
        return array_search(
                array_first($a['relations'])->name, 
                $this->sortNonPersistantRelationsMarkedByAttributesPriorityTable
            ) 
            <=> 
            array_search(
                array_first($b['relations'])->name, 
                $this->sortNonPersistantRelationsMarkedByAttributesPriorityTable
            );
    }

    public function sortNonPersistantRelationsMarkedByAttributes(array &$elemenets)
    {
        return uasort($elemenets, $this::class.'::sortByPriority');
    }

    public function getsortNonPersistantRelationsMarkedByAttributesPriorityTable()
    {
        $prefix = MarkedAs::class;
        $table = [];
        foreach ($this->priorityTable as $row) {
            $posix = array_last(explode('\\',$row));
            $table [] = $prefix.$posix;
        }
        $this->sortNonPersistantRelationsMarkedByAttributesPriorityTable = $table;
    }
}
