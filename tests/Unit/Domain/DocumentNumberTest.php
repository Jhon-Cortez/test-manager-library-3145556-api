<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\DocumentNumber;
use App\Domain\Exceptions\InvalidValue;
use PHPUnit\Framework\TestCase;

final class DocumentNumberTest extends TestCase
{
    public function test_trims_the_document(): void
    {
        self::assertSame('123456789', DocumentNumber::of(' 123456789 ')->value());
    }

    public function test_rejects_empty_document(): void
    {
        $this->expectException(InvalidValue::class);

        DocumentNumber::of('   ');
    }
}
