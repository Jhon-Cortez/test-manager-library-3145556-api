<?php

declare(strict_types=1);

namespace App\Application\Exceptions;

final class MemberHasOverdue extends ApplicationException
{
    public function __construct()
    {
        parent::__construct('El socio tiene libros vencidos.');
    }
}
