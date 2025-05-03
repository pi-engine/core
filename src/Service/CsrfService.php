<?php

declare(strict_types=1);

namespace Pi\Core\Service;

use Random\RandomException;

class CsrfService
{
    /* @var CacheService */
    protected CacheService $cacheService;

    /* @var array */
    protected array $config;

    public function __construct(
        CacheService $cacheService,
                     $config
    ) {
        $this->cacheService = $cacheService;
        $this->config       = $config;
    }

    /**
     * @throws RandomException
     */
    public function generateCsrfToken(array $userData): string
    {
        // Make token
        $token = bin2hex(random_bytes(32));

        // Set user fingerprint as hash
        $contextHash = $this->buildFingerprint($userData);

        // Set a key
        $key = "csrf_$token";

        // Save to cache
        $this->cacheService->setItem($key, ['context' => $contextHash, 'time_create' => time()], 600);

        return $token;
    }

    public function validateCsrfToken(string $token, array $userData): bool
    {
        // Set a key
        $key = "csrf_$token";

        // Set user fingerprint as hash
        $expectedHash = $this->buildFingerprint($userData);

        // Get token data from cache
        $storedHash = $this->cacheService->getItem($key);

        // Check token is set
        if (!$storedHash) {
            return false;
        }

        // Delete a token and cache, its one-time use
        $this->cacheService->deleteItem($key);

        return hash_equals($expectedHash, $storedHash['context']);
    }

    private function buildFingerprint(array $userData): string
    {
        $fingerprintParts = [
            $userData['geo']['ip'] ?? '',
            $userData['geo']['country_code'] ?? '',
            $userData['device']['is_robot'] ?? '',
            $userData['client']['user_agent'] ?? '',
        ];

        return hash('sha256', implode('|', $fingerprintParts));
    }
}