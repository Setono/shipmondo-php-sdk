<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CollectionRequestOptionsTest extends TestCase
{
    #[Test]
    public function it_builds_with_defaults_via_the_named_constructor(): void
    {
        $options = CollectionRequestOptions::new();

        self::assertSame(1, $options->page);
        self::assertSame(20, $options->perPage);
        self::assertSame(['page' => 1, 'per_page' => 20], $options->toArray());
    }

    #[Test]
    public function it_returns_new_instances_from_the_withers_and_leaves_the_original_untouched(): void
    {
        $base = CollectionRequestOptions::new();

        $paged = $base->withPage(3);
        $sized = $base->withPerPage(50);

        self::assertSame(3, $paged->page);
        self::assertSame(20, $paged->perPage);
        self::assertSame(1, $sized->page);
        self::assertSame(50, $sized->perPage);

        // immutability: the original is untouched by either wither
        self::assertSame(1, $base->page);
        self::assertSame(20, $base->perPage);
    }

    #[Test]
    #[DataProvider('outOfRangeValues')]
    public function it_rejects_out_of_range_values(int $page, int $perPage): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CollectionRequestOptions($page, $perPage);
    }

    /**
     * @return \Generator<string, array{int, int}>
     */
    public static function outOfRangeValues(): \Generator
    {
        yield 'page below 1' => [0, 20];
        yield 'per page below 1' => [1, 0];
        yield 'per page above the 50 cap' => [1, 51];
    }
}
