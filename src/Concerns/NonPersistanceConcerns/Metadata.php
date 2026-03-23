<?php
namespace SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns;

use SajedZarinpour\Meloquent\Contracts\AccessRepositoryContract;
use SajedZarinpour\Meloquent\Contracts\MutateDataRepositoryContract;

trait Metadata {

    private static $userDefinedDataRepositoryReflection;

    protected static function getUserDefinedDataRepositoryReflectionAttribute()
    {
        return self::$userDefinedDataRepositoryReflection ?? (function (){
            $class = (
                new \ReflectionProperty(static::class, 'repositioryClass')
            )->getValue();
    
            $userDefinedDataRepository = app($class);
            self::$userDefinedDataRepositoryReflection =  new \ReflectionClass($userDefinedDataRepository);
            return self::$userDefinedDataRepositoryReflection;
        })();
    }

    public static function getDataManipulationService()
    {
        return self::getUserDefinedDataRepositoryReflectionAttribute()->newInstance();
    }

    public static function accessesData()
    {
        return  in_array(
                    AccessRepositoryContract::class,
                    self::getUserDefinedDataRepositoryReflectionAttribute()->getInterfaceNames()
                )
                ? true : false;
    }

    public static function mutatesData()
    {
        return in_array(
                    MutateDataRepositoryContract::class,
                    self::getUserDefinedDataRepositoryReflectionAttribute()->getInterfaceNames()
                )
                ? true : false;
    }

    /**
     * Override to prevent database queries when broadcasting notifications
     */
    public function getMorphClass()
    {
        return static::class;
    }

    /**
     * Override to prevent Laravel from checking if model exists in database
     */
    public function wasRecentlyCreated()
    {
        return false;
    }

    /**
     * Override to prevent JSON serialization issues
     */
    public function toArray()
    {
        $attributes = $this->attributes;

        foreach ($this->casts as $key => $cast) {
            if (isset($attributes[$key])) {
                if ($cast === 'date' && !empty($attributes[$key])) {
                    $attributes[$key] = \Carbon\Carbon::parse($attributes[$key])->format('Y-m-d');
                } elseif ($cast === 'datetime' && !empty($attributes[$key])) {
                    $attributes[$key] = \Carbon\Carbon::parse($attributes[$key])->format('Y-m-d H:i:s');
                } elseif ($cast === 'boolean') {
                    $attributes[$key] = (bool)$attributes[$key];
                }
            }
        }

        return $attributes;
    }
}