<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\ConfigDiscovery;

use function preg_quote;
use function sprintf;

/**
 * @internal
 */
final class DapurConfig extends AbstractDiscovery
{
    /**
     * Configuration file to look for.
     *
     * @var non-empty-string
     */
    protected string $configFile = 'container/config/global.php';

    /**
     * Expected pattern to match if the configuration file exists.
     *
     * Pattern is set in constructor to ensure PCRE quoting is correct.
     *
     * @var non-empty-string
     */
    protected string $expected;

    public function __construct(string $projectDirectory = '')
    {
        $this->expected = sprintf(
            '/new (?:%s?%s)?ConfigManager\(\s*(?:array\(|\[)/s',
            preg_quote('\\'),
            preg_quote('Mezzio\ConfigManager\\')
        );

        parent::__construct($projectDirectory);
    }
}
