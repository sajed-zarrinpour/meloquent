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

    public function get($key)
    {
        $result = match ($this->alg) {
            RedisTypesEnum::STRING => $this->getString($key),
            RedisTypesEnum::HASH => $this->getHash($key),
            default => null,
        };

        return $result;
    }

    public function set($key, Model $value)
    {
        match ($this->alg) {
            RedisTypesEnum::STRING => $this->setString($key,$value),
            RedisTypesEnum::HASH => $this->setHash($key, $value),
            default => null,
        };
        
        FacadesRedis::expire($this->modelFqn.$key, $this->ttl);
    }

    public function forget($key)
    {
        return FacadesRedis::del($this->modelFqn.$key);
    }

    private function getString($key)
    {
        $value =  json_decode(FacadesRedis::get($this->modelFqn.$key));
        if (!empty($value)) {
            $instance = new ($this->modelFqn)((array)$value);
            $instance->exists = true;
            return $instance;
        }

        return null;
    }

    private function getHash($key)
    {

        $value =  FacadesRedis::hgetall($this->modelFqn.$key);
        if (!empty($value)) {
            $instance = new ($this->modelFqn)($value);
            $instance->exists = true;
            return $instance;
        }

        return null;
    }

    private function setString($key, Model $value)
    {
        FacadesRedis::set($this->modelFqn.$key, json_encode($value));
    }

    private function setHash($key, Model $value)
    {
        FacadesRedis::hmset($this->modelFqn.$key, $value->toArray());
    }
}