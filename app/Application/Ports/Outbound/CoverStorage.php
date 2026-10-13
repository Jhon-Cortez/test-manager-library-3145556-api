<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

interface CoverStorage
{
    public function store(string $bookId, string $binary, string $extension): string;

    public function read(string $coverKey): ?string;

    public function delete(string $coverKey): void;
}
