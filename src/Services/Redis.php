<?php
namespace SajedZarinpour\Meloquent\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis as FacadesRedis;
use SajedZarinpour\Meloquent\Enums\RedisTypesEnum;

class Redis
{
    // user must not be able to change this after instanciation (only set it once)
    public function __construct(
        public private(set) string $modelFqn='',
        public private(set) int $ttl=3600,
        public private(set) RedisTypesEnum $alg = RedisTypesEnum::STRING,
    )
    {}

    public function get(string $key)
    {
        $result = match ($this->alg) {
            RedisTypesEnum::STRING => $this->getString($key),
            RedisTypesEnum::HASH => $this->getHash($key),
            default => null,
        };

        return $result;
    }

    public function set(string $key, Model $value)
    {
        $result = match ($this->alg) {
            RedisTypesEnum::STRING => $this->setString($key,$value),
            RedisTypesEnum::HASH => $this->setHash($key, $value),
            default => false,
        };
        
        if ($result) {
            FacadesRedis::expire($this->modelFqn.$key, $this->ttl);
        }

        return $result;
    }

    public function forget(string $key)
    {
        return FacadesRedis::del($this->modelFqn.$key);
    }

    private function getString(string $key)
    {
        $value =  json_decode(FacadesRedis::get($this->modelFqn.$key));
        if (!empty($value)) {
            $instance = new ($this->modelFqn)((array)$value);
            $instance->exists = true;
            return $instance;
        }

        return null;
    }

    private function getHash(string $key)
    {

        $value =  FacadesRedis::hgetall($this->modelFqn.$key);
        if (!empty($value)) {
            $instance = new ($this->modelFqn)($value);
            $instance->exists = true;
            return $instance;
        }

        return null;
    }

    private function setString(string $key, Model $value)
    {
        return FacadesRedis::set($this->modelFqn.$key, json_encode($value));
    }

    private function setHash(string $key, Model $value)
    {
        return FacadesRedis::hmset($this->modelFqn.$key, $value->toArray());
    }
}