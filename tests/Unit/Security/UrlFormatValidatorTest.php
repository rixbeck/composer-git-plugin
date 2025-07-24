<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Tests\Unit\Security;

use Neologik\ComposerGitInstaller\Exception\SecurityException;
use Neologik\ComposerGitInstaller\Model\ParsedUrl;
use Neologik\ComposerGitInstaller\Security\UrlFormatValidator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for URL format validator.
 *
 * @internal
 *
 * @small
 */
class UrlFormatValidatorTest extends TestCase
{
    private UrlFormatValidator $validator;

    /**
     * @test
     */
    public function getName(): void
    {
        self::assertEquals('URL Format Validator', $this->validator->getName());
    }

    /**
     * @test
     */
    public function validateValidUrl(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://github.com/owner/repo@main',
            scheme: 'https',
            host: 'github.com',
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch',
        );

        // Should not throw exception
        $this->validator->validate($parsedUrl);
        self::assertTrue(true); // Assert that no exception was thrown
    }

    /**
     * @test
     */
    public function validateInvalidScheme(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+ftp://github.com/owner/repo@main',
            scheme: 'ftp',
            host: 'github.com',
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch',
        );

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Scheme "ftp" is not allowed');
        $this->validator->validate($parsedUrl);
    }

    /**
     * @test
     */
    public function validateIpAddress(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://192.168.1.1/owner/repo@main',
            scheme: 'https',
            host: '192.168.1.1',
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch',
        );

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('IP addresses are not allowed');
        $this->validator->validate($parsedUrl);
    }

    /**
     * @test
     */
    public function validateExcessivelyLongUrl(): void
    {
        $longUrl = 'git+https://github.com/' . str_repeat('a', 2500) . '/repo@main';
        $parsedUrl = new ParsedUrl(
            originalUrl: $longUrl,
            scheme: 'https',
            host: 'github.com',
            owner: str_repeat('a', 2500),
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch',
        );

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('URL exceeds maximum length');
        $this->validator->validate($parsedUrl);
    }

    /**
     * @test
     */
    public function validateEmptyOwner(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://github.com//repo@main',
            scheme: 'https',
            host: 'github.com',
            owner: '',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch',
        );

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Owner cannot be empty');
        $this->validator->validate($parsedUrl);
    }

    /**
     * @test
     */
    public function validatePathTraversalInSubdirectory(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://github.com/owner/repo@main#../malicious',
            scheme: 'https',
            host: 'github.com',
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch',
            subdirectory: '../malicious',
        );

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Path traversal detected');
        $this->validator->validate($parsedUrl);
    }

    /**
     * @test
     */
    public function validateInvalidCharacters(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://github.com/owner/repo@main',
            scheme: 'https',
            host: 'github.com',
            owner: 'owner<script>',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch',
        );

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Invalid character detected');
        $this->validator->validate($parsedUrl);
    }

    /**
     * @dataProvider validHostProvider
     *
     * @test
     */
    public function validateValidHosts(string $host): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: "git+https://{$host}/owner/repo@main",
            scheme: 'https',
            host: $host,
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch',
        );

        // Should not throw exception
        $this->validator->validate($parsedUrl);
        self::assertTrue(true);
    }

    /**
     * @return array<int, array{string}>
     */
    public static function validHostProvider(): array
    {
        return [
            ['github.com'],
            ['gitlab.com'],
            ['bitbucket.org'],
            ['git.example.com'],
            ['code.company.org'],
        ];
    }

    /**
     * @dataProvider invalidHostProvider
     *
     * @test
     */
    public function validateInvalidHosts(string $host): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: "git+https://{$host}/owner/repo@main",
            scheme: 'https',
            host: $host,
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch',
        );

        $this->expectException(SecurityException::class);
        $this->validator->validate($parsedUrl);
    }

    /**
     * @return array<int, array{string}>
     */
    public static function invalidHostProvider(): array
    {
        return [
            ['192.168.1.1'],  // IP address
            ['::1'],          // IPv6
            ['invalid..host'], // Double dots
            ['-invalid.com'],  // Starting with dash
            ['invalid-.com'],  // Ending with dash
        ];
    }

    /**
     * @test
     */
    protected function setUp(): void
    {
        $this->validator = new UrlFormatValidator();
    }
}
