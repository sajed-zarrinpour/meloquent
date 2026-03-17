<?php

namespace SajedZarinpour\Meloquent\Exceptions;

class InAccessibleFunctionCallException extends \Error implements MelloquentExceptionInterface
{

    public function __construct(string $name, array $arguments) {
        $this->message = sprintf(
            $this->getFormattedString(),
            $name,
            implode(', ', $arguments),
            $this->getLine(),
            $this->getFile(),
        );
    }

    private function getFormattedString()
    {
        return "Calling inaccessible object method %s, params: %s in %s, line %s";
    }

    public function errorMessage() {    
        return $this->message;
    }

}