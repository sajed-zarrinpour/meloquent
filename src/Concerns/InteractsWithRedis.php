<?php
namespace SajedZarinpour\Meloquent\Concerns;

use ReflectionObject;
use SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns\CRUD;
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

        self::_init();

        if (($res =self::$redisClient->get($id))) {
            return $res;
        }

        $result = parent::find($id);
        self::$redisClient->set($id, $result);

        return $result;
    }
    
    // update redis cache
    public static function create($attributes = [])
    {
        self::_init();
        $newInstance = parent::create($attributes);
        $newInstance->exists = true;
        self::$redisClient->set($newInstance->id, $newInstance);
        return $newInstance;
    }

    public function update($attributes = [], $options=[])
    {

    }

    public function delete()
    {
        self::_init();
        self::$redisClient->forget($this->id);

    }

}