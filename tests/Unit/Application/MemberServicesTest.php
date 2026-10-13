<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exceptions\DocumentTaken;
use App\Application\Exceptions\NotFound;
use App\Application\Services\CreateBookService;
use App\Application\Services\CreateMemberService;
use App\Application\Services\GetMemberService;
use App\Application\Services\SearchMembersService;
use App\Domain\Loan;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryBookRepository;
use Tests\Fakes\InMemoryLoanRepository;
use Tests\Fakes\InMemoryMemberRepository;
use Tests\Support\FakeClock;

final class MemberServicesTest extends TestCase
{
    private InMemoryMemberRepository $members;
    private InMemoryLoanRepository $loans;
    private CreateMemberService $create;
    private SearchMembersService $search;
    private GetMemberService $get;

    protected function setUp(): void
    {
        $this->members = new InMemoryMemberRepository();
        $this->loans = new InMemoryLoanRepository();
        $this->create = new CreateMemberService($this->members);
        $this->search = new SearchMembersService($this->members);
        $this->get = new GetMemberService($this->members, $this->loans);
    }

    public function test_creates_a_member_with_name_and_document(): void
    {
        $member = $this->create->create('Ana Torres', '123456789');

        self::assertSame('Ana Torres', $member->fullName());
        self::assertSame('123456789', $member->documentNumber()->value());
    }

    public function test_duplicated_document_is_rejected(): void
    {
        $this->create->create('Ana Torres', '123456789');

        $this->expectException(DocumentTaken::class);
        $this->create->create('Otra Persona', '123456789');
    }

    public function test_search_matches_name_or_document_and_is_paginated(): void
    {
        $this->create->create('Ana Torres', '123456789');
        $this->create->create('Luis Rojas', '987654321');

        $byName = $this->search->search('torres', 1, 20);
        self::assertSame(1, $byName->total());

        $byDocument = $this->search->search('987', 1, 20);
        self::assertSame(1, $byDocument->total());

        $all = $this->search->search(null, 1, 1);
        self::assertSame(2, $all->total());
        self::assertCount(1, $all->items());
    }

    public function test_detail_includes_the_count_of_unreturned_lines(): void
    {
        $member = $this->create->create('Ana Torres', '123456789');
        $book = (new CreateBookService(new InMemoryBookRepository()))
            ->create('Título', 'Autor', '9780307474728', 'cat-1', 1);
        $clock = FakeClock::at('2026-10-13T12:00:00Z');
        $loan = Loan::register('loan-1', $member->id(), 'user-1', $clock->now());
        $loan->addItem('item-1', $book, 'Novela');
        $this->loans->add($loan);

        $detail = $this->get->get($member->id());

        self::assertSame($member->id(), $detail['member']->id());
        self::assertSame(1, $detail['active_loans']);
    }

    public function test_missing_member_is_not_found(): void
    {
        $this->expectException(NotFound::class);

        $this->get->get('no-existe');
    }
}
