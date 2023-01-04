<?php

declare(strict_types=1);

namespace Bentools\DoctrineSafeEvents;

final class SafeEvents
{
    public const POST_PERSIST = 'safePostPersist';
    public const POST_UPDATE = 'safePostUpdate';
    public const POST_REMOVE = 'safePostRemove';
}
