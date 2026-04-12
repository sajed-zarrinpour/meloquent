<?php

use SajedZarinpour\Meloquent\Meloquent;

/**
 * Adding helper functionality to the package. The following calls are equivalent.
 * ```
 *      meloquent::some_func()
 * ```
 * vs
 * ```
 *      meloquent()->some_func()
 * ```
 */
if (!function_exists('meloquent')) {
    function meloquent(?array $priorityTable=[]): Meloquent
    {
        return new Meloquent($priorityTable);
    }
}
