<?php

declare(strict_types=1);

namespace App\Application\Exceptions;

final class IsbnTaken extends ApplicationException
{
    public function __construct()
    {
        parent::__construct('Ya existe un libro activo con ese ISBN.');
    }
}
