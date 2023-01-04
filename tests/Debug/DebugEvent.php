<?php

declare(strict_types=1);

namespace Bentools\DoctrineSafeEvents\Tests\Debug;

use DateTimeImmutable;

use function usort;

final class DebugEvent
{
    public string $name;
    public DateTimeImmutable $firedAt;
    public array $args; // @phpstan-ignore-line

    // @phpstan-ignore-next-line
    public function __construct(string $name, DateTimeImmutable $firedAt, array $args)
    {
        $this->name = $name;
        $this->firedAt = $firedAt;
        $this->args = $args;
    }

    // @phpstan-ignore-next-line
    public static function sort(self ...$events): array
    {
        usort($events, static fn (self $a, self $b) => $a->firedAt <=> $b->firedAt);

        return $events;
    }
}
