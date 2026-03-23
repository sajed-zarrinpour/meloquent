<?php
namespace SajedZarinpour\Meloquent\Contracts;
interface MutateDataRepositoryContract {
    public function create($attributes=[]);
    public function update($attributes=[]);
    public function delete($pKey);
}