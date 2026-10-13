<?php

declare(strict_types=1);

namespace App\Application\Exceptions;

final class BookHasOpenLoans extends ApplicationException
{
    public function __construct()
    {
        parent::__construct('El libro tiene líneas sin devolver.');
    }
}
