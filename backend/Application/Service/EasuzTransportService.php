<?php

declare(strict_types=1);

namespace RegistryService\Infrastructure\Extension\Easuz\Application\Service;

use Psr\Http\Message\ResponseInterface;
use RegistryService\Application\Service\HttpClientServiceInterface;
use RuntimeException;

/**
 * Транспортный слой интеграции с СУЗ-44.
 */
class EasuzTransportService
{
    private HttpClientServiceInterface $httpClient;

    private EasuzAuthService $authService;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    public function __construct(
        HttpClientServiceInterface $httpClient,
        EasuzAuthService $authService,
        array $config
    ) {
        $this->httpClient = $httpClient;
        $this->authService = $authService;
        $this->config = $config;
    }

    /**
     * Универсальный вызов операций СУЗ-44.
     */
    public function call(string $operationCode, string $xmlPayload): string
    {
        if (!isset($this->config['operations'][$operationCode])) {
            throw new RuntimeException(sprintf('Неизвестный код операции СУЗ-44: %s', $operationCode));
        }

        $operation = $this->config['operations'][$operationCode];
        $url = $this->buildUrl($operation['path'] ?? '');
        $requiresToken = (bool)($operation['requires_token'] ?? false);

        $headers = [
            'Content-Type' => 'text/xml; charset=utf-8',
        ];

        if (!empty($operation['soap_action'])) {
            $headers['SOAPAction'] = $operation['soap_action'];
        }

        if ($requiresToken) {
            $headers['Authorization'] = sprintf('Bearer %s', $this->authService->getValidToken());
        }

        $response = $this->sendWithRetries($url, $xmlPayload, $headers);

        return $this->handleResponse($response);
    }

    /**
     * Специализированный вызов получения токена.
     */
    public function callGetToken(string $xmlPayload): string
    {
        $operation = $this->config['operations']['GetToken'] ?? null;
        if ($operation === null) {
            throw new RuntimeException('Настройки операции GetToken не заданы');
        }

        $url = $this->buildUrl($operation['path'] ?? '');
        $headers = [
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => $operation['soap_action'] ?? 'GetToken',
        ];

        $response = $this->sendWithRetries($url, $xmlPayload, $headers);

        return $this->handleResponse($response);
    }

    private function sendWithRetries(string $url, string $xmlPayload, array $headers): ResponseInterface
    {
        $retries = (int)($this->config['retries'] ?? 0);
        $attempt = 0;
        $lastException = null;

        while ($attempt <= $retries) {
            try {
                return $this->httpClient->request('POST', $url, [
                    'headers' => $headers,
                    'body' => $xmlPayload,
                    'timeout' => $this->config['timeout'] ?? 30,
                ]);
            } catch (\Throwable $exception) {
                $lastException = $exception;
                $attempt++;
            }
        }

        throw new RuntimeException(
            sprintf('Ошибка отправки запроса в СУЗ-44: %s', $lastException?->getMessage())
        );
    }

    private function handleResponse(ResponseInterface $response): string
    {
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException(sprintf('СУЗ-44 вернул статус %d: %s', $status, (string)$response->getBody()));
        }

        return (string)$response->getBody();
    }

    private function buildUrl(string $path): string
    {
        return rtrim((string)$this->config['base_url'], '/') . '/' . ltrim($path, '/');
    }
}
