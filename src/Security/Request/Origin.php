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
                'origin'     => $origin,
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
     *  If either IP or URL is local → "local"
     *  Else if either IP or URL is internal → "internal"
     *  Else if both are public → "public"
     *  Otherwise, → "unknown"
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

        // Priority rules
        if ($ipType === 'local' || $urlType === 'local') {
            return 'local';
        }

        if ($ipType === 'internal' || $urlType === 'internal') {
            return 'internal';
        }

        if ($ipType === 'public' && $urlType === 'public') {
            return 'public';
        }

        return 'unknown';
    }
}