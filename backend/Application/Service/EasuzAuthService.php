<?php

declare(strict_types=1);

namespace RegistryService\Infrastructure\Extension\Easuz\Application\Service;

use DateInterval;
use DateTimeImmutable;
use RegistryService\Infrastructure\Extension\Easuz\Domain\Model\Entity\EasuzToken;
use RegistryService\Infrastructure\Extension\Easuz\Domain\Repository\Entity\EasuzTokenRepositoryInterface;
use RuntimeException;
use SimpleXMLElement;

/**
 * Сервис получения и хранения токена авторизации СУЗ-44.
 */
class EasuzAuthService
{
    private EasuzTokenRepositoryInterface $tokenRepository;

    private EasuzTransportService $transportService;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    public function __construct(
        EasuzTokenRepositoryInterface $tokenRepository,
        EasuzTransportService $transportService,
        array $config
    ) {
        $this->tokenRepository = $tokenRepository;
        $this->transportService = $transportService;
        $this->config = $config;
    }

    /**
     * Получение валидного токена для вызовов СУЗ-44.
     */
    public function getValidToken(): string
    {
        $token = $this->tokenRepository->getActiveToken();
        if ($token !== null && !$this->isExpired($token)) {
            return $token->getValue();
        }

        return $this->refreshToken();
    }

    private function refreshToken(): string
    {
        $credentials = $this->config['token_credentials'] ?? [];
        if (empty($credentials['login']) || empty($credentials['password'])) {
            throw new RuntimeException('Не заданы учетные данные для получения токена СУЗ-44');
        }

        $xmlPayload = $this->buildGetTokenRequest((string)$credentials['login'], (string)$credentials['password']);
        $responseXml = $this->transportService->callGetToken($xmlPayload);
        $tokenValue = $this->extractTokenValue($responseXml);

        if ($tokenValue === '') {
            throw new RuntimeException('СУЗ-44 вернул пустой токен');
        }

        $this->tokenRepository->deactivateAllTokens();

        $token = new EasuzToken();
        $token
            ->setValue($tokenValue)
            ->setCreatedAt(new DateTimeImmutable())
            ->setExpiresAt($this->calculateExpiresAt())
            ->setIsActive(true);

        $this->tokenRepository->insert($token);

        return $tokenValue;
    }

    private function buildGetTokenRequest(string $login, string $password): string
    {
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>\n<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">\n  <soap:Body>\n    <GetToken xmlns="http://tempuri.org/">\n      <login>%s</login>\n      <password>%s</password>\n    </GetToken>\n  </soap:Body>\n</soap:Envelope>',
            htmlspecialchars($login, ENT_XML1),
            htmlspecialchars($password, ENT_XML1)
        );
    }

    private function extractTokenValue(string $responseXml): string
    {
        try {
            $xml = new SimpleXMLElement($responseXml);
            $namespaces = $xml->getDocNamespaces(true);
            $body = $xml->children($namespaces['soap'] ?? null)->Body ?? $xml->children()->Body;
            if ($body === null) {
                return '';
            }

            $tokenNode = $body->xpath('//token') ?: $body->xpath('//Token');
            if ($tokenNode !== false && isset($tokenNode[0])) {
                return trim((string)$tokenNode[0]);
            }
        } catch (\Throwable $exception) {
            throw new RuntimeException('Не удалось распарсить ответ GetToken: ' . $exception->getMessage(), 0, $exception);
        }

        return '';
    }

    private function isExpired(EasuzToken $token): bool
    {
        $now = new DateTimeImmutable();
        $expiresAt = $token->getExpiresAt();

        if ($expiresAt !== null) {
            return $expiresAt <= $now;
        }

        $ttlMinutes = (int)($this->config['token_ttl_minutes'] ?? 0);
        if ($ttlMinutes > 0) {
            $calculated = $token->getCreatedAt()->add(new DateInterval('PT' . $ttlMinutes . 'M'));

            return $calculated <= $now;
        }

        return false;
    }

    private function calculateExpiresAt(): ?DateTimeImmutable
    {
        $ttlMinutes = (int)($this->config['token_ttl_minutes'] ?? 0);
        if ($ttlMinutes <= 0) {
            return null;
        }

        return (new DateTimeImmutable())->add(new DateInterval('PT' . $ttlMinutes . 'M'));
    }
}
