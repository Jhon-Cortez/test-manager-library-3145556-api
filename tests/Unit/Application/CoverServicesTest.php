<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exceptions\NotFound;
use App\Application\Services\CreateBookService;
use App\Application\Services\RemoveCoverService;
use App\Application\Services\UploadCoverService;
use App\Domain\Exceptions\InvalidValue;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakeCoverStorage;
use Tests\Fakes\InMemoryBookRepository;

final class CoverServicesTest extends TestCase
{
    private InMemoryBookRepository $books;
    private FakeCoverStorage $storage;
    private CreateBookService $create;
    private UploadCoverService $upload;
    private RemoveCoverService $remove;

    protected function setUp(): void
    {
        $this->books = new InMemoryBookRepository();
        $this->storage = new FakeCoverStorage();
        $this->create = new CreateBookService($this->books);
        $this->upload = new UploadCoverService($this->books, $this->storage);
        $this->remove = new RemoveCoverService($this->books, $this->storage);
    }

    private function makeBook(): \App\Domain\Book
    {
        return $this->create->create('Título', 'Autor', '9780307474728', 'cat-1', 1);
    }

    public function test_png_cover_is_stored_as_an_opaque_key(): void
    {
        $book = $this->makeBook();

        $updated = $this->upload->upload($book->id(), "\x89PNG\r\n\x1a\n" . 'binario');

        self::assertNotNull($updated->coverKey());
        self::assertStringStartsWith('cover-opaque-', $updated->coverKey());
        self::assertSame("\x89PNG\r\n\x1a\n" . 'binario', $this->storage->read($updated->coverKey()));
    }

    public function test_jpg_cover_is_accepted(): void
    {
        $book = $this->makeBook();

        $updated = $this->upload->upload($book->id(), "\xFF\xD8\xFF" . 'binario');

        self::assertNotNull($updated->coverKey());
    }

    public function test_oversized_cover_is_rejected(): void
    {
        $book = $this->makeBook();

        $this->expectException(InvalidValue::class);
        $this->upload->upload($book->id(), "\x89PNG\r\n\x1a\n" . str_repeat('a', UploadCoverService::MAX_COVER_BYTES));
    }

    public function test_non_image_content_is_rejected(): void
    {
        $book = $this->makeBook();

        $this->expectException(InvalidValue::class);
        $this->upload->upload($book->id(), 'GIF89a' . 'binario');
    }

    public function test_removing_the_cover_sets_null_never_empty_string(): void
    {
        $book = $this->makeBook();
        $updated = $this->upload->upload($book->id(), "\x89PNG\r\n\x1a\n" . 'binario');
        $key = $updated->coverKey();

        $removed = $this->remove->remove($book->id());

        self::assertNull($removed->coverKey());
        self::assertNull($this->storage->read($key));
    }

    public function test_uploading_to_a_missing_book_is_not_found(): void
    {
        $this->expectException(NotFound::class);

        $this->upload->upload('no-existe', "\x89PNG\r\n\x1a\n" . 'binario');
    }
}
