<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\ConfigDiscovery;

use Laminas\ComponentInstaller\Collection;

/**
 * @internal
 */
final class DiscoveryChain implements DiscoveryChainInterface
{
    /**
     * Discovery Collection
     *
     * @var Collection<string,DiscoveryInterface>
     */
    protected Collection $chain;

    /**
     * Optionally specify project directory; $configFile will be relative to
     * this value.
     *
     * @param array<string,class-string<DiscoveryInterface>> $discovery
     * @param non-empty-string|null $configFile
     */
    public function __construct(
        array $discovery,
        string $projectDirectory = '',
        private ?string $configFile = null
    ) {
        $this->chain = (new Collection($discovery))
            // Create a discovery class for the discovery type
            ->map(function (string $discoveryClass) use ($projectDirectory) {
                return $this->configFile === null
                    ? new $discoveryClass($projectDirectory)
                    : new $discoveryClass($projectDirectory, $this->configFile);
            })
            // Use only those where we can locate a corresponding config file
            ->filter(static fn(DiscoveryInterface $discovery) => $discovery->locate());
    }

    /**
     * {@inheritDoc}
     */
    public function locate(): bool
    {
        return $this->chain->count() > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function discoveryExists(string $name): bool
    {
        return $this->chain->has($name);
    }
}
