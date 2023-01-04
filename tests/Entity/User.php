<?php

declare(strict_types=1);

namespace Bentools\DoctrineSafeEvents\Tests\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * @Entity
 */
class User
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public int $id;

    /**
     * @Column(type="string")
     */
    public string $name;

    public function __construct(?string $name = null)
    {
        if (null !== $name) {
            $this->name = $name;
        }
    }
}
