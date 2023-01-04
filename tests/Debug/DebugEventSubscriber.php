<?php

declare(strict_types=1);

namespace Bentools\DoctrineSafeEvents\Tests\Debug;

use DateTimeImmutable;
use Doctrine\Common\EventSubscriber;

use function microtime;
use function usleep;

final class DebugEventSubscriber implements EventSubscriber
{
    public array $receivedEvents = []; // @phpstan-ignore-line
    public array $subscribedEvents = []; // @phpstan-ignore-line

    // @phpstan-ignore-next-line
    public function __construct(array $subscribedEvents)
    {
        $this->subscribedEvents = $subscribedEvents;
    }

    public function clear(): void
    {
        $this->receivedEvents = [];
    }

    // @phpstan-ignore-next-line
    public function __call(string $method, array $args): void
    {
        $microtime = (string) microtime(true);
        $firedAt = DateTimeImmutable::createFromFormat('U.u', $microtime);
        $this->receivedEvents[] = new DebugEvent($method, $firedAt, $args); // @phpstan-ignore-line
        usleep(1000);
    }

    public function getSubscribedEvents(): array
    {
        return $this->subscribedEvents;
    }
}
