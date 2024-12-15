<?php

declare(strict_types=1);

namespace Pi\Core\Security\Request;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Service\CacheService;
use Psr\Http\Message\ServerRequestInterface;

class Ip implements RequestSecurityInterface
{
    /* @var CacheService */
    protected CacheService $cacheService;

    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'ip';

    public function __construct(
        CacheService $cacheService,
                     $config
    ) {
        $this->cacheService = $cacheService;
        $this->config       = $config;
    }

    /**
     * @param ServerRequestInterface $request
     * @param array                  $securityStream
     *
     * @return array
     */
    public function check(ServerRequestInterface $request, array $securityStream = []): array
    {
        // Get client ip
        $clientIp = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';

        // Check ip is not lock
        if ($this->isIpLocked($clientIp)) {
            return [
                'result' => false,
                'name'   => $this->name,
                'status' => 'unsuccessful',
                'data'   => [],
            ];
        }

        // Check allow-list
        if ($this->isWhitelist($clientIp)) {
            return [
                'result' => true,
                'name'   => $this->name,
                'status' => 'successful',
                'data'   => [
                    'client_ip'    => $clientIp,
                    'in_whitelist' => true,
                ],
            ];
        }

        // Check blacklist
        if ($this->isBlacklisted($clientIp)) {
            return [
                'result' => false,
                'name'   => $this->name,
                'status' => 'unsuccessful',
                'data'   => [
                    'in_blacklisted' => true,
                ],
            ];
        }

        return [
            'result' => true,
            'name'   => $this->name,
            'status' => 'successful',
            'data'   => [
                'client_ip'    => $clientIp,
                'in_whitelist' => false,
            ],
        ];
    }

    /**
     * Checks if the IP is in the allow-list.
     *
     * @param string $clientIp
     *
     * @return bool
     */
    public function isIpLocked(string $clientIp): bool
    {
        $keyLocked = $this->sanitizeKey("locked_ip_{$clientIp}");
        if ($this->cacheService->hasItem($keyLocked)) {
            return true;
        }
        return false;
    }

    /**
     * Checks if the IP is in the allow-list.
     *
     * @param string $clientIp
     *
     * @return bool
     */
    public function isWhitelist(string $clientIp): bool
    {
        foreach ($this->config['ip']['whitelist'] as $entry) {
            if ($this->ipMatches($clientIp, $entry)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the IP is in the blacklist.
     *
     * @param string $clientIp
     *
     * @return bool
     */
    public function isBlacklisted(string $clientIp): bool
    {
        foreach ($this->config['ip']['blacklist'] as $entry) {
            if ($this->ipMatches($clientIp, $entry)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if an IP matches a given rule (single IP or range).
     *
     * @param string $clientIp
     * @param string $rule
     *
     * @return bool
     */
    private function ipMatches(string $clientIp, string $rule): bool
    {
        if (strpos($rule, '/') !== false) {
            // Handle CIDR notation for IP ranges
            return $this->ipInRange($clientIp, $rule);
        }

        // Handle single IP addresses
        return $clientIp === $rule;
    }

    /**
     * Checks if an IP is within a specified IP range.
     *
     * @param string $clientIp
     * @param string $cidr
     *
     * @return bool
     */
    private function ipInRange(string $clientIp, string $cidr): bool
    {
        [$range, $prefix] = explode('/', $cidr, 2);
        $prefix = (int)$prefix;

        // Convert IP addresses to binary format
        $clientIp = inet_pton($clientIp);
        $range    = inet_pton($range);

        // Calculate the subnet mask
        $mask = Ip . phpstr_repeat('1', $prefix) . str_repeat('0', 128 - $prefix);
        $mask = pack('H*', str_pad(base_convert($mask, 2, 16), 32, '0', STR_PAD_LEFT));

        // Apply the subnet mask to the range and IP
        $range    = $range & $mask;
        $clientIp = $clientIp & $mask;

        return $range === $clientIp;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return 'Access denied: Bad IP';
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return StatusCodeInterface::STATUS_BAD_REQUEST;
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