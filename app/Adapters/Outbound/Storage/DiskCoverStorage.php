<?php

declare(strict_types=1);

namespace App\Adapters\Outbound\Storage;

use App\Application\Ports\Outbound\CoverStorage;

final class DiskCoverStorage implements CoverStorage
{
    public function __construct(private readonly string $directory)
    {
    }

    public function store(string $bookId, string $binary, string $extension): string
    {
        if (! is_dir($this->directory)) {
            mkdir($this->directory, 0775, true);
        }

        $key = bin2hex(random_bytes(16));
        file_put_contents($this->path($key), $binary);

        return $key;
    }

    public function read(string $coverKey): ?string
    {
        $path = $this->path($coverKey);
        if (! is_file($path)) {
            return null;
        }

        return (string) file_get_contents($path);
    }

    public function delete(string $coverKey): void
    {
        $path = $this->path($coverKey);
        if (is_file($path)) {
            unlink($path);
        }
    }

    private function path(string $coverKey): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . basename($coverKey);
    }
}
