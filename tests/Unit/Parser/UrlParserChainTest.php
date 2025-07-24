<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Tests\Unit\Parser;

use Neologik\ComposerGitInstaller\Exception\InvalidUrlException;
use Neologik\ComposerGitInstaller\Parser\UrlParserChain;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for URL parser chain.
 *
 * @internal
 *
 * @small
 */
class UrlParserChainTest extends TestCase
{
    private UrlParserChain $parserChain;

    /**
     * @test
     */
    public function canParseHttpsUrls(): void
    {
        self::assertTrue($this->parserChain->canParse('git+https://github.com/owner/repo@branch'));
        self::assertTrue($this->parserChain->canParse('git+https://gitlab.com/group/project@v1.0.0'));
    }

    /**
     * @test
     */
    public function canParseSshUrls(): void
    {
        self::assertTrue($this->parserChain->canParse('git+ssh://git@github.com/owner/repo@branch'));
        self::assertTrue($this->parserChain->canParse('git+git@github.com:owner/repo@branch'));
    }

    /**
     * @test
     */
    public function cannotParseNonGitUrls(): void
    {
        self::assertFalse($this->parserChain->canParse('https://github.com/owner/repo'));
        self::assertFalse($this->parserChain->canParse('invalid-url'));
    }

    /**
     * @test
     */
    public function parseHttpsUrl(): void
    {
        $url = 'git+https://github.com/symfony/symfony@6.3';
        $result = $this->parserChain->parse($url);

        self::assertEquals('https', $result->scheme);
        self::assertEquals('github.com', $result->host);
        self::assertEquals('symfony', $result->owner);
        self::assertEquals('symfony', $result->repository);
        self::assertEquals('6.3', $result->reference);
    }

    /**
     * @test
     */
    public function parseSshUrl(): void
    {
        $url = 'git+ssh://git@gitlab.com/group/project@main';
        $result = $this->parserChain->parse($url);

        self::assertEquals('ssh', $result->scheme);
        self::assertEquals('gitlab.com', $result->host);
        self::assertEquals('group', $result->owner);
        self::assertEquals('project', $result->repository);
        self::assertEquals('main', $result->reference);
    }

    /**
     * @test
     */
    public function parseInvalidUrlThrowsException(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->expectExceptionMessage('URL must start with git+ prefix');
        $this->parserChain->parse('https://github.com/owner/repo');
    }

    /**
     * @test
     */
    public function parseUnsupportedFormatThrowsException(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->expectExceptionMessage('No parser could handle this URL format');
        $this->parserChain->parse('git+ftp://example.com/repo@branch');
    }

    /**
     * @test
     */
    public function getSupportedPatterns(): void
    {
        $patterns = $this->parserChain->getSupportedPatterns();

        self::assertArrayHasKey('HTTPS', $patterns);
        self::assertArrayHasKey('SSH', $patterns);
        self::assertIsArray($patterns['HTTPS']);
        self::assertIsArray($patterns['SSH']);
        self::assertNotEmpty($patterns['HTTPS']);
        self::assertNotEmpty($patterns['SSH']);
    }

    /**
     * @dataProvider urlPriorityProvider
     *
     * @test
     */
    public function parserPriority(string $url, string $expectedScheme): void
    {
        $result = $this->parserChain->parse($url);
        self::assertEquals($expectedScheme, $result->scheme);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function urlPriorityProvider(): array
    {
        return [
            'HTTPS has priority' => [
                'git+https://github.com/owner/repo@branch',
                'https',
            ],
            'SSH when HTTPS not applicable' => [
                'git+ssh://git@github.com/owner/repo@branch',
                'ssh',
            ],
            'SSH shorthand' => [
                'git+git@github.com:owner/repo@branch',
                'ssh',
            ],
        ];
    }

    protected function setUp(): void
    {
        $this->parserChain = new UrlParserChain();
    }
}
