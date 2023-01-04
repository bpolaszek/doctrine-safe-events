<?php

declare(strict_types=1);

namespace Bentools\DoctrineSafeEvents\Tests;

use Noback\PHPUnitTestServiceContainer\PHPUnit\TestCaseWithEntityManager;

trait EntityManagerAwareTestCase
{
    use TestCaseWithEntityManager;

    protected function getEntityDirectories(): array
    {
        return [__DIR__ . '/Entity'];
    }
}
