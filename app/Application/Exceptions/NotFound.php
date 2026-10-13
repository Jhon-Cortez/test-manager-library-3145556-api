<?php

declare(strict_types=1);

namespace App\Application\Exceptions;

final class NotFound extends ApplicationException
{
    public function __construct(string $resource = 'recurso')
    {
        parent::__construct("No existe $resource.");
    }
}
