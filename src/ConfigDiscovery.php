<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller;

use DirectoryIterator;
use Laminas\ComponentInstaller\ConfigDiscovery\DiscoveryChainInterface;
use Laminas\ComponentInstaller\Injector\InjectorInterface;

use function array_search;
use function array_splice;
use function array_unshift;
use function is_array;
use function is_dir;
use function sort;
use function str_ends_with;

/**
 * @internal
 */
final class ConfigDiscovery
{
    /**
     * Map of known configuration files and their locators.
     */
    private const DISCOVERY = [
        'config/application.config.php'      => ConfigDiscovery\ApplicationConfig::class,
        'config/modules.config.php'          => ConfigDiscovery\ModulesConfig::class,
        'config/development.config.php.dist' => [
            'dist'        => ConfigDiscovery\DevelopmentConfig::class,
            'work'        => ConfigDiscovery\DevelopmentWorkConfig::class,
            'mezzio-dist' => ConfigDiscovery\MezzioDevelopmentConfig::class,
            'mezzio-work' => ConfigDiscovery\MezzioDevelopmentWorkConfig::class,
        ],
        'config/config.php'                  => [
            'aggregator' => ConfigDiscovery\ConfigAggregator::class,
            'manager'    => ConfigDiscovery\MezzioConfig::class,
        ],
    ];

    /**
     * Map of config files to injectors
     */
    private const INJECTORS = [
        'config/application.config.php'      => Injector\ApplicationConfigInjector::class,
        'config/modules.config.php'          => Injector\ModulesConfigInjector::class,
        'config/development.config.php.dist' => [
            'dist'        => Injector\DevelopmentConfigInjector::class,
            'work'        => Injector\DevelopmentWorkConfigInjector::class,
            'mezzio-dist' => Injector\MezzioDevelopmentConfigInjector::class,
            'mezzio-work' => Injector\MezzioDevelopmentWorkConfigInjector::class,
        ],
        'config/config.php'                  => [
            'aggregator' => Injector\ConfigAggregatorInjector::class,
            'manager'    => Injector\MezzioConfigInjector::class,
        ],
    ];

    /**
     * Return a list of available configuration options.
     *
     * @template TKey of array-key
     * @param Collection<TKey,InjectorInterface::TYPE_*> $availableTypes Collection of injector type
     *     constants indicating valid package types that could be injected.
     * @param string $projectRoot Path to the project root; assumes PWD by default.
     * @return Collection<int,ConfigOption> Collection of ConfigOption instances.
     */
    public function getAvailableConfigOptions(Collection $availableTypes, string $projectRoot = ''): Collection
    {
        $options = (new Collection(self::DISCOVERY))
            // Create a discovery class for the discovery type
            ->map(
                static function ($discoveryClass) use ($projectRoot): DiscoveryChainInterface {
                    if (! is_array($discoveryClass)) {
                        $discoveryClass = ['generic' => $discoveryClass];
                    }

                    return new ConfigDiscovery\DiscoveryChain($discoveryClass, $projectRoot);
                }
            )
            // Use only those where we can locate a corresponding config file
            ->filter(static fn(DiscoveryChainInterface $discovery) => $discovery->locate())
            // Create an injector for the config file
            ->map(function (DiscoveryChainInterface $discovery, string $file) use ($projectRoot, $availableTypes) {
                // Look up the injector based on the file type
                $injectorClass = self::INJECTORS[$file];
                if (is_array($injectorClass)) {
                    return new Injector\ConfigInjectorChain(
                        $injectorClass,
                        $discovery,
                        $availableTypes,
                        $projectRoot
                    );
                }
                return new $injectorClass($projectRoot);
            })
            // Keep only those injectors that match types available for the package
            ->filter(static fn($injector) => $availableTypes->anySatisfies(
                static fn($type) => $injector->registersType($type)
            ))
            // Create a config option using the file and injector
            ->map(static fn ($injector, $file) => new ConfigOption($file, $injector))
            ->toOrderedCollection();

        $options->merge($this->getDapurConfigOptions($availableTypes, $projectRoot));

        if ($options->isEmpty()) {
            return new Collection([]);
        }

        return new Collection([
            new ConfigOption('Do not inject', new Injector\NoopInjector()),
            ...$options->toArray(),
        ]);
    }

    /**
     * Discover supported configuration files in Dapur's container/config directory.
     *
     * @template TKey of array-key
     * @param Collection<TKey,InjectorInterface::TYPE_*> $availableTypes
     * @return Collection<string,ConfigOption>
     */
    private function getDapurConfigOptions(Collection $availableTypes, string $projectRoot): Collection
    {
        $configDirectory = ($projectRoot !== '' ? $projectRoot . '/' : '')
            . 'container/config';

        if (! is_dir($configDirectory)) {
            return new Collection([]);
        }

        $configFiles = [];
        foreach (new DirectoryIterator($configDirectory) as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            $configFiles[] = 'container/config/' . $file->getFilename();
        }

        sort($configFiles);

        $defaultConfigIndex = array_search(
            Injector\DapurConfigAggregatorInjector::DEFAULT_CONFIG_FILE,
            $configFiles,
            true
        );
        if ($defaultConfigIndex !== false) {
            $defaultConfig = $configFiles[$defaultConfigIndex];
            array_splice($configFiles, $defaultConfigIndex, 1);
            array_unshift($configFiles, $defaultConfig);
        }

        $options = [];
        foreach ($configFiles as $configFile) {
            $discovery = new ConfigDiscovery\DiscoveryChain(
                [
                    'aggregator' => ConfigDiscovery\DapurConfigAggregator::class,
                    'manager'    => ConfigDiscovery\DapurConfig::class,
                ],
                $projectRoot,
                $configFile
            );

            if (! $discovery->locate()) {
                continue;
            }

            $injector = new Injector\ConfigInjectorChain(
                [
                    'aggregator' => Injector\DapurConfigAggregatorInjector::class,
                    'manager'    => Injector\DapurConfigInjector::class,
                ],
                $discovery,
                $availableTypes,
                $projectRoot,
                $configFile
            );

            if (! $availableTypes->anySatisfies(
                static fn(int $type) => $injector->registersType($type)
            )) {
                continue;
            }

            $options[$configFile] = new ConfigOption($configFile, $injector);
        }

        return new Collection($options);
    }
}
