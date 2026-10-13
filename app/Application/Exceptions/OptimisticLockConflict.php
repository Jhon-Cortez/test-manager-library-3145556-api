<?php

declare(strict_types=1);

namespace App\Application\Exceptions;

final class OptimisticLockConflict extends ApplicationException
{
    public function __construct()
    {
        parent::__construct('El registro cambió mientras se procesaba la operación.');
    }
}
