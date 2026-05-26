<?php

namespace Tests\Unit;

use App\Support\UserAgentParser;
use PHPUnit\Framework\TestCase;

class UserAgentParserTest extends TestCase
{
    /**
     * @dataProvider browserCases
     */
    public function test_browser_detection(string $ua, string $expectedBrowser, ?string $expectedVersion): void
    {
        $result = UserAgentParser::parse($ua);

        $this->assertSame($expectedBrowser, $result['browser']);
        $this->assertSame($expectedVersion, $result['browser_version']);
    }

    public static function browserCases(): array
    {
        return [
            'Chrome on Windows' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Chrome',
                '124',
            ],
            'Edge on Windows (Chrome-based)' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 Edg/124.0.0.0',
                'Edge',
                '124',
            ],
            'Firefox on macOS' => [
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 14.4; rv:126.0) Gecko/20100101 Firefox/126.0',
                'Firefox',
                '126',
            ],
            'Safari on macOS' => [
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15',
                'Safari',
                '17',
            ],
            'Safari on iPhone' => [
                'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1',
                'Safari',
                '17',
            ],
            'Chrome on Android' => [
                'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36',
                'Chrome',
                '124',
            ],
            'Opera (OPR token)' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36 OPR/109.0.0.0',
                'Opera',
                '109',
            ],
        ];
    }

    /**
     * @dataProvider osCases
     */
    public function test_os_detection(string $ua, string $expectedOs, ?string $expectedVersion): void
    {
        $result = UserAgentParser::parse($ua);

        $this->assertSame($expectedOs, $result['os']);
        $this->assertSame($expectedVersion, $result['os_version']);
    }

    public static function osCases(): array
    {
        return [
            'Windows 10/11' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0.0.0',
                'Windows',
                '10/11',
            ],
            'Windows 7' => [
                'Mozilla/5.0 (Windows NT 6.1; WOW64) Chrome/100.0',
                'Windows',
                '7',
            ],
            'macOS' => [
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari/605.1.15',
                'macOS',
                '10.15.7',
            ],
            'iOS' => [
                'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) Safari/604.1',
                'iOS',
                '17.4',
            ],
            'iPadOS' => [
                'Mozilla/5.0 (iPad; CPU OS 17_4 like Mac OS X) Safari/604.1',
                'iPadOS',
                '17.4',
            ],
            'Android' => [
                'Mozilla/5.0 (Linux; Android 14; Pixel 8) Chrome/124.0.0.0',
                'Android',
                '14',
            ],
            'Linux' => [
                'Mozilla/5.0 (X11; Linux x86_64; rv:126.0) Firefox/126.0',
                'Linux',
                null,
            ],
        ];
    }

    public function test_chrome_and_edge_produce_distinct_labels(): void
    {
        $chrome = UserAgentParser::parse(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'
        );
        $edge = UserAgentParser::parse(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 Edg/124.0.0.0'
        );

        $this->assertSame('Chrome 124', $chrome['label']);
        $this->assertSame('Edge 124', $edge['label']);
        $this->assertNotSame($chrome['label'], $edge['label']);
    }

    public function test_bot_detection(): void
    {
        $cases = [
            'curl/8.4.0'                       => ['Curl', true],
            'Slackbot-LinkExpanding 1.0'       => ['Slack', true],
            'facebookexternalhit/1.1'          => ['Facebook', true],
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)' => ['Googlebot', true],
            'Twitterbot/1.0'                   => ['Twitter', true],
        ];

        foreach ($cases as $ua => [$expectedBrowser, $expectedBot]) {
            $r = UserAgentParser::parse($ua);
            $this->assertSame($expectedBrowser, $r['browser'], "browser for: $ua");
            $this->assertSame($expectedBot, $r['is_bot'], "is_bot for: $ua");
        }
    }

    public function test_empty_or_null_returns_unknown(): void
    {
        $this->assertSame('Unknown', UserAgentParser::parse(null)['browser']);
        $this->assertSame('Unknown', UserAgentParser::parse('')['browser']);
        $this->assertFalse(UserAgentParser::parse(null)['is_bot']);
    }
}
