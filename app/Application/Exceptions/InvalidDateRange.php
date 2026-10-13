<?php

declare(strict_types=1);

namespace App\Application\Exceptions;

final class InvalidDateRange extends ApplicationException
{
    public function __construct()
    {
        parent::__construct('El rango de fechas es inválido.');
    }
}
