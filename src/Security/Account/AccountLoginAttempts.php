<?php

namespace Core\Security\Account;

use Fig\Http\Message\StatusCodeInterface;
use User\Service\CacheService;

class AccountLoginAttempts implements AccountSecurityInterface
{
    /* @var CacheService */
    protected CacheService $cacheService;

    /** @var AccountLocked */
    protected AccountLocked $accountLocked;

    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'accountLoginAttempts';

    public function __construct(
        CacheService  $cacheService,
        AccountLocked $accountLocked,
                      $config
    ) {
        $this->cacheService  = $cacheService;
        $this->accountLocked = $accountLocked;
        $this->config        = $config;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function incrementFailedAttempts(array $params): array
    {
        // Set key
        switch ($params['type']) {
            default:
            case 'id':
                $keyAttempts = "account_login_attempts_{$params['user_id']}";
                break;

            case 'ip':
                $keyAttempts = $this->sanitizeKey("ip_login_attempts_{$params['user_ip']}");
                break;
        }

        // Check account is lock or not
        if ($this->accountLocked->isLocked($params)) {
            return [
                'can_try' => false,
            ];
        }

        // Get and update attempts
        $attempts = $this->cacheService->getItem($keyAttempts);
        if (empty($attempts)) {
            $attempts = $this->cacheService->setItem($keyAttempts, ['count' => 1], $this->config['account']['ttl']);
        } else {
            $attempts = $this->cacheService->setItem($keyAttempts, ['count' => $attempts['count'] + 1], $this->config['account']['ttl']);
        }

        if ($attempts['count'] >= $this->config['account']['attempts']) {
            $this->accountLocked->doLocked($params);
        }

        return [
            'can_try'         => true,
            'attempts_count'  => $attempts['count'],
            'attempts_remind' => $this->config['account']['attempts'] - $attempts['count'],
        ];
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return 'Access denied: Your account is locked due to too many failed login attempts. Please try again after 1 hour.';
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