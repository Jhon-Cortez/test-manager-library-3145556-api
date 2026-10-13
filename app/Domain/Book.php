<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exceptions\BookNotAvailable;
use App\Domain\Exceptions\InvalidValue;
use DateTimeImmutable;

final class Book
{
    private function __construct(
        private readonly string $id,
        private string $title,
        private string $author,
        private readonly Isbn $isbn,
        private string $categoryId,
        private int $totalCopies,
        private int $availableCopies,
        private ?string $coverKey,
        private ?DateTimeImmutable $deletedAt,
        private int $version,
    ) {
    }

    public static function create(
        string $id,
        string $title,
        string $author,
        Isbn $isbn,
        string $categoryId,
        Quantity $total,
    ): self {
        $title = trim($title);
        if ($title === '') {
            throw new InvalidValue('El título no puede estar vacío.');
        }
        $author = trim($author);
        if ($author === '') {
            throw new InvalidValue('El autor no puede estar vacío.');
        }
        if ($total->isZero()) {
            throw new InvalidValue('El total de ejemplares debe ser al menos 1.');
        }

        return new self(
            $id,
            $title,
            $author,
            $isbn,
            $categoryId,
            $total->value(),
            $total->value(),
            null,
            null,
            1,
        );
    }

    public static function reconstitute(
        string $id,
        string $title,
        string $author,
        Isbn $isbn,
        string $categoryId,
        int $totalCopies,
        int $availableCopies,
        ?string $coverKey,
        ?DateTimeImmutable $deletedAt,
        int $version,
    ): self {
        return new self(
            $id,
            $title,
            $author,
            $isbn,
            $categoryId,
            $totalCopies,
            $availableCopies,
            $coverKey,
            $deletedAt,
            $version,
        );
    }

    public function update(string $title, string $author, string $categoryId, Quantity $total): void
    {
        $title = trim($title);
        if ($title === '') {
            throw new InvalidValue('El título no puede estar vacío.');
        }
        $author = trim($author);
        if ($author === '') {
            throw new InvalidValue('El autor no puede estar vacío.');
        }
        if ($total->isZero()) {
            throw new InvalidValue('El total de ejemplares debe ser al menos 1.');
        }

        $delta = $total->value() - $this->totalCopies;
        $newAvailable = $this->availableCopies + $delta;
        if ($newAvailable < 0) {
            throw new InvalidValue('El cambio de total deja disponibles por debajo de cero.');
        }

        $this->title = $title;
        $this->author = $author;
        $this->categoryId = $categoryId;
        $this->totalCopies = $total->value();
        $this->availableCopies = $newAvailable;
    }

    public function withdraw(): void
    {
        if ($this->availableCopies === 0) {
            throw new BookNotAvailable('No quedan ejemplares disponibles.');
        }

        $this->availableCopies--;
    }

    public function restock(): void
    {
        if ($this->availableCopies >= $this->totalCopies) {
            throw new InvalidValue('Los disponibles jamás pueden superar el total.');
        }

        $this->availableCopies++;
    }

    public function setCoverKey(string $coverKey): void
    {
        $coverKey = trim($coverKey);
        if ($coverKey === '') {
            throw new InvalidValue('La clave de portada no puede ser una cadena vacía.');
        }

        $this->coverKey = $coverKey;
    }

    public function clearCover(): void
    {
        $this->coverKey = null;
    }

    public function markDeleted(DateTimeImmutable $now): void
    {
        if ($this->deletedAt !== null) {
            return;
        }

        $this->deletedAt = $now;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function author(): string
    {
        return $this->author;
    }

    public function isbn(): Isbn
    {
        return $this->isbn;
    }

    public function categoryId(): string
    {
        return $this->categoryId;
    }

    public function totalCopies(): int
    {
        return $this->totalCopies;
    }

    public function availableCopies(): int
    {
        return $this->availableCopies;
    }

    public function coverKey(): ?string
    {
        return $this->coverKey;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function version(): int
    {
        return $this->version;
    }
}