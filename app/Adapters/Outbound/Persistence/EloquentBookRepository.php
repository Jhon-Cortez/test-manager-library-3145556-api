<?php

declare(strict_types=1);

namespace App\Adapters\Outbound\Persistence;

use App\Adapters\Outbound\Persistence\Models\BookModel;
use App\Application\Exceptions\OptimisticLockConflict;
use App\Application\Ports\Outbound\BookRepository;
use App\Application\Ports\Outbound\Support\Page;
use App\Domain\Book;
use App\Domain\Isbn;
use DateTimeImmutable;

final class EloquentBookRepository implements BookRepository
{
    public function findById(string $id): ?Book
    {
        $row = BookModel::query()->find($id);

        return $row === null ? null : $this->toDomain($row);
    }

    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return BookModel::query()->whereIn('id', $ids)->get()
            ->map(fn (BookModel $row): Book => $this->toDomain($row))
            ->all();
    }

    public function add(Book $book): void
    {
        BookModel::query()->insert($this->toRow($book));
    }

    public function save(Book $book): void
    {
        $row = $this->toRow($book);
        $row['version'] = $book->version() + 1;

        $affected = BookModel::query()
            ->where('id', $book->id())
            ->where('version', $book->version())
            ->update($row);

        if ($affected === 0) {
            throw new OptimisticLockConflict();
        }
    }

    public function existsByIsbn(string $isbn, ?string $exceptBookId = null): bool
    {
        $query = BookModel::query()
            ->whereNull('deleted_at')
            ->where('isbn', $isbn);
        if ($exceptBookId !== null) {
            $query->where('id', '!=', $exceptBookId);
        }

        return $query->exists();
    }

    public function search(?string $query, ?string $categoryId, int $page, int $size): Page
    {
        $builder = BookModel::query()->whereNull('deleted_at');
        if ($categoryId !== null) {
            $builder->where('category_id', $categoryId);
        }
        if ($query !== null && $query !== '') {
            $needle = '%' . $query . '%';
            $builder->where(function ($inner) use ($needle): void {
                $inner->whereRaw('title ILIKE ?', [$needle])
                    ->orWhereRaw('author ILIKE ?', [$needle]);
            });
        }

        $total = (clone $builder)->count();
        $rows = $builder->orderBy('title')
            ->offset(($page - 1) * $size)
            ->limit($size)
            ->get();

        return new Page(
            $rows->map(fn (BookModel $row): Book => $this->toDomain($row))->all(),
            $total,
            $page,
            $size,
        );
    }

    private function toDomain(BookModel $row): Book
    {
        return Book::reconstitute(
            (string) $row->id,
            (string) $row->title,
            (string) $row->author,
            Isbn::of((string) $row->isbn),
            (string) $row->category_id,
            (int) $row->total_copies,
            (int) $row->available_copies,
            $row->cover_key === null ? null : (string) $row->cover_key,
            $row->deleted_at === null ? null : new DateTimeImmutable((string) $row->deleted_at),
            (int) $row->version,
        );
    }

    /** @return array<string, mixed> */
    private function toRow(Book $book): array
    {
        return [
            'id' => $book->id(),
            'title' => $book->title(),
            'author' => $book->author(),
            'isbn' => $book->isbn()->value(),
            'category_id' => $book->categoryId(),
            'total_copies' => $book->totalCopies(),
            'available_copies' => $book->availableCopies(),
            'cover_key' => $book->coverKey(),
            'deleted_at' => $book->deletedAt()?->format('Y-m-d H:i:s.uP'),
            'version' => $book->version(),
        ];
    }
}
