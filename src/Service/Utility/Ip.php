<?php

declare(strict_types=1);

namespace Pi\Core\Service\Utility;

use Exception;
use GeoIp2\Database\Reader;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\ServiceInterface;

class Ip implements ServiceInterface
{
    private static array $localIpRanges
        = [
            '127.0.0.1', '::1', '192.168.', '10.',
            '172.16.', '172.17.', '172.18.', '172.19.', '172.20.', '172.21.', '172.22.', '172.23.',
            '172.24.', '172.25.', '172.26.', '172.27.', '172.28.', '172.29.', '172.30.', '172.31.',
        ];

    /* @var CacheService|null */
    private ?CacheService $cacheService;

    public function __construct(CacheService $cacheService = null)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Get the real client IP address, considering proxies.
     *
     * @return string Client IP address
     */
    public function getClientIp(): string
    {
        $ipCandidates = $this->extractIpFromHeaders();

        // Check remote address as last fallback
        if (!empty($_SERVER['REMOTE_ADDR']) && $this->isValidIp($_SERVER['REMOTE_ADDR'])) {
            $ipCandidates[] = $_SERVER['REMOTE_ADDR'];
        }

        // Remove duplicates
        $ipCandidates = array_unique($ipCandidates);

        // Determine if running in CLI or local
        $isLocal = php_sapi_name() === 'cli' || (!$this->isPublicIp($_SERVER['REMOTE_ADDR'] ?? ''));

        // Prioritize first public IPv4
        foreach ($ipCandidates as $ip) {
            if ($this->isPublicIp($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $ip;
            }
        }

        // If no public IPv4 found, return first public IPv6
        foreach ($ipCandidates as $ip) {
            if ($this->isPublicIp($ip)) {
                return $ip;
            }
        }

        // Default fallback
        return $isLocal ? '127.0.0.1' : '0.0.0.0';
    }

    /**
     * Extracts potential IPs from HTTP headers.
     */
    private function extractIpFromHeaders(): array
    {
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_TRUE_CLIENT_IP',
            'HTTP_CF_PSEUDO_IPV4',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_X_ORIGINAL_FORWARDED_FOR',
        ];

        $ipCandidates = [];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ipList = explode(',', $_SERVER[$header]);
                foreach ($ipList as $ip) {
                    $cleanIp = trim($ip);
                    if ($this->isValidIp($cleanIp)) {
                        $ipCandidates[] = $cleanIp;
                    }
                }
            }
        }

        return $ipCandidates;
    }

    /**
     * Check if the given IP address is public.
     *
     * @param string $ip The IP address to check.
     *
     * @return bool True if the IP is public, false otherwise.
     */
    public function isPublicIp(string $ip): bool
    {
        foreach (self::$localIpRanges as $range) {
            if (str_starts_with($ip, $range)) {
                return false;
            }
        }
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    /**
     * Validate if a given IP is in the correct format.
     *
     * @param string $ip The IP address to validate.
     *
     * @return bool True if valid, false otherwise.
     */
    public function isValidIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Normalize an IP address format.
     *
     * @param string $ip The IP address to normalize.
     *
     * @return string Normalized IP address.
     */
    public function normalizeIp(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return inet_ntop(inet_pton($ip));
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return long2ip(ip2long($ip)); // Ensure consistent format
        }

        return $ip; // In case of invalid IP
    }

    /**
     * Check if an IP falls within a given CIDR range.
     *
     * @param string $ip   The IP address to check.
     * @param string $cidr The CIDR range.
     *
     * @return bool True if IP is in range, false otherwise.
     */
    public function isIpInRange(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        $ipBinary     = ip2long($ip);
        $subnetBinary = ip2long($subnet);
        $maskBinary   = -1 << (32 - (int)$mask);
        return ($ipBinary & $maskBinary) === ($subnetBinary & $maskBinary);
    }

    /**
     * Get GeoIP data for a given IP address.
     *
     * @param string $ip        The IP address.
     * @param string $geoDbPath Path to the GeoIP database.
     *
     * @return array The geolocation data.
     */
    public function getGeoIpData(string $ip, string $geoDbPath): array
    {
        $cacheKey = $this->sanitizeKey("geo_ip_{$ip}");

        // Check if cache service is available and if geo IP data is already cached
        if ($this->cacheService && $this->cacheService->hasItem($cacheKey)) {
            return [
                'result' => true,
                'data'   => $this->cacheService->getItem($cacheKey),
                'error'  => [],
            ];
        }

        if (!$this->isPublicIp($ip)) {
            return [
                'result' => true,
                'data'   => [
                    'ip'           => $ip,
                    'country'      => 'Local Network',
                    'country_code' => 'XX',
                    'city'         => 'Unknown',
                    'region'       => 'Unknown',
                    'region_code'  => 'Unknown',
                    'latitude'     => 0,
                    'longitude'    => 0,
                    'timezone'     => 'Unknown',
                ],
                'error'  => [],
            ];
        }

        if (!file_exists($geoDbPath)) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'GeoIP database not found',
                    'key'     => 'geoip-database-not-found',
                ],
            ];
        }

        try {
            $reader = new Reader($geoDbPath);
            $record = $reader->city($ip);

            // Set geo data
            $geoData = [
                'ip'           => $ip,
                'country'      => $record->country->name ?? 'Unknown',
                'country_code' => $record->country->isoCode ?? 'Unknown',
                'city'         => $record->city->name ?? 'Unknown',
                'region'       => $record->subdivisions[0]->name ?? 'Unknown',
                'region_code'  => $record->subdivisions[0]->isoCode ?? 'Unknown',
                'latitude'     => $record->location->latitude ?? 0,
                'longitude'    => $record->location->longitude ?? 0,
                'timezone'     => $record->location->timeZone ?? 'Unknown',
            ];

            // If cache is available, store the fetched data with a time-to-live (TTL)
            if ($this->cacheService) {
                $this->cacheService->setItem($cacheKey, $geoData, 3600); // 1 hour TTL
            }

            return [
                'result' => true,
                'data'   => $geoData,
                'error'  => [],
            ];
        } catch (Exception $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'GeoIP lookup failed: ' . $e->getMessage(),
                    'key'     => 'geoip-lookup-failed',
                ],
            ];
        }
    }

    /**
     * Check if an IP address is whitelisted.
     *
     * @param string $clientIp The client IP address.
     *
     * @return bool True if whitelisted, false otherwise.
     */
    public function isWhitelist(string $clientIp, array $whitelist): bool
    {
        foreach ($whitelist as $entry) {
            if ($this->ipMatches($clientIp, $entry)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if an IP address is blacklisted.
     *
     * @param string $clientIp The client IP address.
     *
     * @return bool True if blacklisted, false otherwise.
     */
    public function isBlacklisted(string $clientIp, array $blacklist): bool
    {
        foreach ($blacklist as $entry) {
            if ($this->ipMatches($clientIp, $entry)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if an IP matches a given rule (exact or CIDR notation).
     *
     * @param string $clientIp The client IP.
     * @param string $rule     The rule to check.
     *
     * @return bool True if matched, false otherwise.
     */
    private function ipMatches(string $clientIp, string $rule): bool
    {
        return str_contains($rule, '/') ? $this->isIpInRange($clientIp, $rule) : $clientIp === $rule;
    }

    /**
     * Checks if the given IP is from the specified country.
     *
     * @param string $ip          The IP address to check.
     * @param string $countryCode The two-letter country code (e.g., 'US').
     * @param string $geoDbPath   The path to the GeoIP database.
     *
     * @return bool True if the IP is from the specified country, false otherwise.
     */
    public function isIpFromCountry(string $ip, string $countryCode, string $geoDbPath): bool
    {
        $geoData = $this->getGeoIpData($ip, $geoDbPath);
        return $geoData['result'] && strtoupper($geoData['data']['country_code']) === strtoupper($countryCode);
    }

    /**
     * Checks if the request is using a proxy.
     *
     * @return bool True if the request is behind a proxy, false otherwise.
     */
    public function isUsingProxy(): bool
    {
        $proxyHeaders = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_X_CLUSTER_CLIENT_IP',
        ];

        foreach ($proxyHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitizes the cache key to ensure it meets the allowed format.
     *
     * @param string $key The original key
     *
     * @return string The sanitized key
     */
    private function sanitizeKey(string $key): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
    }
}
