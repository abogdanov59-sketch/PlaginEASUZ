<?php

declare(strict_types=1);

namespace Tests\Application\Service;

use PHPUnit\Framework\TestCase;
use RegistryService\Infrastructure\Extension\Easuz\Application\Service\EasuzAuthService;
use RegistryService\Infrastructure\Extension\Easuz\Application\Service\EasuzTransportService;
use RegistryService\Infrastructure\Extension\Easuz\Domain\Model\Entity\EasuzToken;
use RegistryService\Infrastructure\Extension\Easuz\Domain\Repository\Entity\EasuzTokenRepositoryInterface;
use RuntimeException;

/**
 * Базовый набор юнит-тестов для EasuzAuthService.
 */
class EasuzAuthServiceTest extends TestCase
{
    public function testThrowsExceptionWhenCredentialsMissing(): void
    {
        $repository = $this->createMock(EasuzTokenRepositoryInterface::class);
        $transport = $this->createMock(EasuzTransportService::class);

        $service = new EasuzAuthService($repository, $transport, []);

        $this->expectException(RuntimeException::class);
        $service->getValidToken();
    }

    public function testReturnsExistingActiveToken(): void
    {
        $token = (new EasuzToken())
            ->setValue('cached')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setIsActive(true);

        $repository = $this->createMock(EasuzTokenRepositoryInterface::class);
        $repository->method('getActiveToken')->willReturn($token);

        $transport = $this->createMock(EasuzTransportService::class);

        $service = new EasuzAuthService($repository, $transport, ['token_ttl_minutes' => 60]);

        self::assertSame('cached', $service->getValidToken());
    }
}
