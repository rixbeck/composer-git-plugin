<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use Neologik\ComposerGitInstaller\Security\UrlFormatValidator;
use Neologik\ComposerGitInstaller\Model\ParsedUrl;
use Neologik\ComposerGitInstaller\Exception\SecurityException;

/**
 * Unit tests for URL format validator.
 */
class UrlFormatValidatorTest extends TestCase
{
    private UrlFormatValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UrlFormatValidator();
    }

    public function testGetName(): void
    {
        $this->assertEquals('URL Format Validator', $this->validator->getName());
    }

    public function testValidateValidUrl(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://github.com/owner/repo@main',
            scheme: 'https',
            host: 'github.com',
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch'
        );

        // Should not throw exception
        $this->validator->validate($parsedUrl);
        $this->assertTrue(true); // Assert that no exception was thrown
    }

    public function testValidateInvalidScheme(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+ftp://github.com/owner/repo@main',
            scheme: 'ftp',
            host: 'github.com',
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch'
        );

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Scheme "ftp" is not allowed');
        $this->validator->validate($parsedUrl);
    }

    public function testValidateIpAddress(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://192.168.1.1/owner/repo@main',
            scheme: 'https',
            host: '192.168.1.1',
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch'
        );

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('IP addresses are not allowed');
        $this->validator->validate($parsedUrl);
    }

    public function testValidateExcessivelyLongUrl(): void
    {
        $longUrl = 'git+https://github.com/' . str_repeat('a', 2500) . '/repo@main';
        $parsedUrl = new ParsedUrl(
            originalUrl: $longUrl,
            scheme: 'https',
            host: 'github.com',
            owner: str_repeat('a', 2500),
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch'
        );

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('URL exceeds maximum length');
        $this->validator->validate($parsedUrl);
    }

    public function testValidateEmptyOwner(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://github.com//repo@main',
            scheme: 'https',
            host: 'github.com',
            owner: '',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch'
        );

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Owner cannot be empty');
        $this->validator->validate($parsedUrl);
    }

    public function testValidatePathTraversalInSubdirectory(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://github.com/owner/repo@main#../malicious',
            scheme: 'https',
            host: 'github.com',
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch',
            subdirectory: '../malicious'
        );

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Path traversal detected');
        $this->validator->validate($parsedUrl);
    }

    public function testValidateInvalidCharacters(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://github.com/owner/repo@main',
            scheme: 'https',
            host: 'github.com',
            owner: 'owner<script>',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch'
        );

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Invalid character detected');
        $this->validator->validate($parsedUrl);
    }

    /**
     * @dataProvider validHostProvider
     */
    public function testValidateValidHosts(string $host): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: "git+https://{$host}/owner/repo@main",
            scheme: 'https',
            host: $host,
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch'
        );

        // Should not throw exception
        $this->validator->validate($parsedUrl);
        $this->assertTrue(true);
    }

    public static function validHostProvider(): array
    {
        return [
            ['github.com'],
            ['gitlab.com'],
            ['bitbucket.org'],
            ['git.example.com'],
            ['code.company.org']
        ];
    }

    /**
     * @dataProvider invalidHostProvider
     */
    public function testValidateInvalidHosts(string $host): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: "git+https://{$host}/owner/repo@main",
            scheme: 'https',
            host: $host,
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch'
        );

        $this->expectException(SecurityException::class);
        $this->validator->validate($parsedUrl);
    }

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
}