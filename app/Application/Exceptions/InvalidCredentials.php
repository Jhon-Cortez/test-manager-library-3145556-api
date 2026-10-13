<?php

declare(strict_types=1);

namespace App\Application\Exceptions;

final class InvalidCredentials extends ApplicationException
{
    public function __construct()
    {
        parent::__construct('Credenciales inválidas.');
    }
}
