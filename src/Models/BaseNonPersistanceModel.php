<?php

namespace SajedZarinpour\Meloquent\Models;

use Illuminate\Database\Eloquent\Model;
use SajedZarinpour\Meloquent\Concerns\IsNonpersistance;

class BaseNonPersistanceModel extends Model implements \ArrayAccess
{
    use IsNonpersistance;
    
    /**
     * Implement ArrayAccess to allow array access to attributes
     * This allows $instance['attribute'] syntax to work
     */
    public function offsetExists($offset): bool
    {
        return isset($this->attributes[$offset]) || isset($this->$offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->attributes[$offset] ?? $this->$offset ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->attributes[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }
}
