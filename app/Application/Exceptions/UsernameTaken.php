<?php

declare(strict_types=1);

namespace App\Application\Exceptions;

final class UsernameTaken extends ApplicationException
{
    public function __construct()
    {
        parent::__construct('El nombre de usuario ya está en uso.');
    }
}
