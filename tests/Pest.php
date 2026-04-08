<?php

declare(strict_types=1);

namespace Bentools\DoctrineSafeEvents\Tests;

use function uses;

uses(EntityManagerAwareTestCase::class)
    ->beforeEach(fn () => $this->setUpEntityManager())
    ->afterEach(fn () => $this->tearDownEntityManager())
    ->in(__DIR__);
