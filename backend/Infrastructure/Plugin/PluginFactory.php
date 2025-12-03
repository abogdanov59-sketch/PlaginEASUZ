<?php

declare(strict_types=1);

namespace RegistryService\Infrastructure\Extension\Easuz\Infrastructure\Plugin;

use Phalcon\Di;
use RegistryService\Domain\Service\PluginFactoryInterface;
use RegistryService\Infrastructure\Extension\Easuz\Application\Command\EasuzIntegrationCommand;
use RegistryService\Infrastructure\Plugin\Exception\PluginNotFoundException;

/**
 * Фабрика плагинов расширения Easuz.
 */
class PluginFactory implements PluginFactoryInterface
{
    public function create(string $pluginName, array $payload = [])
    {
        switch ($pluginName) {
            case 'EasuzIntegrationCommand':
                $plugin = new EasuzIntegrationCommand(
                    Di::getDefault()->get('easuz_transport_service'),
                    Di::getDefault()->get('user_query_repository'),
                    Di::getDefault()->get('http_client'),
                    Di::getDefault()->get('easuz_integration_config')
                );
                break;
            default:
                throw new PluginNotFoundException(sprintf('Плагин %s не найден', $pluginName));
        }

        return $plugin;
    }
}
