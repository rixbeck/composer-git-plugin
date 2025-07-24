<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Security;

use Composer\IO\IOInterface;
use Neologik\ComposerGitInstaller\Model\ParsedUrl;
use Neologik\ComposerGitInstaller\Exception\SecurityException;

/**
 * Validates rate limiting to prevent abuse.
 */
class RateLimitValidator implements ValidatorInterface
{
    private const DEFAULT_MAX_REQUESTS = 100;
    private const DEFAULT_TIME_WINDOW = 3600; // 1 hour in seconds
    private const CACHE_FILE = 'git-installer-rate-limit.json';

    /**
     * @var array<string, int[]>
     */
    private array $requestCounts = [];
    private string $cacheFile;

    public function __construct(private readonly IOInterface $io)
    {
        $this->cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::CACHE_FILE;
        $this->loadRequestCounts();
    }

    /**
     * Validate rate limiting.
     */
    public function validate(ParsedUrl $parsedUrl): void
    {
        $key = $this->generateKey($parsedUrl);
        $now = time();
        
        // Clean old entries
        $this->cleanExpiredEntries($now);
        
        // Check current count
        if (!isset($this->requestCounts[$key])) {
            $this->requestCounts[$key] = [];
        }
        
        $requests = $this->requestCounts[$key];
        $recentRequests = array_filter($requests, fn($timestamp) => ($now - $timestamp) < self::DEFAULT_TIME_WINDOW);
        
        if (count($recentRequests) >= self::DEFAULT_MAX_REQUESTS) {
            throw new SecurityException(
                sprintf(
                    'Rate limit exceeded. Maximum %d requests per hour allowed for %s',
                    self::DEFAULT_MAX_REQUESTS,
                    $parsedUrl->host
                ),
                'RATE_LIMIT_EXCEEDED'
            );
        }
        
        // Record this request
        $this->requestCounts[$key][] = $now;
        $this->saveRequestCounts();
        
        $this->io->writeError(
            sprintf('<info>Rate limit check passed: %d/%d requests in the last hour</info>', 
                count($recentRequests) + 1, 
                self::DEFAULT_MAX_REQUESTS
            ),
            true,
            IOInterface::VERY_VERBOSE
        );
    }

    /**
     * Get the name of this validator.
     */
    public function getName(): string
    {
        return 'Rate Limit Validator';
    }

    /**
     * Generate a key for rate limiting (based on host).
     */
    private function generateKey(ParsedUrl $parsedUrl): string
    {
        return 'host:' . strtolower($parsedUrl->host);
    }

    /**
     * Load request counts from cache file.
     */
    private function loadRequestCounts(): void
    {
        if (file_exists($this->cacheFile)) {
            $data = file_get_contents($this->cacheFile);
            if ($data !== false) {
                $decoded = json_decode($data, true);
                if (is_array($decoded)) {
                    $this->requestCounts = $decoded;
                }
            }
        }
    }

    /**
     * Save request counts to cache file.
     */
    private function saveRequestCounts(): void
    {
        file_put_contents($this->cacheFile, json_encode($this->requestCounts, JSON_PRETTY_PRINT));
    }

    /**
     * Clean expired entries from the cache.
     */
    private function cleanExpiredEntries(int $now): void
    {
        foreach ($this->requestCounts as $key => $requests) {
            $this->requestCounts[$key] = array_filter(
                $requests, 
                fn($timestamp) => ($now - $timestamp) < self::DEFAULT_TIME_WINDOW
            );
            
            // Remove empty entries
            if (empty($this->requestCounts[$key])) {
                unset($this->requestCounts[$key]);
            }
        }
    }

    /**
     * Get current request counts (for testing).
     */
    /**
     * @return array<string, int[]>
     */
    public function getRequestCounts(): array
    {
        return $this->requestCounts;
    }

    /**
     * Reset rate limiting data (for testing).
     */
    public function reset(): void
    {
        $this->requestCounts = [];
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }
}