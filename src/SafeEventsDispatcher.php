<?php

declare(strict_types=1);

namespace Bentools\DoctrineSafeEvents;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use SplObjectStorage;

final class SafeEventsDispatcher implements EventSubscriber
{
    /**
     * @var SplObjectStorage<PostPersistEventArgs>
     */
    private SplObjectStorage $postPersists;

    /**
     * @var SplObjectStorage<PostUpdateEventArgs>
     */
    private SplObjectStorage $postUpdates;

    /**
     * @var SplObjectStorage<PostRemoveEventArgs>
     */
    private SplObjectStorage $postRemoves;

    public function __construct()
    {
        $this->postPersists = new SplObjectStorage();
        $this->postUpdates = new SplObjectStorage();
        $this->postRemoves = new SplObjectStorage();
    }

    public function reset(): void
    {
        $this->postPersists->removeAll($this->postPersists);
        $this->postUpdates->removeAll($this->postUpdates);
        $this->postRemoves->removeAll($this->postRemoves);
    }

    public function postPersist(/* PostPersistEventArgs */ LifecycleEventArgs $eventArgs): void
    {
        $this->postPersists->attach($eventArgs);
    }

    public function postUpdate(/* PostUpdateEventArgs */ LifecycleEventArgs $eventArgs): void
    {
        $this->postUpdates->attach($eventArgs);
    }

    public function postRemove(/* PostRemoveEventArgs */ LifecycleEventArgs $eventArgs): void
    {
        $this->postRemoves->attach($eventArgs);
    }

    public function postFlush(PostFlushEventArgs $eventArgs): void
    {
        $eventManager = $eventArgs->getObjectManager()->getEventManager();

        $clear = new SplObjectStorage();
        foreach ($this->postPersists as $postPersist) {
            if ($eventArgs->getObjectManager() === $postPersist->getObjectManager()) {
                $eventManager->dispatchEvent(SafeEvents::POST_PERSIST, $postPersist);
                $clear->attach($postPersist);
            }
        }
        foreach ($this->postUpdates as $postUpdate) {
            if ($eventArgs->getObjectManager() === $postUpdate->getObjectManager()) {
                $eventManager->dispatchEvent(SafeEvents::POST_UPDATE, $postUpdate);
                $clear->attach($postUpdate);
            }
        }
        foreach ($this->postRemoves as $postRemove) {
            if ($eventArgs->getObjectManager() === $postRemove->getObjectManager()) {
                $eventManager->dispatchEvent(SafeEvents::POST_REMOVE, $postRemove);
                $clear->attach($postRemove);
            }
        }

        $this->postPersists->removeAll($clear);
        $this->postUpdates->removeAll($clear);
        $this->postRemoves->removeAll($clear);
        $clear->removeAll($clear);
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
            Events::postFlush,
        ];
    }
}
