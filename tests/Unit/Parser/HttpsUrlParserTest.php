<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Tests\Unit\Parser;

use Neologik\ComposerGitInstaller\Exception\InvalidUrlException;
use Neologik\ComposerGitInstaller\Parser\HttpsUrlParser;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for HTTPS URL parser.
 *
 * @internal
 *
 * @small
 */
class HttpsUrlParserTest extends TestCase
{
    private HttpsUrlParser $parser;

    /**
     * @test
     */
    public function canHandleHttpsUrls(): void
    {
        self::assertTrue($this->parser->canHandle('git+https://github.com/owner/repo@branch'));
        self::assertFalse($this->parser->canHandle('git+ssh://git@github.com/owner/repo@branch'));
        self::assertFalse($this->parser->canHandle('https://github.com/owner/repo'));
    }

    /**
     * @test
     */
    public function parseGitHubUrl(): void
    {
        $url = 'git+https://github.com/owner/repo@main';
        $result = $this->parser->parse($url);

        self::assertNotNull($result);
        self::assertEquals($url, $result->originalUrl);
        self::assertEquals('https', $result->scheme);
        self::assertEquals('github.com', $result->host);
        self::assertEquals('owner', $result->owner);
        self::assertEquals('repo', $result->repository);
        self::assertEquals('main', $result->reference);
        self::assertEquals('branch', $result->referenceType);
        self::assertNull($result->subdirectory);
    }

    /**
     * @test
     */
    public function parseUrlWithSubdirectory(): void
    {
        $url = 'git+https://github.com/owner/repo@v1.0.0#packages/subpackage';
        $result = $this->parser->parse($url);

        self::assertEquals('packages/subpackage', $result->subdirectory);
        self::assertEquals('v1.0.0', $result->reference);
        self::assertEquals('tag', $result->referenceType);
    }

    /**
     * @test
     */
    public function parseUrlWithCommitHash(): void
    {
        $url = 'git+https://github.com/owner/repo@abcdef1234567890abcdef1234567890abcdef12';
        $result = $this->parser->parse($url);

        self::assertEquals('abcdef1234567890abcdef1234567890abcdef12', $result->reference);
        self::assertEquals('commit', $result->referenceType);
    }

    /**
     * @test
     */
    public function parseUrlWithGitExtension(): void
    {
        $url = 'git+https://github.com/owner/repo.git@main';
        $result = $this->parser->parse($url);

        self::assertEquals('repo', $result->repository); // .git should be stripped
    }

    /**
     * @test
     */
    public function parseInvalidUrlThrowsException(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->parser->parse('git+https://github.com/incomplete');
    }

    /**
     * @test
     */
    public function parseMissingReferenceThrowsException(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->parser->parse('git+https://github.com/owner/repo');
    }

    /**
     * @test
     */
    public function parseEmptyReferenceThrowsException(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->parser->parse('git+https://github.com/owner/repo@');
    }

    /**
     * @dataProvider validUrlProvider
     *
     * @param array<string, string> $expected
     *
     * @test
     */
    public function parseValidUrls(string $url, array $expected): void
    {
        $result = $this->parser->parse($url);

        self::assertEquals($expected['host'], $result->host);
        self::assertEquals($expected['owner'], $result->owner);
        self::assertEquals($expected['repository'], $result->repository);
        self::assertEquals($expected['reference'], $result->reference);
    }

    /**
     * @return array<string, array{string, array<string, string>}>
     */
    public static function validUrlProvider(): array
    {
        return [
            'GitHub' => [
                'git+https://github.com/symfony/symfony@6.3',
                ['host' => 'github.com', 'owner' => 'symfony', 'repository' => 'symfony', 'reference' => '6.3'],
            ],
            'GitLab' => [
                'git+https://gitlab.com/group/project@develop',
                ['host' => 'gitlab.com', 'owner' => 'group', 'repository' => 'project', 'reference' => 'develop'],
            ],
            'Bitbucket' => [
                'git+https://bitbucket.org/user/repo@feature-branch',
                ['host' => 'bitbucket.org', 'owner' => 'user', 'repository' => 'repo', 'reference' => 'feature-branch'],
            ],
        ];
    }

    protected function setUp(): void
    {
        $this->parser = new HttpsUrlParser();
    }
}
