<?php

declare(strict_types=1);

namespace App\Application\Exceptions;

final class ForbiddenRole extends ApplicationException
{
    public function __construct()
    {
        parent::__construct('Su rol no permite esta operación.');
    }
}
