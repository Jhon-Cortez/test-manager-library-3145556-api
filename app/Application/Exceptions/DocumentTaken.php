<?php

declare(strict_types=1);

namespace App\Application\Exceptions;

final class DocumentTaken extends ApplicationException
{
    public function __construct()
    {
        parent::__construct('Ya existe un socio con ese documento.');
    }
}
