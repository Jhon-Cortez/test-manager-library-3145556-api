<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Ports\Outbound\MemberRepository;
use App\Application\Ports\Outbound\Support\Page;

final class SearchMembersService
{
    public const MAX_PAGE_SIZE = 50;

    public function __construct(private readonly MemberRepository $members)
    {
    }

    public function search(?string $query, int $page = 1, int $size = 20): Page
    {
        $page = max(1, $page);
        $size = min(max(1, $size), self::MAX_PAGE_SIZE);

        return $this->members->search($query, $page, $size);
    }
}
