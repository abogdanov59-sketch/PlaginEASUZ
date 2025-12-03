<?php

declare(strict_types=1);

namespace RegistryService\Infrastructure\Extension\Easuz\Application\Command;

use RegistryService\Application\Service\HttpClientServiceInterface;
use RegistryService\Domain\Repository\UserQueryRepositoryInterface;
use RegistryService\Infrastructure\Extension\Easuz\Application\Service\EasuzTransportService;
use RuntimeException;

/**
 * Плагин для отправки подготовленного XML в СУЗ-44 и передачи результата на парсинг.
 */
class EasuzIntegrationCommand
{
    private EasuzTransportService $transportService;

    private UserQueryRepositoryInterface $userQueryRepository;

    private HttpClientServiceInterface $httpClient;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    public function __construct(
        EasuzTransportService $transportService,
        UserQueryRepositoryInterface $userQueryRepository,
        HttpClientServiceInterface $httpClient,
        array $config = []
    ) {
        $this->transportService = $transportService;
        $this->userQueryRepository = $userQueryRepository;
        $this->httpClient = $httpClient;
        $this->config = $config;
    }

    /**
     * Выполнение интеграционного запроса к СУЗ-44
     *
     * @param string      $operationCode  Код операции СУЗ-44 (GetPurchasePublishedList и др.)
     * @param string      $requestId      Внутренний идентификатор запроса
     * @param string      $xmlFilePath    Абсолютный путь к исходящему XML-файлу
     * @param int|null    $recordId       ID записи реестра (при запуске из карточки)
     * @param int|null    $registryId     ID реестра (при запуске из карточки)
     * @param array|null  $options        Дополнительные опции
     */
    public function execute(
        string $operationCode,
        string $requestId,
        string $xmlFilePath,
        ?int $recordId = null,
        ?int $registryId = null,
        ?array $options = null
    ) {
        $options = $options ?? [];
        $this->validateInput($operationCode, $requestId, $xmlFilePath);

        $xmlPayload = $this->readXmlFile($xmlFilePath);
        $responseXml = $this->transportService->call($operationCode, $xmlPayload);
        $responseFilePath = $this->saveResponse($options, $requestId, $operationCode, $responseXml);

        $this->notifyParsingService($options, $operationCode, $requestId, $recordId, $registryId, $responseFilePath);

        return [
            'status' => 'success',
            'response_file_path' => $responseFilePath,
        ];
    }

    private function validateInput(string $operationCode, string $requestId, string $xmlFilePath): void
    {
        if ($operationCode === '') {
            throw new RuntimeException('operationCode не задан');
        }

        if ($requestId === '') {
            throw new RuntimeException('requestId не задан');
        }

        if ($xmlFilePath === '' || !is_readable($xmlFilePath)) {
            throw new RuntimeException('Исходящий XML-файл не найден или недоступен для чтения: ' . $xmlFilePath);
        }
    }

    private function readXmlFile(string $xmlFilePath): string
    {
        $payload = file_get_contents($xmlFilePath);
        if ($payload === false) {
            throw new RuntimeException('Не удалось прочитать файл: ' . $xmlFilePath);
        }

        return $payload;
    }

    private function saveResponse(array $options, string $requestId, string $operationCode, string $responseXml): string
    {
        $directory = $options['save_response_dir'] ?? $this->config['default_response_dir'] ?? sys_get_temp_dir();
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Не удалось создать каталог для сохранения ответа: ' . $directory);
        }

        $fileName = sprintf('%s_%s_response.xml', $requestId, $operationCode);
        $filePath = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

        if (file_put_contents($filePath, $responseXml) === false) {
            throw new RuntimeException('Не удалось сохранить ответ СУЗ-44 в файл: ' . $filePath);
        }

        return $filePath;
    }

    private function notifyParsingService(
        array $options,
        string $operationCode,
        string $requestId,
        ?int $recordId,
        ?int $registryId,
        string $responseFilePath
    ): void {
        $apiKey = $this->userQueryRepository->findSystemApiKey();
        if (empty($apiKey)) {
            throw new RuntimeException('Системный API-ключ не найден');
        }

        $payload = [
            'operation_code' => $operationCode,
            'request_id' => $requestId,
            'record_id' => $recordId,
            'registry_id' => $registryId,
            'response_file_path' => $responseFilePath,
        ];

        $relativeUrl = $options['parse_service_relative_url'] ?? $this->config['parser_relative_url'] ?? '/xmlparser/parse';
        $url = rtrim((string)($this->config['parser_base_url'] ?? ''), '/') . '/' . ltrim((string)$relativeUrl, '/');

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $apiKey,
            ],
            'json' => $payload,
            'timeout' => $this->config['timeout'] ?? 30,
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException(sprintf(
                'Сервис парсинга вернул ошибку %d: %s',
                $statusCode,
                (string)$response->getBody()
            ));
        }
    }
}
