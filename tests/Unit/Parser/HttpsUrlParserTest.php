<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Tests\Unit\Parser;

use PHPUnit\Framework\TestCase;
use Neologik\ComposerGitInstaller\Parser\HttpsUrlParser;
use Neologik\ComposerGitInstaller\Exception\InvalidUrlException;

/**
 * Unit tests for HTTPS URL parser.
 */
class HttpsUrlParserTest extends TestCase
{
    private HttpsUrlParser $parser;

    protected function setUp(): void
    {
        $this->parser = new HttpsUrlParser();
    }

    public function testCanHandleHttpsUrls(): void
    {
        $this->assertTrue($this->parser->canHandle('git+https://github.com/owner/repo@branch'));
        $this->assertFalse($this->parser->canHandle('git+ssh://git@github.com/owner/repo@branch'));
        $this->assertFalse($this->parser->canHandle('https://github.com/owner/repo'));
    }

    public function testParseGitHubUrl(): void
    {
        $url = 'git+https://github.com/owner/repo@main';
        $result = $this->parser->parse($url);

        $this->assertNotNull($result);
        $this->assertEquals($url, $result->originalUrl);
        $this->assertEquals('https', $result->scheme);
        $this->assertEquals('github.com', $result->host);
        $this->assertEquals('owner', $result->owner);
        $this->assertEquals('repo', $result->repository);
        $this->assertEquals('main', $result->reference);
        $this->assertEquals('branch', $result->referenceType);
        $this->assertNull($result->subdirectory);
    }

    public function testParseUrlWithSubdirectory(): void
    {
        $url = 'git+https://github.com/owner/repo@v1.0.0#packages/subpackage';
        $result = $this->parser->parse($url);

        $this->assertEquals('packages/subpackage', $result->subdirectory);
        $this->assertEquals('v1.0.0', $result->reference);
        $this->assertEquals('tag', $result->referenceType);
    }

    public function testParseUrlWithCommitHash(): void
    {
        $url = 'git+https://github.com/owner/repo@abcdef1234567890abcdef1234567890abcdef12';
        $result = $this->parser->parse($url);

        $this->assertEquals('abcdef1234567890abcdef1234567890abcdef12', $result->reference);
        $this->assertEquals('commit', $result->referenceType);
    }

    public function testParseUrlWithGitExtension(): void
    {
        $url = 'git+https://github.com/owner/repo.git@main';
        $result = $this->parser->parse($url);

        $this->assertEquals('repo', $result->repository); // .git should be stripped
    }

    public function testParseInvalidUrlThrowsException(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->parser->parse('git+https://github.com/incomplete');
    }

    public function testParseMissingReferenceThrowsException(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->parser->parse('git+https://github.com/owner/repo');
    }

    public function testParseEmptyReferenceThrowsException(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->parser->parse('git+https://github.com/owner/repo@');
    }

    /**
     * @dataProvider validUrlProvider
     */
    public function testParseValidUrls(string $url, array $expected): void
    {
        $result = $this->parser->parse($url);

        $this->assertEquals($expected['host'], $result->host);
        $this->assertEquals($expected['owner'], $result->owner);
        $this->assertEquals($expected['repository'], $result->repository);
        $this->assertEquals($expected['reference'], $result->reference);
    }

    public static function validUrlProvider(): array
    {
        return [
            'GitHub' => [
                'git+https://github.com/symfony/symfony@6.3',
                ['host' => 'github.com', 'owner' => 'symfony', 'repository' => 'symfony', 'reference' => '6.3']
            ],
            'GitLab' => [
                'git+https://gitlab.com/group/project@develop',
                ['host' => 'gitlab.com', 'owner' => 'group', 'repository' => 'project', 'reference' => 'develop']
            ],
            'Bitbucket' => [
                'git+https://bitbucket.org/user/repo@feature-branch',
                ['host' => 'bitbucket.org', 'owner' => 'user', 'repository' => 'repo', 'reference' => 'feature-branch']
            ]
        ];
    }
}