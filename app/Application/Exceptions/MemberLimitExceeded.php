<?php

declare(strict_types=1);

namespace App\Application\Exceptions;

final class MemberLimitExceeded extends ApplicationException
{
    public function __construct(int $currentUnreturned)
    {
        parent::__construct("El socio ya tiene $currentUnreturned libros sin devolver (máximo 3).");
    }
}
