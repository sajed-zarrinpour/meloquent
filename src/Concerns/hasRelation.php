<?php

namespace SajedZarinpour\Meloquent\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use ReflectionObject;
use ReflectionProperty;

trait HasRelation {
    
    public function __get($property) 
    {
        $reflectionOfClass = new ReflectionObject($this);
        
        if ($reflectionOfClass->hasProperty($property)) {
            return $this->{$property};
        } else if ($reflectionOfClass->hasMethod('get_')) {
            return $this->get_($property);
        }
    }

    /**
     * gets the value of the field on the model similar to data_get,
     * but acts on object and all it's relations.
     * 
     * @param Model $model
     * @param string $field
     */
    public function get_(string $field)
    {
        return meloquent($this->getPriorityTable())
                ->getFieldValue(
                    model: $this,
                    field: $field,
                );
    }

    /**
     * returns the $priorityTable static protected property if it has been set in the class, otherwise the default.
     * you can control the priority of search in the relations by setting a protected static property **$priorityTable**.
     */
    public function getPriorityTable()
    {
        if (isset(static::$priorityTable)) {
            return (new ReflectionProperty(static::class, 'priorityTable'))->getValue() ;
        }
        return [
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

    /**
     * provides the foreign key through which you may access the field.
     * if you want to compare two models for equaivalency in a field in the third model, you may use this 
     * function to get the fk on both models and compare them instead. 
     * example:
     * suppose you have two models, rfq and so, which have relation with product, then:
     * rfq->product_name == so->product_name => rfq->getForeignKeyForModelBasedOnField('product_name) == so->getForeignKeyForModelBasedOnField('product_name')
     */
    public function getForeignKeyForModelBasedOnField(string $field) 
    {
        return meloquent($this->getPriorityTable())->getForeignKeyForModelBasedOnField(static::class, $field);
    }

    /**
     * returns a complete categorized list of relations of a model, regardless of wwhether the relation is eager loaded or not.
     */
    public function getCategorizedRelations($type = null) : array
    {
        return meloquent($this->getPriorityTable())->getAllRelations($this, $type);
    }

    /**
     * returns the relations of this class in the order they are going to get visit to find a field
     */
    public function getSortedRelations() : array
    {
        return meloquent($this->getPriorityTable())->getSortedRelations($this);
    }
}