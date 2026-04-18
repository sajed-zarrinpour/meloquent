<?php
namespace SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use SajedZarinpour\Meloquent\Contracts\AccessRepositoryContract;
use SajedZarinpour\Meloquent\Exceptions\BreakingContractCallException;

trait DataFetching {

    use Metadata;

    /**
     * Override the find method to use UserCacheService instead of database queries
     *
     * @param mixed $id
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model|static|null
     */
    public static function find($id, $columns = ['*'])
    {
        try {
            
            if (
                ! static::accessesData()
            ) {
                throw new BreakingContractCallException(static::class, AccessRepositoryContract::class);
            }
            $data = static::getUserDefinedDataRepositoryReflectionAttribute()->newInstance()->find($id);

            if (empty($data)) {
                return null;
            }

            return static::hydrate([$data])->first();

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Override the findOrFail method to use UserCacheService
     *
     * @param mixed $id
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model|static
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function findOrFail($id, $columns = ['*'])
    {
        $result = static::find($id, $columns);

        if (!$result) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(
                static::class,
                $id
            );
        }

        return $result;
    }

    public static function firstOrFail($id)
    {
        $result = static::find($id);

        if (!$result) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(
                static::class,
                $id
            );
        }

        return $result;
    }


    /**
     * Create a new instance of the model from cache data
     *
     * @param array $attributes
     * @param bool $exists
     * @return static
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $model = $this->newInstance([], true);

        $model->setRawAttributes((array)$attributes, true);

        return $model;
    }

    /**
     * Custom hydration method for UserCacheService data
     *
     * @param array $items
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function hydrate(array $items, $connection = null)
    {
        $instance = new static;
        $models = new Collection();

        foreach ($items as $item) {
            $model = $instance->newFromBuilder($item);
            $model->exists = true;
            $models->push($model);
        }

        return $models;
    }

    /**
     * Override the where method to query UserCacheService instead
     * This supports basic where clauses for organization_id and id
     *
     * @param string|\Closure $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $boolean
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        try {
            
            if (
                ! static::accessesData()
            ) {
                throw new BreakingContractCallException(static::class, AccessRepositoryContract::class);
            }
            
            return static::getUserDefinedDataRepositoryReflectionAttribute()->newInstance()->where($column, $operator, $value, $boolean);

        } catch (\Exception $e) {
            return null;
        }
    }

    // Helper method to create an Employee instance from user data
    protected static function make($incomingData)
    {
        $instance = new static();
        foreach ($instance->fillable as $field) {
            if (isset($incomingData[$field])) {
                $instance->{$field} = $incomingData[$field];
            }
        }
        $instance->exists = true;
        return $instance;
    }

    /**
     * Override the newQuery method to return a custom query builder
     * that works with UserCacheService
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQuery()
    {
        // Return a custom builder that prevents database operations
        return new class extends \Illuminate\Database\Eloquent\Builder {
            public function __construct()
            {
                // Don't call parent constructor to avoid database connection
            }

            public function get($columns = ['*'])
            {
                return new \Illuminate\Database\Eloquent\Collection();
            }

            public function first($columns = ['*'])
            {
                return null;
            }

            public function count()
            {
                return 0;
            }

            public function delete()
            {
                return true;
            }

            public function update(array $values)
            {
                return true;
            }

            public function insert(array $values)
            {
                return true;
            }

            public function where($column, $operator = null, $value = null, $boolean = 'and')
            {
                return $this;
            }

            public function orderBy($column, $direction = 'asc')
            {
                return $this;
            }

            // Prevent any other database operations
            public function __call($method, $parameters)
            {
                return $this;
            }
        };
    }
}