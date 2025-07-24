<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Tests\Unit\Model;

use Neologik\ComposerGitInstaller\Model\ParsedUrl;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ParsedUrl model.
 *
 * @internal
 *
 * @small
 */
class ParsedUrlTest extends TestCase
{
    /**
     * @test
     */
    public function constructorAndGetters(): void
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
            port: 443,
        );

        self::assertEquals('git+https://github.com/owner/repo@main#subdirectory', $parsedUrl->originalUrl);
        self::assertEquals('https', $parsedUrl->scheme);
        self::assertEquals('github.com', $parsedUrl->host);
        self::assertEquals('owner', $parsedUrl->owner);
        self::assertEquals('repo', $parsedUrl->repository);
        self::assertEquals('main', $parsedUrl->reference);
        self::assertEquals('branch', $parsedUrl->referenceType);
        self::assertEquals('subdirectory', $parsedUrl->subdirectory);
        self::assertEquals(443, $parsedUrl->port);
    }

    /**
     * @test
     */
    public function getGitUrlHttps(): void
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

        self::assertEquals('https://github.com/owner/repo', $parsedUrl->getGitUrl());
    }

    /**
     * @test
     */
    public function getGitUrlSsh(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+ssh://git@github.com/owner/repo@main',
            scheme: 'ssh',
            host: 'github.com',
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch',
        );

        self::assertEquals('git@github.com:owner/repo', $parsedUrl->getGitUrl());
    }

    /**
     * @test
     */
    public function getGitUrlWithPort(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://gitlab.example.com:8080/owner/repo@main',
            scheme: 'https',
            host: 'gitlab.example.com',
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch',
            port: 8080,
        );

        self::assertEquals('https://gitlab.example.com:8080/owner/repo', $parsedUrl->getGitUrl());
    }

    /**
     * @test
     */
    public function getSuggestedPackageNameWithBranch(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://github.com/symfony/symfony@6.3',
            scheme: 'https',
            host: 'github.com',
            owner: 'symfony',
            repository: 'symfony',
            reference: '6.3',
            referenceType: 'branch',
        );

        self::assertEquals('symfony/symfony-6.3', $parsedUrl->getSuggestedPackageName());
    }

    /**
     * @test
     */
    public function getSuggestedPackageNameWithMainBranch(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://github.com/symfony/symfony@main',
            scheme: 'https',
            host: 'github.com',
            owner: 'symfony',
            repository: 'symfony',
            reference: 'main',
            referenceType: 'branch',
        );

        self::assertEquals('symfony/symfony', $parsedUrl->getSuggestedPackageName());
    }

    /**
     * @test
     */
    public function getSuggestedPackageNameWithMasterBranch(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://github.com/symfony/symfony@master',
            scheme: 'https',
            host: 'github.com',
            owner: 'symfony',
            repository: 'symfony',
            reference: 'master',
            referenceType: 'branch',
        );

        self::assertEquals('symfony/symfony', $parsedUrl->getSuggestedPackageName());
    }

    /**
     * @test
     */
    public function hasSubdirectory(): void
    {
        $withSubdirectory = new ParsedUrl(
            originalUrl: 'git+https://github.com/owner/repo@main#subdir',
            scheme: 'https',
            host: 'github.com',
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch',
            subdirectory: 'subdir',
        );

        $withoutSubdirectory = new ParsedUrl(
            originalUrl: 'git+https://github.com/owner/repo@main',
            scheme: 'https',
            host: 'github.com',
            owner: 'owner',
            repository: 'repo',
            reference: 'main',
            referenceType: 'branch',
        );

        self::assertTrue($withSubdirectory->hasSubdirectory());
        self::assertFalse($withoutSubdirectory->hasSubdirectory());
    }

    /**
     * @test
     */
    public function getRepositoryIdentifier(): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: 'git+https://github.com/symfony/console@main',
            scheme: 'https',
            host: 'github.com',
            owner: 'symfony',
            repository: 'console',
            reference: 'main',
            referenceType: 'branch',
        );

        self::assertEquals('symfony/console', $parsedUrl->getRepositoryIdentifier());
    }

    /**
     * @dataProvider referenceTypeProvider
     *
     * @test
     */
    public function differentReferenceTypes(string $reference, string $expectedType): void
    {
        $parsedUrl = new ParsedUrl(
            originalUrl: "git+https://github.com/owner/repo@{$reference}",
            scheme: 'https',
            host: 'github.com',
            owner: 'owner',
            repository: 'repo',
            reference: $reference,
            referenceType: $expectedType,
        );

        self::assertEquals($expectedType, $parsedUrl->referenceType);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function referenceTypeProvider(): array
    {
        return [
            'Branch' => ['develop', 'branch'],
            'Tag' => ['v1.0.0', 'tag'],
            'Commit' => ['abcdef1234567890abcdef1234567890abcdef12', 'commit'],
        ];
    }
}
