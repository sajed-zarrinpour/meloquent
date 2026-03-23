<?php
namespace SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns;

trait TableStructure
{
    /**
     * The table associated with the model.
     * This is set to null since we're not using a real table.
     *
     * @var string|null
     */
    protected $table = null;

    /**
     * Indicates if the model exists.
     *
     * @var bool
     */
    public $exists = false;


    public function getKey()
    {
        return $this->pKey['field'];
    }

    /**
     * Override to set proper key type
     */
    public function getKeyType()
    {
        return $this->pKey['type'];
    }

    /**
     * Override to prevent auto-incrementing behavior
     */
    public function getIncrementing()
    {
        return false;
    }

    public function getTable()
    {
        return null; // prevents calling any table
    }

    public function getConnectionName()
    {
        return null;
    }

    /**
     * Override to prevent Laravel from trying to resolve the model
     */
    public static function resolveConnection($connection = null)
    {
        return null;
    }

    /**
     * Override to prevent Laravel from using a database connection
     */
    public function getConnection()
    {
        return null;
    }


    /**
     * Normalize snake/camel variants for loose matching.
     */
    protected function snakeOrCamel(string $s): string
    {
        // quick heuristic: remove underscores and lowercase
        return strtolower(str_replace('_', '', $s));
    }

}