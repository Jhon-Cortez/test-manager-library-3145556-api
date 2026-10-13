<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

use App\Application\Ports\Outbound\Support\Page;
use App\Domain\Member;

interface MemberRepository
{
    public function findById(string $id): ?Member;

    public function add(Member $member): void;

    public function existsByDocument(string $documentNumber): bool;

    public function search(?string $query, int $page, int $size): Page;
}
