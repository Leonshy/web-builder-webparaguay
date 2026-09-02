<?php

namespace App\Generation;

use RuntimeException;

class GenerationFailed extends RuntimeException
{
    /** @param array<int,string> $errors */
    public function __construct(public array $errors)
    {
        parent::__construct('El sitio generado no valida contra el esquema: '.implode(' | ', array_slice($errors, 0, 3)));
    }
}
