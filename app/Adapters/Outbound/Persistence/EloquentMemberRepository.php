<?php

declare(strict_types=1);

namespace App\Adapters\Outbound\Persistence;

use App\Adapters\Outbound\Persistence\Models\MemberModel;
use App\Application\Ports\Outbound\MemberRepository;
use App\Application\Ports\Outbound\Support\Page;
use App\Domain\DocumentNumber;
use App\Domain\Member;

final class EloquentMemberRepository implements MemberRepository
{
    public function findById(string $id): ?Member
    {
        $row = MemberModel::query()->find($id);

        return $row === null ? null : $this->toDomain($row);
    }

    public function add(Member $member): void
    {
        MemberModel::query()->insert([
            'id' => $member->id(),
            'full_name' => $member->fullName(),
            'document_number' => $member->documentNumber()->value(),
        ]);
    }

    public function existsByDocument(string $documentNumber): bool
    {
        return MemberModel::query()->where('document_number', $documentNumber)->exists();
    }

    public function search(?string $query, int $page, int $size): Page
    {
        $builder = MemberModel::query();
        if ($query !== null && $query !== '') {
            $needle = '%' . $query . '%';
            $builder->where(function ($inner) use ($needle): void {
                $inner->whereRaw('full_name ILIKE ?', [$needle])
                    ->orWhereRaw('document_number ILIKE ?', [$needle]);
            });
        }

        $total = (clone $builder)->count();
        $rows = $builder->orderBy('full_name')
            ->offset(($page - 1) * $size)
            ->limit($size)
            ->get();

        return new Page(
            $rows->map(fn (MemberModel $row): Member => $this->toDomain($row))->all(),
            $total,
            $page,
            $size,
        );
    }

    private function toDomain(MemberModel $row): Member
    {
        return Member::reconstitute(
            (string) $row->id,
            (string) $row->full_name,
            DocumentNumber::of((string) $row->document_number),
        );
    }
}
