<?php

declare(strict_types=1);

namespace Pi\Core\Service\Utility;

use Exception;
use GeoIp2\Database\Reader;
use Pi\Core\Service\ServiceInterface;

class Ip implements ServiceInterface
{
    private static array $localIpRanges
        = [
            '127.0.0.1', '::1', '192.168.', '10.',
            '172.16.', '172.17.', '172.18.', '172.19.', '172.20.', '172.21.', '172.22.', '172.23.',
            '172.24.', '172.25.', '172.26.', '172.27.', '172.28.', '172.29.', '172.30.', '172.31.',
        ];

    public function __construct()
    {
    }

    /**
     * Get the real client IP address, considering proxies.
     *
     * @return string Client IP address
     */
    public function getClientIp(): string
    {
        $ipCandidates = [];

        // List of common headers used by proxies, cloud services, and load balancers
        $headers = [
            'HTTP_X_FORWARDED_FOR',       // Used by most proxies/load balancers
            'HTTP_CLIENT_IP',             // Alternative client IP header
            'HTTP_X_REAL_IP',             // Common in Nginx proxy setups
            'HTTP_CF_CONNECTING_IP',      // Cloudflare
            'HTTP_X_FORWARDED',           // Less common, but seen in some setups
            'HTTP_FORWARDED_FOR',         // RFC-compliant proxy forwarding
            'HTTP_FORWARDED',             // Another RFC-compliant variant
            'HTTP_TRUE_CLIENT_IP',        // Akamai & some CDN providers
            'HTTP_CF_PSEUDO_IPV4',        // Cloudflare's pseudo IPv4 header for IPv6 clients
            'HTTP_X_CLUSTER_CLIENT_IP',   // Load balancers (e.g., AWS, Google Cloud, Kubernetes)
            'HTTP_X_ORIGINAL_FORWARDED_FOR', // Edge cases (Google Cloud, Azure)
        ];

        // Extract potential IPs from headers
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ipList = explode(',', $_SERVER[$header]);
                foreach ($ipList as $ip) {
                    $cleanIp = trim($ip);
                    if (filter_var($cleanIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        $ipCandidates[] = $cleanIp;
                    }
                }
            }
        }

        // Check remote address (last fallback, may be unreliable if behind proxy)
        if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            $ipCandidates[] = $_SERVER['REMOTE_ADDR'];
        }

        // Remove duplicates
        $ipCandidates = array_unique($ipCandidates);

        // If running locally, allow loopback IPs
        $isLocal = php_sapi_name() === 'cli' || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);

        // If multiple IPs exist, prioritize IPv4 over IPv6
        foreach ($ipCandidates as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $ip; // Return first valid IPv4 address
            }
        }

        // If no IPv4 found, return the first valid IP (likely IPv6)
        return $ipCandidates[0] ?? ($isLocal ? '127.0.0.1' : '0.0.0.0');
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
        return inet_ntop(inet_pton($ip));
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
                ],
            ];
        }

        try {
            $reader = new Reader($geoDbPath);
            $record = $reader->city($ip);

            return [
                'result' => true,
                'data'   => [
                    'ip'           => $ip,
                    'country'      => $record->country->name ?? 'Unknown',
                    'country_code' => $record->country->isoCode ?? 'Unknown',
                    'city'         => $record->city->name ?? 'Unknown',
                    'region'       => $record->subdivisions[0]->name ?? 'Unknown',
                    'region_code'  => $record->subdivisions[0]->isoCode ?? 'Unknown',
                    'latitude'     => $record->location->latitude ?? 0,
                    'longitude'    => $record->location->longitude ?? 0,
                    'timezone'     => $record->location->timeZone ?? 'Unknown',
                ],
                'error'  => [],
            ];
        } catch (Exception $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'GeoIP lookup failed: ' . $e->getMessage(),
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
        return strpos($rule, '/') !== false ? $this->isIpInRange($clientIp, $rule) : $clientIp === $rule;
    }
}
