<?php

declare(strict_types=1);

namespace Pi\Core\Service\Utility;

use Pi\Core\Service\ServiceInterface;
use Psr\Http\Message\ServerRequestInterface;

class Url implements ServiceInterface
{
    public function __construct()
    {
    }

    /**
     * Get the caller URL for a request, considering headers and IP type.
     *
     * @param ServerRequestInterface $request
     * @param array                  $securityStream Security info including IP type
     *
     * @return string Normalized URL or IP-based fallback
     */
    public function getCallerUrl(ServerRequestInterface $request, array $securityStream): string
    {
        // Custom header for backend/microservice requests
        $clientOrigin = $request->getHeaderLine('X-Client-Origin');
        if (!empty($clientOrigin)) {
            return $this->normalizeUrl($clientOrigin);
        }

        // Browser-originated request
        $origin = $request->getHeaderLine('Origin');
        if (!empty($origin)) {
            return $this->normalizeUrl($origin);
        }

        // Fallback to Referer
        $referer = $request->getHeaderLine('Referer');
        if (!empty($referer)) {
            $parts = parse_url($referer);
            if (!empty($parts['scheme']) && !empty($parts['host'])) {
                $url = $parts['scheme'] . '://' . $parts['host'];
                if (!empty($parts['port'])) {
                    $url .= ':' . $parts['port'];
                }
                return $this->normalizeUrl($url);
            }
        }

        // Final fallback — client IP or localhost
        $ip     = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ipType = $securityStream['ip']['data']['ip_type'] ?? null;

        if ($ipType === 'local') {
            // Detect scheme and port dynamically
            $scheme       = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $port         = $_SERVER['SERVER_PORT'] ?? 80;
            $localhostUrl = $scheme . '://localhost' . (($port == 80 || $port == 443) ? '' : ':' . $port);
            return $this->normalizeUrl($localhostUrl);
        }

        return 'ip://' . $ip;
    }

    /**
     * Determine if the caller URL is local, internal, or public.
     *
     * @param string $callerUrl
     * @param array  $securityStream Contains IP data including type (local, internal, public)
     * @param array  $internalUrls   Optional list of known internal base URLs or domains
     *
     * @return string One of: 'local', 'internal', 'public', or 'unknown'
     */
    public function getCallerUrlType(string $callerUrl, array $securityStream, array $internalUrls = []): string
    {
        $ipType = $securityStream['ip']['data']['ip_type'] ?? null;

        // 1. Localhost or loopback check
        if (str_contains($callerUrl, 'localhost') || str_contains($callerUrl, '127.0.0.1')) {
            return 'local';
        }

        // 2. Check against predefined internal URLs/domains
        foreach ($internalUrls as $internalUrl) {
            if (stripos($callerUrl, $internalUrl) !== false) {
                return 'internal';
            }
        }

        // 3. Fallback to IP-based type (from $securityStream)
        $validTypes = ['local', 'internal', 'public'];
        if (in_array($ipType, $validTypes, true)) {
            return $ipType;
        }

        return 'unknown';
    }

    /**
     * Check if URL is allowed
     *
     * @param string $url
     * @param array  $allowedUrls
     *
     * @return bool
     */
    public function isUrlAllowed(string $url, array $allowedUrls): bool
    {
        foreach ($allowedUrls as $allowed) {
            if (str_starts_with($url, $allowed)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Normalize and sanitize a URL (consistent host lowercase and trailing slash)
     *
     * @param string $url
     *
     * @return string Normalized URL
     */
    private function normalizeUrl(string $url): string
    {
        $url   = trim($url);
        $parts = parse_url($url);

        if (empty($parts['scheme']) || empty($parts['host'])) {
            return $url; // return as-is if scheme/host missing
        }

        $normalized = strtolower($parts['scheme']) . '://' . strtolower($parts['host']);
        if (!empty($parts['port'])) {
            $normalized .= ':' . $parts['port'];
        }

        if (empty($parts['path'])) {
            $normalized .= '/';
        }

        return $normalized;
    }
}