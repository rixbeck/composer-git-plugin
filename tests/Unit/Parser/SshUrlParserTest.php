<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Tests\Unit\Parser;

use Neologik\ComposerGitInstaller\Exception\InvalidUrlException;
use Neologik\ComposerGitInstaller\Parser\SshUrlParser;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SSH URL parser.
 *
 * @internal
 *
 * @small
 */
class SshUrlParserTest extends TestCase
{
    private SshUrlParser $parser;

    /**
     * @test
     */
    public function canHandleSshUrls(): void
    {
        self::assertTrue($this->parser->canHandle('git+ssh://git@github.com/owner/repo@branch'));
        self::assertTrue($this->parser->canHandle('git+git@github.com:owner/repo@branch'));
        self::assertFalse($this->parser->canHandle('git+https://github.com/owner/repo@branch'));
    }

    /**
     * @test
     */
    public function parseSshProtocolUrl(): void
    {
        $url = 'git+ssh://git@github.com/owner/repo@main';
        $result = $this->parser->parse($url);

        self::assertNotNull($result);
        self::assertEquals($url, $result->originalUrl);
        self::assertEquals('ssh', $result->scheme);
        self::assertEquals('github.com', $result->host);
        self::assertEquals('owner', $result->owner);
        self::assertEquals('repo', $result->repository);
        self::assertEquals('main', $result->reference);
        self::assertEquals('branch', $result->referenceType);
    }

    /**
     * @test
     */
    public function parseSshShorthandUrl(): void
    {
        $url = 'git+git@github.com:owner/repo@develop';
        $result = $this->parser->parse($url);

        self::assertNotNull($result);
        self::assertEquals('ssh', $result->scheme);
        self::assertEquals('github.com', $result->host);
        self::assertEquals('owner', $result->owner);
        self::assertEquals('repo', $result->repository);
        self::assertEquals('develop', $result->reference);
    }

    /**
     * @test
     */
    public function parseWithSubdirectory(): void
    {
        $url = 'git+ssh://git@gitlab.com/group/project@v2.0.0#subpackage';
        $result = $this->parser->parse($url);

        self::assertEquals('subpackage', $result->subdirectory);
        self::assertEquals('v2.0.0', $result->reference);
        self::assertEquals('tag', $result->referenceType);
    }

    /**
     * @test
     */
    public function parseWithCommitHash(): void
    {
        $url = 'git+git@bitbucket.org:user/repo@abcdef1234567890abcdef1234567890abcdef12';
        $result = $this->parser->parse($url);

        self::assertEquals('abcdef1234567890abcdef1234567890abcdef12', $result->reference);
        self::assertEquals('commit', $result->referenceType);
    }

    /**
     * @test
     */
    public function parseInvalidSshUrlThrowsException(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->parser->parse('git+ssh://git@github.com/incomplete');
    }

    /**
     * @test
     */
    public function parseMissingColonThrowsException(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->parser->parse('git+git@github.com/owner/repo@branch');
    }

    /**
     * @test
     */
    public function parseInvalidSshShorthandUrlThrowsException(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->parser->parse('git+git@github.com/owner/repo@branch');
    }

    /**
     * @dataProvider validSshUrlProvider
     *
     * @param array<string, string> $expected
     *
     * @test
     */
    public function parseValidSshUrls(string $url, array $expected): void
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
    public static function validSshUrlProvider(): array
    {
        return [
            'SSH Protocol GitHub' => [
                'git+ssh://git@github.com/symfony/symfony@6.3',
                ['host' => 'github.com', 'owner' => 'symfony', 'repository' => 'symfony', 'reference' => '6.3'],
            ],
            'SSH Shorthand GitLab' => [
                'git+git@gitlab.com:group/project@main',
                ['host' => 'gitlab.com', 'owner' => 'group', 'repository' => 'project', 'reference' => 'main'],
            ],
            'SSH Protocol with .git' => [
                'git+ssh://git@bitbucket.org/user/repo.git@feature',
                ['host' => 'bitbucket.org', 'owner' => 'user', 'repository' => 'repo', 'reference' => 'feature'],
            ],
        ];
    }

    protected function setUp(): void
    {
        $this->parser = new SshUrlParser();
    }
}
