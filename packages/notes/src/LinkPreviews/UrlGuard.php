<?php

namespace Coleus\Notes\LinkPreviews;

use Closure;

/**
 * Decides whether the server may fetch a URL for a link preview (A4.1),
 * so a note can't make it reach into the private network (SSRF):
 *
 * - http or https only, on the standard ports, no user:password@;
 * - the host must resolve, and every address it resolves to must be
 *   public: no loopback, private, link-local (cloud metadata), carrier-
 *   grade NAT, multicast or other reserved ranges, IPv4 or IPv6.
 *
 * It returns the address to connect to, so the request can be pinned to
 * it and DNS can't change its answer between the check and the fetch.
 */
class UrlGuard
{
    /**
     * @param  (Closure(string): list<string>)|null  $resolve  host => addresses (DNS by default; swappable in tests)
     */
    public function __construct(private ?Closure $resolve = null)
    {
        $this->resolve ??= self::dns(...);
    }

    /**
     * @return array{host: string, port: int, ip: string}|null null when the URL may not be fetched
     */
    public function check(string $url): ?array
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));

        if (! in_array($port, [80, 443], true)) {
            return null;
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : ($this->resolve)($host);

        if ($addresses === [] || array_filter($addresses, fn (string $ip) => ! self::isPublic($ip)) !== []) {
            return null;
        }

        return ['host' => $host, 'port' => $port, 'ip' => $addresses[0]];
    }

    public static function isPublic(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        // Ranges PHP's flags let through: carrier-grade NAT, IPv4-mapped/compatible IPv6, NAT64, 6to4, ULA, multicast.
        foreach (['100.64.0.0/10', '192.0.0.0/24', '198.18.0.0/15', '224.0.0.0/4', '::ffff:0:0/96', '64:ff9b::/96', '2002::/16', 'fc00::/7', 'fe80::/10', 'ff00::/8'] as $range) {
            if (self::inRange($ip, $range)) {
                return false;
            }
        }

        return true;
    }

    private static function inRange(string $ip, string $cidr): bool
    {
        [$network, $bits] = explode('/', $cidr);
        $address = inet_pton($ip);
        $base = inet_pton($network);

        if ($address === false || $base === false || strlen($address) !== strlen($base)) {
            return false;
        }

        $bytes = intdiv((int) $bits, 8);
        $rest = (int) $bits % 8;

        if (substr($address, 0, $bytes) !== substr($base, 0, $bytes)) {
            return false;
        }

        return $rest === 0 || (ord($address[$bytes]) >> (8 - $rest)) === (ord($base[$bytes]) >> (8 - $rest));
    }

    /**
     * @return list<string>
     */
    private static function dns(string $host): array
    {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA) ?: [];

        return array_values(array_filter(array_map(fn (array $record) => $record['ip'] ?? $record['ipv6'] ?? null, $records)));
    }
}
