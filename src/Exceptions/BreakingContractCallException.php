<?php

namespace SajedZarinpour\Meloquent\Exceptions;

class BreakingContractCallException extends \Error implements MelloquentExceptionInterface
{

    public function __construct(string $contractor, string $contract) {
        $this->message = sprintf(
            $this->getFormattedString(),
            $contractor,
            $contract,
            $this->getLine(),
            $this->getFile(),
        );
    }

    private function getFormattedString()
    {
        return "%s Does not implements %s, line: %s, file: %s";
    }

    public function errorMessage() {    
        return $this->message;
    }

}