<?php
namespace SajedZarinpour\Meloquent\Concerns;

use SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns\CRUD;
use SajedZarinpour\Meloquent\Contracts\AccessRepositoryContract;
use SajedZarinpour\Meloquent\Contracts\MutateDataRepositoryContract;
use SajedZarinpour\Meloquent\Exceptions\BreakingContractCallException;
use SajedZarinpour\Meloquent\Services\Redis;

/**
 * my vision for this is as follow:
 * - reduce api calls in non persistant data by caching them automatically, but 
 *   doing this in a way that doesn't change the native elloquent syntax
 * - if the user can mutate data, provide a seemless way to do this with updating cache
 * - if the user cannot mutate data, give him the option to update the cache nonetheless, but user must specifically request for this
 */
trait InteractsWithRedis 
{
    use CRUD;

    protected static Redis $redisClient;

    public static function _init()
    {
        if (empty(self::$redisClient)) {
            self::$redisClient = new Redis(static::class, 3600);
        }
    }

    // read and write data in redis
    public static function find($id, $columns=[])
    {
        if (static::accessesData()) {
            self::_init();

            if (($res =self::$redisClient->get($id))) {
                return $res;
            }

            $result = parent::find($id);
            self::$redisClient->set($id, $result);

            return $result;
        } else {
            throw new BreakingContractCallException(static::class, AccessRepositoryContract::class);
        }
    }
    
    // update redis cache
    public static function create($attributes = [])
    {
        if (static::mutatesData()) {
            self::_init();
            $newInstance = parent::create($attributes);
            $newInstance->exists = true;
            self::$redisClient->set($newInstance->id, $newInstance);
            return $newInstance;
        } else {
            throw new BreakingContractCallException(static::class, MutateDataRepositoryContract::class);
        }
    }

    public function update($attributes = [], $options=[])
    {
        if ($this->mutatesData()) {
        } else {
            throw new BreakingContractCallException(static::class, MutateDataRepositoryContract::class);
        }
    }

    public function save(array $options = [])
    {
        if ($this->mutatesData()) {

            // parent::save($options);
            
            // lifting some codes from the laravel model itself, so it stays consistant with the elloquent agenda
            // but keep in mind, this is acting on a non persistance model,
            // so, we do want to fire events for listeners, but it is your job to make sure that your listeners
            // do not rely on db fetching for them!

            $this->mergeAttributesFromCachedCasts();

            $attributes = $this->getAttributes();

            parent::save($attributes); // is this the right way?

            // If the "saving" event returns false we'll bail out of the save and return
            // false, indicating that the save failed. This provides a chance for any
            // listeners to cancel save operations if validations fail or whatever.
            if ($this->fireModelEvent('saving') === false) {
                return false;
            }

            // If the model already exists in the database we can just update our record
            // that is already in this database using the current IDs in this "where"
            // clause to only update this model. Otherwise, we'll just insert them.
            if ($this->exists) {
                $saved = $this->isDirty() ?
                    self::$redisClient->set($this->id, $this) : true;
            }

            // If the model is brand new, we'll insert it into our database and set the
            // ID attribute on the model to the value of the newly inserted row's ID
            // which is typically an auto-increment value managed by the database.
            else {
                $saved = self::create($attributes);
            }

            // If the model is successfully saved, we need to do a few more things once
            // that is done. We will call the "saved" method here to run any actions
            // we need to happen after a model gets successfully saved right here.
            if ($saved) {
                $this->finishSave($options);
            }

            return $saved;
        } else {
            throw new BreakingContractCallException(static::class, MutateDataRepositoryContract::class);
        }
    }

    public function delete()
    {
        if ($this->mutatesData()) {
            self::_init();
            self::$redisClient->forget($this->id);
        } else {
            throw new BreakingContractCallException(static::class, MutateDataRepositoryContract::class);
        }
    }

}