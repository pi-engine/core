<?php

declare(strict_types=1);

namespace Pi\Core\Security\Request;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\Utility\Ip as IpUtility;
use Pi\Core\Service\UtilityService;
use Psr\Http\Message\ServerRequestInterface;

class Ip implements RequestSecurityInterface
{
    /* @var CacheService */
    protected CacheService $cacheService;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'ip';

    public function __construct(
        CacheService   $cacheService,
        UtilityService $utilityService,
                       $config
    ) {
        $this->cacheService   = $cacheService;
        $this->utilityService = $utilityService;
        $this->config         = $config;
    }

    /**
     * @param ServerRequestInterface $request
     * @param array                  $securityStream
     *
     * @return array
     */
    public function check(ServerRequestInterface $request, array $securityStream = []): array
    {
        // Set ip class
        $ipUtility = new IpUtility();
        $clientIp  = $ipUtility->getClientIp();
        $ipType    = $ipUtility->getIpType($clientIp);

        // Check ip is not lock
        if ($this->isIpLocked($clientIp)) {
            return [
                'result' => false,
                'name'   => $this->name,
                'status' => 'unsuccessful',
                'data'   => [
                    'client_ip'      => $clientIp,
                    'ip_type'        => $ipType,
                    'is_locked'      => true,
                    'in_whitelist'   => false,
                    'in_blacklisted' => false,
                ],
            ];
        }

        // Check allow-list
        if ($ipUtility->isWhitelist($clientIp, $this->config['ip']['whitelist'])) {
            return [
                'result' => true,
                'name'   => $this->name,
                'status' => 'successful',
                'data'   => [
                    'client_ip'      => $clientIp,
                    'ip_type'        => $ipType,
                    'is_locked'      => false,
                    'in_whitelist'   => true,
                    'in_blacklisted' => false,
                ],
            ];
        }

        // Check blacklist
        if ($ipUtility->isBlacklisted($clientIp, $this->config['ip']['blacklist'])) {
            return [
                'result' => false,
                'name'   => $this->name,
                'status' => 'unsuccessful',
                'data'   => [
                    'client_ip'      => $clientIp,
                    'ip_type'        => $ipType,
                    'is_locked'      => false,
                    'in_whitelist'   => false,
                    'in_blacklisted' => true,
                ],
            ];
        }

        return [
            'result' => true,
            'name'   => $this->name,
            'status' => 'successful',
            'data'   => [
                'client_ip'      => $clientIp,
                'ip_type'        => $ipType,
                'is_locked'      => false,
                'in_whitelist'   => false,
                'in_blacklisted' => false,
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