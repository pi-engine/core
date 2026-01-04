<?php

declare(strict_types=1);

namespace Pi\Core\Security\Request;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;

class Origin implements RequestSecurityInterface
{
    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'origin';

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function check(ServerRequestInterface $request, array $securityStream = []): array
    {
        $ipType  = $securityStream['ip']['data']['ip_type'] ?? null;
        $urlType = $securityStream['url']['data']['client_type'] ?? null;
        if (is_null($ipType) || is_null($urlType)) {
            return [
                'result' => false,
                'name'   => $this->name,
                'status' => 'unsuccessful',
                'data'   => [],
            ];
        }

        $origin = $this->getRequestOrigin($ipType, $urlType);

        return [
            'result' => true,
            'name'   => $this->name,
            'status' => 'successful',
            'data'   => [
                'origin' => $origin,
            ],
        ];
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return 'Access denied: Unable to detect request origin';
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return StatusCodeInterface::STATUS_BAD_REQUEST;
    }

    /**
     * Determine the overall request origin type based on both IP and URL data.
     *
     * Priority logic:
     *  First check URL type; if not 'public', use URL type
     *  If URL type is 'public', check IP type
     *  If URL type is 'unknown' or not detectable, use IP type
     *  Otherwise → "unknown"
     *
     * @param $ipType
     * @param $urlType
     *
     * @return string One of: 'local', 'internal', 'public', or 'unknown'
     */
    public function getRequestOrigin($ipType, $urlType): string
    {
        // Normalize both to lowercase
        $ipType  = strtolower((string)$ipType);
        $urlType = strtolower((string)$urlType);

        // First priority: URL type determines if not 'public'
        if ($urlType === 'local' || $urlType === 'internal') {
            return $urlType;
        }

        // If URL type is 'public', check IP type for more specific classification
        if ($urlType === 'public') {
            // IP type can override 'public' to be more restrictive
            if ($ipType === 'local' || $ipType === 'internal') {
                return $ipType;
            }
            // Both are public
            if ($ipType === 'public') {
                return 'public';
            }
        }

        // If URL type is 'unknown' or empty, use IP type
        if ($urlType === 'unknown' || $urlType === '') {
            if (in_array($ipType, ['local', 'internal', 'public'])) {
                return $ipType;
            }
        }

        // If we have a valid IP type but URL type wasn't helpful
        if (in_array($ipType, ['local', 'internal', 'public'])) {
            return $ipType;
        }

        return 'unknown';
    }
}