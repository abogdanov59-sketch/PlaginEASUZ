<?php

declare(strict_types=1);

return [
    // Базовый URL сервисов СУЗ-44
    'base_url' => '__EASUZ_BASE_URL__',

    // Таймауты и количество повторных попыток
    'timeout' => 30,
    'retries' => 1,

    // Настройки токена
    'token_ttl_minutes' => 55,
    'token_credentials' => [
        'login' => '__EASUZ_LOGIN__',
        'password' => '__EASUZ_PASSWORD__',
    ],

    // Пути для операций СУЗ-44
    'operations' => [
        'GetToken' => [
            'path' => '/soap/GetToken',
            'soap_action' => 'GetToken',
            'requires_token' => false,
        ],
        'GetPurchasePublishedList' => [
            'path' => '/soap/GetPurchasePublishedList',
            'soap_action' => 'GetPurchasePublishedList',
            'requires_token' => true,
        ],
        'GetPurchaseByRegistryNumber' => [
            'path' => '/soap/GetPurchaseByRegistryNumber',
            'soap_action' => 'GetPurchaseByRegistryNumber',
            'requires_token' => true,
        ],
        'GetContractList' => [
            'path' => '/soap/GetContractList',
            'soap_action' => 'GetContractList',
            'requires_token' => true,
        ],
        'GetContractByRegistryNumber' => [
            'path' => '/soap/GetContractByRegistryNumber',
            'soap_action' => 'GetContractByRegistryNumber',
            'requires_token' => true,
        ],
        'GetKoz2ByCode' => [
            'path' => '/soap/GetKoz2ByCode',
            'soap_action' => 'GetKoz2ByCode',
            'requires_token' => true,
        ],
    ],

    // Сервис парсинга XML
    'parser_base_url' => '__PARSER_BASE_URL__',
    'parser_relative_url' => '/xmlparser/parse',

    // Путь по умолчанию для сохранения ответов
    'default_response_dir' => '/var/tmp/easuz',
];
