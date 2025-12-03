<?php

declare(strict_types=1);

namespace RegistryService\Infrastructure\Extension\Easuz\Domain\Model\Entity;

/**
 * Сущность авторизационного токена СУЗ-44.
 */
class EasuzToken
{
    /**
     * @var int
     */
    private $id;

    /**
     * Строка токена авторизации СУЗ-44
     *
     * @var string
     */
    private string $value;

    /**
     * Дата/время получения токена
     *
     * @var \DateTimeImmutable
     */
    private \DateTimeImmutable $createdAt;

    /**
     * Дата/время предполагаемого истечения токена
     *
     * @var \DateTimeImmutable|null
     */
    private ?\DateTimeImmutable $expiresAt = null;

    /**
     * Признак актуальности токена
     *
     * @var bool
     */
    private bool $isActive = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }
}
