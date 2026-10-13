<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

interface TokenParser
{
    /** @return array{sub: string, role: string} */
    public function parse(string $token): array;
}
