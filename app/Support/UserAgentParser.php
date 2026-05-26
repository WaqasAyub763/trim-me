<?php

namespace App\Support;

/**
 * Tiny, dependency-free User-Agent parser.
 *
 * Pulls out the browser family + major version, the OS name + version, and
 * a basic bot flag. Detection order matters — Edge's UA contains "Chrome",
 * Chrome's contains "Safari", and Safari's contains "AppleWebKit", so the
 * branches are arranged from most-specific to least-specific.
 *
 * Not exhaustive — it covers the browsers a small internal link shortener
 * actually sees. Anything unrecognized falls back to "Unknown".
 */
class UserAgentParser
{
    /**
     * @return array{
     *     browser: string,
     *     browser_version: ?string,
     *     os: string,
     *     os_version: ?string,
     *     is_bot: bool,
     *     label: string,
     *     os_label: string,
     * }
     */
    public static function parse(?string $ua): array
    {
        $ua = trim((string) $ua);

        if ($ua === '') {
            return self::result('Unknown', null, 'Unknown', null, false);
        }

        // ---- Bots / non-browsers first. -------------------------------------
        if (preg_match('/(Slackbot|facebookexternalhit|Twitterbot|LinkedInBot|Discordbot|TelegramBot|WhatsApp)/i', $ua, $m)) {
            return self::result(self::title($m[1]), null, self::os($ua)['name'], self::os($ua)['version'], true);
        }
        if (preg_match('/(Googlebot|bingbot|DuckDuckBot|YandexBot|Applebot|Baiduspider)/i', $ua, $m)) {
            return self::result(self::title($m[1]), null, 'Unknown', null, true);
        }
        if (preg_match('/^(curl|Wget|HTTPie|python-requests|Go-http-client|GuzzleHttp|PostmanRuntime)\/?([\d.]*)/i', $ua, $m)) {
            return self::result(self::title($m[1]), self::short($m[2] ?? ''), self::os($ua)['name'], self::os($ua)['version'], true);
        }
        if (preg_match('/(bot|crawler|spider|crawling|robot|slurp)/i', $ua)) {
            return self::result('Bot', null, self::os($ua)['name'], self::os($ua)['version'], true);
        }

        // ---- Browser detection. Order matters. ------------------------------
        $browser = 'Unknown';
        $version = null;

        if (preg_match('/Edg(?:e|A|iOS)?\/([\d.]+)/i', $ua, $m)) {
            $browser = 'Edge';
            $version = self::short($m[1]);
        } elseif (preg_match('/OPR\/([\d.]+)/', $ua, $m) || preg_match('/Opera\/([\d.]+)/i', $ua, $m)) {
            $browser = 'Opera';
            $version = self::short($m[1]);
        } elseif (preg_match('/Vivaldi\/([\d.]+)/i', $ua, $m)) {
            $browser = 'Vivaldi';
            $version = self::short($m[1]);
        } elseif (preg_match('/Brave\/([\d.]+)/i', $ua, $m)) {
            $browser = 'Brave';
            $version = self::short($m[1]);
        } elseif (preg_match('/Firefox\/([\d.]+)/i', $ua, $m)) {
            $browser = 'Firefox';
            $version = self::short($m[1]);
        } elseif (preg_match('/Chrome\/([\d.]+)/i', $ua, $m)) {
            // After Edge, Opera, Vivaldi, Brave — they all carry a Chrome token.
            $browser = 'Chrome';
            $version = self::short($m[1]);
        } elseif (preg_match('/Version\/([\d.]+).*Safari/i', $ua, $m)) {
            $browser = 'Safari';
            $version = self::short($m[1]);
        } elseif (preg_match('/Safari\/([\d.]+)/', $ua)) {
            $browser = 'Safari';
            $version = null;
        }

        $os = self::os($ua);

        return self::result($browser, $version, $os['name'], $os['version'], false);
    }

    /**
     * @return array{name: string, version: ?string}
     */
    private static function os(string $ua): array
    {
        // Mobile first (their tokens often co-occur with desktop ones).
        if (preg_match('/Android\s*([\d.]+)?/i', $ua, $m)) {
            return ['name' => 'Android', 'version' => $m[1] ?? null];
        }
        if (preg_match('/iPad/i', $ua)) {
            preg_match('/OS\s*([\d_]+)/i', $ua, $m);
            return ['name' => 'iPadOS', 'version' => isset($m[1]) ? self::dottedFromUnderscored($m[1]) : null];
        }
        if (preg_match('/iPhone|iPod/i', $ua)) {
            preg_match('/OS\s*([\d_]+)/i', $ua, $m);
            return ['name' => 'iOS', 'version' => isset($m[1]) ? self::dottedFromUnderscored($m[1]) : null];
        }
        if (preg_match('/Mac OS X\s*([\d_]+)?/i', $ua, $m)) {
            $v = isset($m[1]) ? self::dottedFromUnderscored($m[1]) : null;
            return ['name' => 'macOS', 'version' => $v];
        }
        if (preg_match('/Windows NT\s*([\d.]+)?/i', $ua, $m)) {
            $version = match ($m[1] ?? null) {
                '10.0'  => '10/11',
                '6.3'   => '8.1',
                '6.2'   => '8',
                '6.1'   => '7',
                '6.0'   => 'Vista',
                '5.1', '5.2' => 'XP',
                null    => null,
                default => $m[1] ?? null,
            };
            return ['name' => 'Windows', 'version' => $version];
        }
        if (stripos($ua, 'CrOS') !== false) {
            return ['name' => 'ChromeOS', 'version' => null];
        }
        if (stripos($ua, 'Linux') !== false) {
            return ['name' => 'Linux', 'version' => null];
        }

        return ['name' => 'Unknown', 'version' => null];
    }

    private static function short(string $version): ?string
    {
        if ($version === '') {
            return null;
        }
        $parts = explode('.', $version);
        return $parts[0];
    }

    private static function dottedFromUnderscored(string $v): string
    {
        return str_replace('_', '.', $v);
    }

    private static function title(string $s): string
    {
        $s = strtolower($s);
        return match ($s) {
            'httpie'             => 'HTTPie',
            'guzzlehttp'         => 'Guzzle',
            'postmanruntime'     => 'Postman',
            'go-http-client'     => 'Go-http',
            'python-requests'    => 'python-requests',
            'duckduckbot'        => 'DuckDuckBot',
            'linkedinbot'        => 'LinkedInBot',
            'discordbot'         => 'Discord',
            'telegrambot'        => 'Telegram',
            'whatsapp'           => 'WhatsApp',
            'facebookexternalhit'=> 'Facebook',
            'twitterbot'         => 'Twitter',
            'slackbot'           => 'Slack',
            'googlebot'          => 'Googlebot',
            'bingbot'            => 'Bingbot',
            'yandexbot'          => 'YandexBot',
            'applebot'           => 'Applebot',
            'baiduspider'        => 'Baidu',
            default              => ucfirst($s),
        };
    }

    /**
     * @return array{
     *     browser: string,
     *     browser_version: ?string,
     *     os: string,
     *     os_version: ?string,
     *     is_bot: bool,
     *     label: string,
     *     os_label: string,
     * }
     */
    private static function result(string $browser, ?string $version, string $os, ?string $osVersion, bool $isBot): array
    {
        $browserLabel = $version !== null ? "{$browser} {$version}" : $browser;
        $osLabel      = $osVersion !== null ? "{$os} {$osVersion}" : $os;

        return [
            'browser'         => $browser,
            'browser_version' => $version,
            'os'              => $os,
            'os_version'      => $osVersion,
            'is_bot'          => $isBot,
            'label'           => $browserLabel,
            'os_label'        => $osLabel,
        ];
    }
}
