<?php

namespace Core\Security\Account;

use Fig\Http\Message\StatusCodeInterface;
use User\Service\CacheService;

class AccountLocked implements AccountSecurityInterface
{
    /* @var CacheService */
    protected CacheService $cacheService;

    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'accountLocked';

    public function __construct(
        CacheService $cacheService,
                     $config
    ) {
        $this->cacheService = $cacheService;
        $this->config       = $config;
    }

    /**
     * @param array $params
     *
     * @return void
     */
    public function doLocked(array $params): void
    {
        // ip in whitelist
        $inWhitelist = $params['security_stream']['ip']['data']['in_whitelist'] ?? false;

        // Set key
        switch ($params['type']) {
            default:
            case 'id':
                $keyLocked = "locked_account_{$params['user_id']}";
                break;

            case 'ip':
                if (!$inWhitelist) {
                    $keyLocked = $this->sanitizeKey("locked_ip_{$params['user_ip']}");
                }
                break;
        }

        // do lock
        if (isset($keyLocked)) {
            $this->cacheService->setItem(
                $keyLocked,
                ['locked_from' => time(), 'locked_to' => time() + $this->config['account']['ttl']],
                $this->config['account']['ttl']
            );
        }
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function isLocked(array $params): bool
    {
        // Set key
        switch ($params['type']) {
            default:
            case 'id':
                $keyLocked = "locked_account_{$params['user_id']}";
                break;

            case 'ip':
                $userIp    = $params['user_ip'] ?? $_SERVER['REMOTE_ADDR'];
                $keyLocked = $this->sanitizeKey("locked_ip_{$userIp}");
                break;
        }

        // Check is locked
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
        $ttl = $this->config['account']['ttl'];
        if ($ttl < 3600) {
            $minutes = floor(($ttl % 3600) / 60);
            $message = sprintf('Access denied: Your account is locked due to too many failed login attempts. Please try again after %s minutes.', $minutes);
        } elseif ($ttl < 86400) {
            $hours   = floor(($ttl % 86400) / 3600);
            $message = sprintf('Access denied: Your account is locked due to too many failed login attempts. Please try again after %s hours.', $hours);
        } else {
            $days    = floor($ttl / 86400);
            $hours   = floor(($ttl % 86400) / 3600);
            $message = sprintf(
                'Access denied: Your account is locked due to too many failed login attempts. Please try again after %s days, %s hours.', $days, $hours
            );
        }

        return $message;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return StatusCodeInterface::STATUS_UNAUTHORIZED;
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