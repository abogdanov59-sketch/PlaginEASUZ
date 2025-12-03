<?php

declare(strict_types=1);

namespace RegistryService\Infrastructure\Extension\Easuz\Domain\Repository\Entity;

use RegistryService\Infrastructure\Extension\Easuz\Domain\Model\Entity\EasuzToken;

interface EasuzTokenRepositoryInterface
{
    public function getActiveToken(): ?EasuzToken;

    public function insert(EasuzToken $token): void;

    public function deactivateAllTokens(): void;
}
