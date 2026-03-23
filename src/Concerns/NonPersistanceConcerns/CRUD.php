<?php
namespace SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns;

use SajedZarinpour\Meloquent\Contracts\AccessRepositoryContract;
use SajedZarinpour\Meloquent\Contracts\MutateDataRepositoryContract;

trait CRUD {
    use Metadata;

    public static function create($attributes=[])
    {
        if (static::mutatesData()) {
            return static::getDataManipulationService()->create($attributes);
        } else {
            throw new \Exception(static::class . ' Does not implements '. MutateDataRepositoryContract::class);
        }
    }
    /**
     * Override save method to prevent database operations
     */
    public function save(array $options = [])
    {
        if ($this->mutatesData()) {
            return $this->getDataManipulationService()->update($options);
        } else {
            throw new \Exception(static::class . ' Does not implements '. MutateDataRepositoryContract::class);
        }
    }

    /**
     * Override delete method to prevent database operations
     */
    public function delete()
    {
        if ($this->mutatesData()) {
            return $this->getDataManipulationService()->delete($this->{$this->pKey['field']});
        } else {
            throw new \Exception(static::class . ' Does not implements '. MutateDataRepositoryContract::class);
        }
    }

    /**
     * Override refresh method to prevent database operations
     */
    public function refresh()
    {
        if ($this->accessesData()) {
            return $this->getDataManipulationService()->refresh($this->{$this->pKey['field']});
        } else {
            throw new \Exception(static::class . ' Does not implements '. AccessRepositoryContract::class);
        }
    }

    /**
     * Override to prevent Laravel from syncing original attributes
     */
    public function syncOriginal()
    {
        $this->original = $this->attributes;
        return $this;
    }


    /**
     * Override to prevent Laravel from performing database operations
     */
    public function performUpdate(\Illuminate\Database\Eloquent\Builder $query)
    {
        return true;
    }

    /**
     * Override to prevent Laravel from performing database operations
     */
    public function performInsert(\Illuminate\Database\Eloquent\Builder $query)
    {
        return true;
    }

    /**
     * Override the newInstance method to ensure proper model creation
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $model = new static((array)$attributes);
        $model->exists = $exists;
        $model->setConnection($this->getConnectionName());
        return $model;
    }

    /**
     * Override the setRawAttributes method to properly handle attributes
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        $this->attributes = $attributes;

        if ($sync) {
            $this->syncOriginal();
        }

        return $this;
    }
}