<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Tests\Unit\Parser;

use PHPUnit\Framework\TestCase;
use Neologik\ComposerGitInstaller\Parser\UrlParserChain;
use Neologik\ComposerGitInstaller\Exception\InvalidUrlException;

/**
 * Unit tests for URL parser chain.
 */
class UrlParserChainTest extends TestCase
{
    private UrlParserChain $parserChain;

    protected function setUp(): void
    {
        $this->parserChain = new UrlParserChain();
    }

    public function testCanParseHttpsUrls(): void
    {
        $this->assertTrue($this->parserChain->canParse('git+https://github.com/owner/repo@branch'));
        $this->assertTrue($this->parserChain->canParse('git+https://gitlab.com/group/project@v1.0.0'));
    }

    public function testCanParseSshUrls(): void
    {
        $this->assertTrue($this->parserChain->canParse('git+ssh://git@github.com/owner/repo@branch'));
        $this->assertTrue($this->parserChain->canParse('git+git@github.com:owner/repo@branch'));
    }

    public function testCannotParseNonGitUrls(): void
    {
        $this->assertFalse($this->parserChain->canParse('https://github.com/owner/repo'));
        $this->assertFalse($this->parserChain->canParse('invalid-url'));
    }

    public function testParseHttpsUrl(): void
    {
        $url = 'git+https://github.com/symfony/symfony@6.3';
        $result = $this->parserChain->parse($url);

        $this->assertEquals('https', $result->scheme);
        $this->assertEquals('github.com', $result->host);
        $this->assertEquals('symfony', $result->owner);
        $this->assertEquals('symfony', $result->repository);
        $this->assertEquals('6.3', $result->reference);
    }

    public function testParseSshUrl(): void
    {
        $url = 'git+ssh://git@gitlab.com/group/project@main';
        $result = $this->parserChain->parse($url);

        $this->assertEquals('ssh', $result->scheme);
        $this->assertEquals('gitlab.com', $result->host);
        $this->assertEquals('group', $result->owner);
        $this->assertEquals('project', $result->repository);
        $this->assertEquals('main', $result->reference);
    }

    public function testParseInvalidUrlThrowsException(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->expectExceptionMessage('URL must start with git+ prefix');
        $this->parserChain->parse('https://github.com/owner/repo');
    }

    public function testParseUnsupportedFormatThrowsException(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->expectExceptionMessage('No parser could handle this URL format');
        $this->parserChain->parse('git+ftp://example.com/repo@branch');
    }

    public function testGetSupportedPatterns(): void
    {
        $patterns = $this->parserChain->getSupportedPatterns();

        $this->assertArrayHasKey('HTTPS', $patterns);
        $this->assertArrayHasKey('SSH', $patterns);
        $this->assertIsArray($patterns['HTTPS']);
        $this->assertIsArray($patterns['SSH']);
        $this->assertNotEmpty($patterns['HTTPS']);
        $this->assertNotEmpty($patterns['SSH']);
    }

    /**
     * @dataProvider urlPriorityProvider
     */
    public function testParserPriority(string $url, string $expectedScheme): void
    {
        $result = $this->parserChain->parse($url);
        $this->assertEquals($expectedScheme, $result->scheme);
    }

    public static function urlPriorityProvider(): array
    {
        return [
            'HTTPS has priority' => [
                'git+https://github.com/owner/repo@branch',
                'https'
            ],
            'SSH when HTTPS not applicable' => [
                'git+ssh://git@github.com/owner/repo@branch',
                'ssh'
            ],
            'SSH shorthand' => [
                'git+git@github.com:owner/repo@branch',
                'ssh'
            ]
        ];
    }
}