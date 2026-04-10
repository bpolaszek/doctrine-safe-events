<?php

declare(strict_types=1);

namespace Bentools\DoctrineSafeEvents\Tests;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;

trait EntityManagerAwareTestCase
{
    private EntityManagerInterface $entityManager;

    public function setUpEntityManager(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: $this->getEntityDirectories(),
            isDevMode: true,
        );

        $connection = DriverManager::getConnection(
            params: ['driver' => 'pdo_sqlite', 'memory' => true],
            config: $config,
        );

        $this->entityManager = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema(
            $this->entityManager->getMetadataFactory()->getAllMetadata()
        );
    }

    public function tearDownEntityManager(): void
    {
        $this->entityManager->close();
    }

    protected function getEntityDirectories(): array
    {
        return [__DIR__ . '/Entity'];
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}
