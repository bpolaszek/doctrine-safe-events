<?php

declare(strict_types=1);

namespace Bentools\DoctrineSafeEvents\Tests;

use Bentools\DoctrineSafeEvents\SafeEvents;
use Bentools\DoctrineSafeEvents\SafeEventsDispatcher;
use Bentools\DoctrineSafeEvents\Tests\Debug\DebugEvent;
use Bentools\DoctrineSafeEvents\Tests\Debug\DebugEventSubscriber;
use Bentools\DoctrineSafeEvents\Tests\Entity\User;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Events;
use ReflectionProperty;
use SplObjectStorage;

use function array_map;
use function expect;

it('fires safePostPersist events once postFlush has been called', function () {
    // Background
    $em = $this->getEntityManager();
    /** @var EventManager $eventManager */
    $eventManager = $em->getEventManager();
    $postFlushDebugger = new DebugEventSubscriber([Events::postFlush]);
    $eventManager->addEventSubscriber($postFlushDebugger);
    $eventManager->addEventSubscriber(new SafeEventsDispatcher());

    // Given
    $appEventsSubscriber = new DebugEventSubscriber([
        Events::postPersist,
        SafeEvents::POST_PERSIST,
    ]);
    $eventManager->addEventSubscriber($appEventsSubscriber);
    $bob = new User('Bob');
    $joe = new User('Joe');
    $em->persist($bob);
    $em->persist($joe);

    // When
    $em->flush();

    // Then
    $receivedEvents = DebugEvent::sort(...$postFlushDebugger->receivedEvents, ...$appEventsSubscriber->receivedEvents);
    $receivedEventNames = array_map(fn (DebugEvent $event) => $event->name, $receivedEvents);

    expect($receivedEventNames)->toBe([
        Events::postPersist,
        Events::postPersist,
        Events::postFlush,
        SafeEvents::POST_PERSIST,
        SafeEvents::POST_PERSIST,
    ])
        ->and($receivedEvents[0]->args[0]->getObject())->toBe($bob)
        ->and($receivedEvents[1]->args[0]->getObject())->toBe($joe)
        ->and($receivedEvents[3]->args[0]->getObject())->toBe($bob)
        ->and($receivedEvents[4]->args[0]->getObject())->toBe($joe);
});

it('fires safePostUpdate events once postFlush has been called', function () {
    // Background
    $em = $this->getEntityManager();
    /** @var EventManager $eventManager */
    $eventManager = $em->getEventManager();
    $postFlushDebugger = new DebugEventSubscriber([Events::postFlush]);
    $eventManager->addEventSubscriber($postFlushDebugger);
    $eventManager->addEventSubscriber(new SafeEventsDispatcher());

    // Given
    $appEventsSubscriber = new DebugEventSubscriber([
        Events::postUpdate,
        SafeEvents::POST_UPDATE,
    ]);
    $eventManager->addEventSubscriber($appEventsSubscriber);
    $bob = new User('Bob');
    $joe = new User('Joe');
    $em->persist($bob);
    $em->persist($joe);
    $em->flush();
    $postFlushDebugger->clear();
    $appEventsSubscriber->clear();

    // When
    $joe->name = 'Joe Cocker';
    $em->flush();

    // Then
    $receivedEvents = DebugEvent::sort(...$postFlushDebugger->receivedEvents, ...$appEventsSubscriber->receivedEvents);
    $receivedEventNames = array_map(fn (DebugEvent $event) => $event->name, $receivedEvents);

    expect($receivedEventNames)->toBe([
        Events::postUpdate,
        Events::postFlush,
        SafeEvents::POST_UPDATE,
    ])
        ->and($receivedEvents[0]->args[0]->getObject())->toBe($joe)
        ->and($receivedEvents[2]->args[0]->getObject())->toBe($joe);
});

it('fires safePostRemove events once postFlush has been called', function () {
    // Background
    $em = $this->getEntityManager();
    /** @var EventManager $eventManager */
    $eventManager = $em->getEventManager();
    $postFlushDebugger = new DebugEventSubscriber([Events::postFlush]);
    $eventManager->addEventSubscriber($postFlushDebugger);
    $eventManager->addEventSubscriber(new SafeEventsDispatcher());

    // Given
    $appEventsSubscriber = new DebugEventSubscriber([
        Events::postRemove,
        SafeEvents::POST_REMOVE,
    ]);
    $eventManager->addEventSubscriber($appEventsSubscriber);
    $bob = new User('Bob');
    $joe = new User('Joe');
    $em->persist($bob);
    $em->persist($joe);
    $em->flush();
    $postFlushDebugger->clear();
    $appEventsSubscriber->clear();

    // When
    $em->remove($joe);
    $em->flush();

    // Then
    $receivedEvents = DebugEvent::sort(...$postFlushDebugger->receivedEvents, ...$appEventsSubscriber->receivedEvents);
    $receivedEventNames = array_map(fn (DebugEvent $event) => $event->name, $receivedEvents);

    expect($receivedEventNames)->toBe([
        Events::postRemove,
        Events::postFlush,
        SafeEvents::POST_REMOVE,
    ])
        ->and($receivedEvents[0]->args[0]->getObject())->toBe($joe)
        ->and($receivedEvents[2]->args[0]->getObject())->toBe($joe);
});

it('resets successfully', function () {
    $dispatcher = new SafeEventsDispatcher();
    $postPersistsRefl = new ReflectionProperty($dispatcher, 'postPersists');
    $postUpdatesRefl = new ReflectionProperty($dispatcher, 'postUpdates');
    $postRemovesRefl = new ReflectionProperty($dispatcher, 'postRemoves');
    $postPersistsRefl->setAccessible(true);
    $postUpdatesRefl->setAccessible(true);
    $postRemovesRefl->setAccessible(true);

    /** @var SplObjectStorage $postPersists */
    $postPersists = $postPersistsRefl->getValue($dispatcher);
    /** @var SplObjectStorage $postUpdates */
    $postUpdates = $postUpdatesRefl->getValue($dispatcher);
    /** @var SplObjectStorage $postRemoves */
    $postRemoves = $postRemovesRefl->getValue($dispatcher);

    // Background
    $postPersists->attach(new User('Bob'));
    $postUpdates->attach(new User('Alice'));
    $postRemoves->attach(new User('John'));

    // When
    $dispatcher->reset();

    // Then
    expect($postPersists)->toHaveCount(0)
        ->and($postUpdates)->toHaveCount(0)
        ->and($postRemoves)->toHaveCount(0);
});
