[![CI Workflow](https://github.com/bpolaszek/doctrine-safe-events/actions/workflows/ci.yml/badge.svg)](https://github.com/bpolaszek/doctrine-safe-events/actions/workflows/ci.yml)
[![Coverage](https://codecov.io/gh/bpolaszek/doctrine-safe-events/branch/main/graph/badge.svg?token=o2kCCIuXz3)](https://codecov.io/gh/bpolaszek/doctrine-safe-events)

# Doctrine safe post* events

Doctrine's [postPersist, postUpdate and postRemove](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/events.html#reference-events-post-update-remove-persist) events
are fired when the corresponding SQL queries (INSERT / UPDATE / DELETE) have been performed against the database server.

What happens under the hood is that Doctrine creates a wrapping transaction, runs SQL queries, then commits the transaction.

However, these events are fired _immediately_, e.g. not once the transaction is **complete**, which means:

- If the wrapping transaction fails, events have **already been fired anyway** (meaning you trusted generated primary key values, although they're going to be rolled back)
- If the wrapping transaction takes some time (typically during row locks), you get the inserted / updated / deleted information **before it's actually done**
(meaning if you run some async process once those events are triggered, you end up in processing not data which is not **up-to-date**)

## Background

The idea of this repository indeed came up with the following issue:
- An entity is persisted, then `$em->flush()` is called
- A `postPersist` event listener gets the entity's id, then asks a worker to do some async processing through [Symfony Messenger](https://symfony.com/doc/current/messenger.html)
- The worker queries database against the entity's id, and gets an `EntityNotFound` exception (the `COMMIT` did not happen yet)
- The flush operation on the main thread completes, and the `postFlush` event is fired (but it does not contain the inserted / updated / deleted entities)

## Our solution

If you run into the same kind of issues, you can replace your listeners' listened events in favor of:

- `Bentools\DoctrineSafeEvents\SafeEvents::POST_PERSIST` (and implement `safePostPersist` as a replacement of `postPersist`)
- `Bentools\DoctrineSafeEvents\SafeEvents::POST_UPDATE` (and implement `safePostUpdate` as a replacement of `postUpdate`)
- `Bentools\DoctrineSafeEvents\SafeEvents::POST_REMOVE` (and implement `safePostRemove` as a replacement of `postRemove`)

Basically, this library will collect entities which are scheduled for insertion / update / deletion, except it will delay event firing until the `postFlush` occurs.

## Installation

```bash
composer require bentools/doctrine-safe-events
```

### Usage in Symfony

Although this library has no dependency on Symfony, you can easily use it in your Symfony project:

```yaml
Bentools\DoctrineSafeEvents\SafeEventsDispatcher:
    tags:
        - { name: 'doctrine.event_subscriber' }
        - { name: 'kernel.reset', method: 'reset' }
```

#### Example usage

```php
declare(strict_types=1);

namespace App;

use Bentools\DoctrineSafeEvents\SafeEvents;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsDoctrineListener(SafeEvents::POST_PERSIST)]
final class SomeListener
{
    public function safePostPersist(LifecycleEventArgs $eventArgs): void
    {
        // ...
    }
}
```

## Tests

```bash
composer test
```

## License
MIT.
