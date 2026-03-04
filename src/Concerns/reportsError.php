<?php
namespace SajedZarinpour\Meloquent\Concerns;

use Exception;

trait reportsError {

    public static function throws($errorMessage) {
        $trace = debug_backtrace();
        throw new Exception(
            $errorMessage.
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line']
        );

        // debug_print_backtrace();

    }
}