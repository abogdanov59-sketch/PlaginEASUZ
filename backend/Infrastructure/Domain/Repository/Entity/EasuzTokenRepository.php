<?php

declare(strict_types=1);

namespace RegistryService\Infrastructure\Extension\Easuz\Infrastructure\Domain\Repository\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use RegistryService\Infrastructure\Extension\Easuz\Domain\Model\Entity\EasuzToken;
use RegistryService\Infrastructure\Extension\Easuz\Domain\Repository\Entity\EasuzTokenRepositoryInterface;

class EasuzTokenRepository implements EasuzTokenRepositoryInterface
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(EasuzToken::class);
    }

    public function getActiveToken(): ?EasuzToken
    {
        return $this->repository->findOneBy([
            'isActive' => true,
        ]);
    }

    public function insert(EasuzToken $token): void
    {
        $this->entityManager->persist($token);
        $this->entityManager->flush();
    }

    public function deactivateAllTokens(): void
    {
        $this->entityManager
            ->createQueryBuilder()
            ->update(EasuzToken::class, 'token')
            ->set('token.isActive', ':inactive')
            ->setParameter('inactive', false)
            ->getQuery()
            ->execute();
    }
}
