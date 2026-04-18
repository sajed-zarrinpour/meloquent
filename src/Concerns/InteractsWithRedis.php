<?php
namespace SajedZarinpour\Meloquent\Concerns;

use Exception;
use ReflectionObject;
use SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns\CRUD;
use SajedZarinpour\Meloquent\Contracts\AccessRepositoryContract;
use SajedZarinpour\Meloquent\Contracts\MutateDataRepositoryContract;
use SajedZarinpour\Meloquent\Exceptions\BreakingContractCallException;
use SajedZarinpour\Meloquent\Services\Redis;

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