<?php

namespace SajedZarinpour\Meloquent\Models;

use Illuminate\Database\Eloquent\Model;

use SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns\CRUD;
use SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns\DataFetching;
use SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns\DateUtils;
use SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns\HandelsNotification;
use SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns\Metadata;
use SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns\TableStructure;


class BaseNonPersistanceModel extends Model implements \ArrayAccess
{
    /**
     * structural concerns:
    */
    use TableStructure;
    
    /**
     * meta data
     */
    use Metadata;

    /**
     * Date 
     */
    use DateUtils;

    /**
     * Data Fetching
     */
    use DataFetching;

    /**
     * handling notifications
     */
    use HandelsNotification;

    /**
     * CRUD
     */
    use CRUD;

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
