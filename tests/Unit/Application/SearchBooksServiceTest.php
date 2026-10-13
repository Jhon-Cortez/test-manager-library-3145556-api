<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exceptions\NotFound;
use App\Application\Services\CreateBookService;
use App\Application\Services\GetBookService;
use App\Application\Services\SearchBooksService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryBookRepository;

final class SearchBooksServiceTest extends TestCase
{
    private InMemoryBookRepository $books;
    private CreateBookService $create;
    private GetBookService $get;
    private SearchBooksService $search;

    protected function setUp(): void
    {
        $this->books = new InMemoryBookRepository();
        $this->create = new CreateBookService($this->books);
        $this->get = new GetBookService($this->books);
        $this->search = new SearchBooksService($this->books);
    }

    public function test_search_matches_partial_text_in_title_or_author(): void
    {
        $this->create->create('Cien años de soledad', 'Gabriel García Márquez', '9780307474728', 'cat-1', 1);
        $this->create->create('El amor en los tiempos del cólera', 'Gabriel García Márquez', '9780307474729', 'cat-1', 1);
        $this->create->create('La ciudad y los perros', 'Mario Vargas Llosa', '9780307474730', 'cat-1', 1);

        $byTitle = $this->search->search('ciudad', null, 1, 20);
        self::assertCount(1, $byTitle->items());
        self::assertSame(1, $byTitle->total());

        $byAuthor = $this->search->search('garcía', null, 1, 20);
        self::assertCount(2, $byAuthor->items());
        self::assertSame(2, $byAuthor->total());
    }

    public function test_search_filters_by_category_and_excludes_deleted_books(): void
    {
        $novela = $this->create->create('Libro N', 'Autor', '9780307474728', 'cat-novela', 1);
        $this->create->create('Libro C', 'Autor', '9780307474729', 'cat-ciencia', 1);
        $borrado = $this->create->create('Libro Borrado', 'Autor', '9780307474730', 'cat-novela', 1);
        $borrado->markDeleted(new DateTimeImmutable('2026-10-13T12:00:00Z'));

        $result = $this->search->search(null, 'cat-novela', 1, 20);

        self::assertSame(1, $result->total());
        self::assertSame($novela->id(), $result->items()[0]->id());
    }

    public function test_results_are_sorted_by_title_and_paginated_with_total(): void
    {
        $this->create->create('Bravo', 'Autor', '9780307474728', 'cat-1', 1);
        $this->create->create('Alfa', 'Autor', '9780307474729', 'cat-1', 1);
        $this->create->create('Zulu', 'Autor', '9780307474730', 'cat-1', 1);

        $page1 = $this->search->search(null, null, 1, 2);

        self::assertSame(3, $page1->total());
        self::assertCount(2, $page1->items());
        self::assertSame('Alfa', $page1->items()[0]->title());
        self::assertSame('Bravo', $page1->items()[1]->title());

        $page2 = $this->search->search(null, null, 2, 2);
        self::assertSame('Zulu', $page2->items()[0]->title());
    }

    public function test_page_size_is_capped_at_fifty(): void
    {
        $result = $this->search->search(null, null, 1, 500);

        self::assertSame(50, $result->size());
    }

    public function test_get_returns_the_book(): void
    {
        $book = $this->create->create('Título', 'Autor', '9780307474728', 'cat-1', 1);

        self::assertSame($book->id(), $this->get->get($book->id())->id());
    }

    public function test_get_of_missing_or_deleted_book_is_not_found(): void
    {
        $book = $this->create->create('Título', 'Autor', '9780307474728', 'cat-1', 1);
        $book->markDeleted(new DateTimeImmutable('2026-10-13T12:00:00Z'));

        $this->expectException(NotFound::class);
        $this->get->get($book->id());
    }
}
