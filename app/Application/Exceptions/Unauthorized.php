<?php

declare(strict_types=1);

namespace App\Application\Exceptions;

final class Unauthorized extends ApplicationException
{
    public function __construct()
    {
        parent::__construct('Se requiere un token válido.');
    }
}
