<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Security;

use Composer\Composer;
use Neologik\ComposerGitInstaller\Model\ParsedUrl;
use Neologik\ComposerGitInstaller\Exception\SecurityException;

/**
 * Validates that the repository host is in the allowed whitelist.
 */
class HostWhitelistValidator implements ValidatorInterface
{
    private const DEFAULT_ALLOWED_HOSTS = [
        'github.com',
        'gitlab.com',
        'bitbucket.org',
    ];

    /**
     * @var string[]
     */
    private array $allowedHosts;

    public function __construct(private readonly Composer $composer)
    {
        $this->allowedHosts = $this->loadAllowedHosts();
    }

    /**
     * Validate that the host is in the whitelist.
     */
    public function validate(ParsedUrl $parsedUrl): void
    {
        $host = strtolower($parsedUrl->host);
        
        // Check exact match first
        if (in_array($host, $this->allowedHosts, true)) {
            return;
        }

        // Check subdomain matches for enterprise instances
        foreach ($this->allowedHosts as $allowedHost) {
            if ($this->isSubdomainOf($host, $allowedHost)) {
                return;
            }
        }

        throw new SecurityException(
            sprintf(
                'Host "%s" is not in the allowed whitelist. Allowed hosts: %s',
                $host,
                implode(', ', $this->allowedHosts)
            ),
            'HOST_NOT_WHITELISTED'
        );
    }

    /**
     * Get the name of this validator.
     */
    public function getName(): string
    {
        return 'Host Whitelist Validator';
    }

    /**
     * Load allowed hosts from Composer configuration or use defaults.
     */
    /**
     * @return string[]
     */
    private function loadAllowedHosts(): array
    {
        $config = $this->composer->getConfig();
        $configHosts = $config->get('git-installer-allowed-hosts');
        
        if (is_array($configHosts) && !empty($configHosts)) {
            return array_map('strtolower', $configHosts);
        }

        return array_map('strtolower', self::DEFAULT_ALLOWED_HOSTS);
    }

    /**
     * Check if a host is a subdomain of an allowed host.
     */
    private function isSubdomainOf(string $host, string $allowedHost): bool
    {
        // For enterprise instances like gitlab.company.com being subdomain of gitlab.com
        return str_ends_with($host, '.' . $allowedHost);
    }

    /**
     * Get current allowed hosts.
     */
    /**
     * @return string[]
     */
    public function getAllowedHosts(): array
    {
        return $this->allowedHosts;
    }

    /**
     * Add a host to the whitelist.
     */
    public function addAllowedHost(string $host): void
    {
        $host = strtolower($host);
        if (!in_array($host, $this->allowedHosts, true)) {
            $this->allowedHosts[] = $host;
        }
    }
}