<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Exceptions\NotFound;
use App\Application\Ports\Outbound\BookRepository;
use App\Application\Ports\Outbound\CoverStorage;
use App\Domain\Book;
use App\Domain\Exceptions\InvalidValue;

final class UploadCoverService
{
    public const MAX_COVER_BYTES = 2 * 1024 * 1024;

    public function __construct(
        private readonly BookRepository $books,
        private readonly CoverStorage $storage,
    ) {
    }

    public function upload(string $bookId, string $binary): Book
    {
        $book = $this->books->findById($bookId);
        if ($book === null || $book->isDeleted()) {
            throw new NotFound('el libro');
        }

        $extension = $this->detectExtension($binary);
        $key = $this->storage->store($book->id(), $binary, $extension);

        $book->setCoverKey($key);
        $this->books->save($book);

        return $book;
    }

    private function detectExtension(string $binary): string
    {
        if ($binary === '' || strlen($binary) > self::MAX_COVER_BYTES) {
            throw new InvalidValue('La portada no puede superar los 2 MB.');
        }
        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return 'png';
        }
        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return 'jpg';
        }

        throw new InvalidValue('La portada debe ser una imagen JPG o PNG.');
    }
}
