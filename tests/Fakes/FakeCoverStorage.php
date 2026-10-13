<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Application\Ports\Outbound\CoverStorage;

final class FakeCoverStorage implements CoverStorage
{
    /** @var array<string, string> */
    public array $stored = [];

    private int $sequence = 0;

    public function store(string $bookId, string $binary, string $extension): string
    {
        $key = 'cover-opaque-' . ++$this->sequence;
        $this->stored[$key] = $binary;

        return $key;
    }

    public function read(string $coverKey): ?string
    {
        return $this->stored[$coverKey] ?? null;
    }

    public function delete(string $coverKey): void
    {
        unset($this->stored[$coverKey]);
    }
}
