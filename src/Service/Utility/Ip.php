<?php

declare(strict_types=1);

namespace Pi\Core\Service\Utility;

use Exception;
use GeoIp2\Database\Reader;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\ServiceInterface;

class Ip implements ServiceInterface
{
    /* @var CacheService|null */
    private ?CacheService $cacheService;

    /* @var array */
    private array $config;

    public function __construct(
        $config,
        CacheService $cacheService = null,
    ) {
        $this->config       = $config;
        $this->cacheService = $cacheService;
    }

    /**
     * Get the real client IP address, considering proxies.
     *
     * @return string Client IP address
     */
    /**
     * Get the real client IP address, considering proxies.
     *
     * Prioritizes public IPv4, then public IPv6, then internal/local fallback.
     *
     * @return string The most likely client IP address.
     */
    public function getClientIp(): string
    {
        $ipCandidates = $this->extractIpFromHeaders();

        // Include REMOTE_ADDR as a fallback
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!empty($remoteAddr) && $this->isValidIp($remoteAddr)) {
            $ipCandidates[] = $remoteAddr;
        }

        // Remove duplicates and invalid IPs
        $ipCandidates = array_filter(array_unique($ipCandidates), fn($ip) => $this->isValidIp($ip));

        // Detect CLI or local environment
        $isCliOrLocal = php_sapi_name() === 'cli' || $this->getIpType($remoteAddr) === 'local';

        // Try to find first public IPv4
        foreach ($ipCandidates as $ip) {
            if ($this->getIpType($ip) === 'public' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $ip;
            }
        }

        // Fallback to first public IPv6
        foreach ($ipCandidates as $ip) {
            if ($this->getIpType($ip) === 'public') {
                return $ip;
            }
        }

        // Fallback to internal or local IP
        foreach ($ipCandidates as $ip) {
            $type = $this->getIpType($ip);
            if ($type === 'internal' || $type === 'local') {
                return $ip;
            }
        }

        // Default fallback
        return $isCliOrLocal ? '127.0.0.1' : '0.0.0.0';
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
     * Check if the given IP address is local or private.
     *
     * @param string $ip The IP address to check.
     *
     * @return bool True if the IP is local/private, false otherwise.
     */
    public function isLocalIp(string $ip): bool
    {
        // Check against known local/private ranges
        foreach ($this->config['local_ranges'] as $range) {
            if (str_starts_with($ip, $range)) {
                return true;
            }
        }

        // Check if IP is a private or reserved IP
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Check if the given IP address is considered internal.
     *
     * @param string $ip The IP address to check.
     *
     * @return bool True if the IP is internal, false otherwise.
     */
    public function isInternalIp(string $ip): bool
    {
        foreach ($this->config['internal_ranges'] as $range) {
            if (str_starts_with($ip, $range)) {
                return true;
            }
        }

        // Check if IP is a private or reserved IP
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Determine if the given IP is local, internal, or public.
     *
     * @param string $ip The IP address to check.
     *
     * @return string Returns one of: 'local', 'internal', or 'public'.
     */
    public function getIpType(string $ip): string
    {
        if ($this->isLocalIp($ip)) {
            return 'local';
        }

        if ($this->isInternalIp($ip)) {
            return 'internal';
        }

        return 'public';
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

        if ($this->getIpType($ip) !== 'public') {
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
     * Check if two IPs are the same (IPv4 or IPv6)
     *
     * @param string $ip1 First IP address
     * @param string $ip2 Second IP address
     *
     * @return bool True if both IPs are identical, false otherwise
     */
    function areIpsEqual(string $ip1, string $ip2): bool
    {
        // Convert IPs to binary format
        $ip1Normalized = @inet_pton($ip1);
        $ip2Normalized = @inet_pton($ip2);

        // If either IP is invalid, consider them different
        if ($ip1Normalized === false || $ip2Normalized === false) {
            return false;
        }

        // Compare
        return $ip1Normalized === $ip2Normalized;
    }

    /**
     * Check if an IP is in the allowed list (supports CIDR ranges)
     *
     * @param string $ip         The IP to check.
     * @param array  $allowedIps List of allowed IPs and CIDR subnets.
     *
     * @return bool True if allowed, otherwise false.
     */
    public function isIpAllowed(string $ip, array $allowedIps): bool
    {
        foreach ($allowedIps as $allowedIp) {
            if (str_contains($allowedIp, '/')) {
                // CIDR range check
                if ($this->isIpInRange($ip, $allowedIp)) {
                    return true;
                }
            } elseif ($ip === $allowedIp) {
                // Exact match
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
