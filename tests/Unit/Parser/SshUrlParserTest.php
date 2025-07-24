<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Tests\Unit\Parser;

use PHPUnit\Framework\TestCase;
use Neologik\ComposerGitInstaller\Parser\SshUrlParser;
use Neologik\ComposerGitInstaller\Exception\InvalidUrlException;

/**
 * Unit tests for SSH URL parser.
 */
class SshUrlParserTest extends TestCase
{
    private SshUrlParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SshUrlParser();
    }

    public function testCanHandleSshUrls(): void
    {
        $this->assertTrue($this->parser->canHandle('git+ssh://git@github.com/owner/repo@branch'));
        $this->assertTrue($this->parser->canHandle('git+git@github.com:owner/repo@branch'));
        $this->assertFalse($this->parser->canHandle('git+https://github.com/owner/repo@branch'));
    }

    public function testParseSshProtocolUrl(): void
    {
        $url = 'git+ssh://git@github.com/owner/repo@main';
        $result = $this->parser->parse($url);

        $this->assertNotNull($result);
        $this->assertEquals($url, $result->originalUrl);
        $this->assertEquals('ssh', $result->scheme);
        $this->assertEquals('github.com', $result->host);
        $this->assertEquals('owner', $result->owner);
        $this->assertEquals('repo', $result->repository);
        $this->assertEquals('main', $result->reference);
        $this->assertEquals('branch', $result->referenceType);
    }

    public function testParseSshShorthandUrl(): void
    {
        $url = 'git+git@github.com:owner/repo@develop';
        $result = $this->parser->parse($url);

        $this->assertNotNull($result);
        $this->assertEquals('ssh', $result->scheme);
        $this->assertEquals('github.com', $result->host);
        $this->assertEquals('owner', $result->owner);
        $this->assertEquals('repo', $result->repository);
        $this->assertEquals('develop', $result->reference);
    }

    public function testParseWithSubdirectory(): void
    {
        $url = 'git+ssh://git@gitlab.com/group/project@v2.0.0#subpackage';
        $result = $this->parser->parse($url);

        $this->assertEquals('subpackage', $result->subdirectory);
        $this->assertEquals('v2.0.0', $result->reference);
        $this->assertEquals('tag', $result->referenceType);
    }

    public function testParseWithCommitHash(): void
    {
        $url = 'git+git@bitbucket.org:user/repo@abcdef1234567890abcdef1234567890abcdef12';
        $result = $this->parser->parse($url);

        $this->assertEquals('abcdef1234567890abcdef1234567890abcdef12', $result->reference);
        $this->assertEquals('commit', $result->referenceType);
    }

    public function testParseInvalidSshUrlThrowsException(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->parser->parse('git+ssh://git@github.com/incomplete');
    }

    public function testParseMissingColonThrowsException(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->parser->parse('git+git@github.com/owner/repo@branch');
    }

    public function testParseInvalidSshShorthandUrlThrowsException(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->parser->parse('git+git@github.com/owner/repo@branch');
    }

    /**
     * @dataProvider validSshUrlProvider
     * @param array<string, string> $expected
     */
    public function testParseValidSshUrls(string $url, array $expected): void
    {
        $result = $this->parser->parse($url);

        $this->assertEquals($expected['host'], $result->host);
        $this->assertEquals($expected['owner'], $result->owner);
        $this->assertEquals($expected['repository'], $result->repository);
        $this->assertEquals($expected['reference'], $result->reference);
    }

    /**
     * @return array<string, array{string, array<string, string>}>
     */
    public static function validSshUrlProvider(): array
    {
        return [
            'SSH Protocol GitHub' => [
                'git+ssh://git@github.com/symfony/symfony@6.3',
                ['host' => 'github.com', 'owner' => 'symfony', 'repository' => 'symfony', 'reference' => '6.3']
            ],
            'SSH Shorthand GitLab' => [
                'git+git@gitlab.com:group/project@main',
                ['host' => 'gitlab.com', 'owner' => 'group', 'repository' => 'project', 'reference' => 'main']
            ],
            'SSH Protocol with .git' => [
                'git+ssh://git@bitbucket.org/user/repo.git@feature',
                ['host' => 'bitbucket.org', 'owner' => 'user', 'repository' => 'repo', 'reference' => 'feature']
            ]
        ];
    }
}