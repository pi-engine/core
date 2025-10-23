<?php

declare(strict_types=1);

namespace Pi\Core\Security\Request;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;

class Url implements RequestSecurityInterface
{
    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'ip';

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function check(ServerRequestInterface $request, array $securityStream = []): array
    {
        $currentUrl = $this->getCallerUrl($request);

        // Check block list first
        if (!empty($currentUrl)) {
            if ($this->isBlacklisted($currentUrl, $this->config['url']['blacklist'])) {
                return [
                    'result' => false,
                    'name'   => $this->name,
                    'status' => 'unsuccessful',
                    'data'   => [
                        'client_url'     => $currentUrl,
                        'in_blacklisted' => true,
                    ],
                ];
            }
        }

        return [
            'result' => true,
            'name'   => $this->name,
            'status' => 'successful',
            'data'   => [
                'client_url'     => $currentUrl,
                'in_blacklisted' => false,
            ],
        ];
    }

    /**
     * Detect and return the URL (origin) of the caller making the request.
     * Tries custom headers first, then standard browser headers,
     * and finally falls back to the client IP address.
     */
    public function getCallerUrl(ServerRequestInterface $request): string
    {
        // Custom header for backend or microservice requests
        $clientOrigin = $request->getHeaderLine('X-Client-Origin');
        if (!empty($clientOrigin)) {
            return $this->normalizeUrl($clientOrigin);
        }

        // Browser-originated request
        $origin = $request->getHeaderLine('Origin');
        if (!empty($origin)) {
            return $this->normalizeUrl($origin);
        }

        // Fallback to Referer (older browsers or redirects)
        $referer = $request->getHeaderLine('Referer');
        if (!empty($referer)) {
            // Strip down to scheme + host
            $parts = parse_url($referer);
            if (!empty($parts['scheme']) && !empty($parts['host'])) {
                $url = $parts['scheme'] . '://' . $parts['host'];
                if (!empty($parts['port'])) {
                    $url .= ':' . $parts['port'];
                }
                return $this->normalizeUrl($url);
            }
        }

        // Final fallback — client IP (for internal services or CLI)
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return 'ip://' . $ip;
    }

    /**
     * Normalize and sanitize a URL (ensure consistent trailing slash and lowercase host)
     */
    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);

        if (empty($parts['scheme']) || empty($parts['host'])) {
            return $url;
        }

        $normalized = strtolower($parts['scheme']) . '://' . strtolower($parts['host']);
        if (!empty($parts['port'])) {
            $normalized .= ':' . $parts['port'];
        }

        // Optionally, ensure consistent trailing slash for host-only URLs
        if (empty($parts['path'])) {
            $normalized .= '/';
        }

        return $normalized;
    }

    /**
     * Check if URL is in the blocked list
     */
    public function isBlacklisted(string $currentUrl, array $blockedUrls): bool
    {
        foreach ($blockedUrls as $url) {
            if (str_starts_with($currentUrl, $url)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return 'Access denied: URL not allowed or blocked';
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return StatusCodeInterface::STATUS_BAD_REQUEST;
    }
}