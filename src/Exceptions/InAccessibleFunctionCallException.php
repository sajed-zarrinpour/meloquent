<?php

namespace SajedZarinpour\Meloquent\Exceptions;

class InAccessibleFunctionCallException extends \Error implements MelloquentExceptionInterface
{

    public function __construct(string $name, array $arguments) {
        $this->message = sprintf(
            $this->getFormattedString(),
            // $this->getCode(),
            $name,
            implode(', ', $arguments),
            $this->getLine(),
            $this->getFile(),
            // $this->getTraceAsString(),
        );
    }

    private function getFormattedString()
    {
        return "Calling inaccessible object method %s, params: %s in %s, line %s";
        // return "Calling inaccessible object method (Code %d) %s, params: %s in %s, line %s". \PHP_EOL."trace ".\PHP_EOL."%s";
    }

    public function errorMessage() {    
        return $this->message;//. '\n'. debug_print_backtrace();
    }

}