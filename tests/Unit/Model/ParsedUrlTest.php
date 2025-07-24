<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Neologik\ComposerGitInstaller\Model\ParsedUrl;

/**
 * Unit tests for ParsedUrl model.
 */
class ParsedUrlTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://github.com/owner/repo@main#subdirectory',
            scheme: 'https',
            host: 'github.com',
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch',
            subdirectory: 'subdirectory',
            port: 443
        );

        $this->assertEquals('git+https://github.com/owner/repo@main#subdirectory', $parsedUrl->originalUrl);
        $this->assertEquals('https', $parsedUrl->scheme);
        $this->assertEquals('github.com', $parsedUrl->host);
        $this->assertEquals('owner', $parsedUrl->owner);
        $this->assertEquals('repo', $parsedUrl->repository);
        $this->assertEquals('main', $parsedUrl->reference);
        $this->assertEquals('branch', $parsedUrl->referenceType);
        $this->assertEquals('subdirectory', $parsedUrl->subdirectory);
        $this->assertEquals(443, $parsedUrl->port);
    }

    public function testGetGitUrlHttps(): void
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

        $this->assertEquals('https://github.com/owner/repo', $parsedUrl->getGitUrl());
    }

    public function testGetGitUrlSsh(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+ssh://git@github.com/owner/repo@main',
            scheme: 'ssh',
            host: 'github.com',
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch'
        );

        $this->assertEquals('git@github.com:owner/repo', $parsedUrl->getGitUrl());
    }

    public function testGetGitUrlWithPort(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://gitlab.example.com:8080/owner/repo@main',
            scheme: 'https',
            host: 'gitlab.example.com',
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch',
            port: 8080
        );

        $this->assertEquals('https://gitlab.example.com:8080/owner/repo', $parsedUrl->getGitUrl());
    }

    public function testGetSuggestedPackageNameWithBranch(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://github.com/symfony/symfony@6.3',
            scheme: 'https',
            host: 'github.com',
            owner: 'symfony',
            repository: 'symfony',
            reference: '6.3',
            referenceType: 'branch'
        );

        $this->assertEquals('symfony/symfony-6.3', $parsedUrl->getSuggestedPackageName());
    }

    public function testGetSuggestedPackageNameWithMainBranch(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://github.com/symfony/symfony@main',
            scheme: 'https',
            host: 'github.com',
            owner: 'symfony',
            repository: 'symfony',
            reference: 'main',
            referenceType: 'branch'
        );

        $this->assertEquals('symfony/symfony', $parsedUrl->getSuggestedPackageName());
    }

    public function testGetSuggestedPackageNameWithMasterBranch(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://github.com/symfony/symfony@master',
            scheme: 'https',
            host: 'github.com',
            owner: 'symfony',
            repository: 'symfony',
            reference: 'master',
            referenceType: 'branch'
        );

        $this->assertEquals('symfony/symfony', $parsedUrl->getSuggestedPackageName());
    }

    public function testHasSubdirectory(): void
    {
        $withSubdirectory = new ParsedUrl(
            originalUrl: 'git+https://github.com/owner/repo@main#subdir',
            scheme: 'https',
            host: 'github.com',
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch',
            subdirectory: 'subdir'
        );

        $withoutSubdirectory = new ParsedUrl(
            originalUrl: 'git+https://github.com/owner/repo@main',
            scheme: 'https',
            host: 'github.com',
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch'
        );

        $this->assertTrue($withSubdirectory->hasSubdirectory());
        $this->assertFalse($withoutSubdirectory->hasSubdirectory());
    }

    public function testGetRepositoryIdentifier(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://github.com/symfony/console@main',
            scheme: 'https',
            host: 'github.com',
            owner: 'symfony',
            repository: 'console',
            reference: 'main',
            referenceType: 'branch'
        );

        $this->assertEquals('symfony/console', $parsedUrl->getRepositoryIdentifier());
    }

    /**
     * @dataProvider referenceTypeProvider
     */
    public function testDifferentReferenceTypes(string $reference, string $expectedType): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: "git+https://github.com/owner/repo@{$reference}",
            scheme: 'https',
            host: 'github.com',
            owner: 'owner',
            repository: 'repo',
            reference: $reference,
            referenceType: $expectedType
        );

        $this->assertEquals($expectedType, $parsedUrl->referenceType);
    }

    public static function referenceTypeProvider(): array
    {
        return [
            'Branch' => ['develop', 'branch'],
            'Tag' => ['v1.0.0', 'tag'],
            'Commit' => ['abcdef1234567890abcdef1234567890abcdef12', 'commit'],
        ];
    }
}