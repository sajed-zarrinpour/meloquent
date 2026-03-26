<?php
namespace SajedZarinpour\Meloquent\Contracts;

interface AccessRepositoryContract
{
    public function find();
    public function where($column, $operator='=', $value=null);
    public function refresh($pKey);
}