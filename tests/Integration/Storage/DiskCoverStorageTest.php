<?php

declare(strict_types=1);

namespace Tests\Integration\Storage;

use App\Adapters\Outbound\Storage\DiskCoverStorage;
use PHPUnit\Framework\TestCase;

final class DiskCoverStorageTest extends TestCase
{
    private string $directory;
    private DiskCoverStorage $storage;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/covers-' . bin2hex(random_bytes(6));
        $this->storage = new DiskCoverStorage($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }
        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    public function test_stores_and_reads_back_by_opaque_key(): void
    {
        $key = $this->storage->store('book-1', "\x89PNG\r\n\x1a\nbinario", 'png');

        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $key);
        self::assertSame("\x89PNG\r\n\x1a\nbinario", $this->storage->read($key));
    }

    public function test_reading_an_unknown_key_returns_null(): void
    {
        self::assertNull($this->storage->read('clave-inexistente'));
    }

    public function test_deleting_removes_the_binary(): void
    {
        $key = $this->storage->store('book-1', 'binario', 'jpg');

        $this->storage->delete($key);

        self::assertNull($this->storage->read($key));
    }

    public function test_deleting_an_unknown_key_is_idempotent(): void
    {
        $this->storage->delete('clave-inexistente');

        self::assertNull($this->storage->read('clave-inexistente'));
    }
}
