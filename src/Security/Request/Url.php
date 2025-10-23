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

    public function __construct(                       $config    ) {
        $this->config         = $config;
    }

    public function check(ServerRequestInterface $request, array $securityStream = []): array
    {
        $currentUrl = $this->getRequestUrl($request);

        // Check block list first
        if ($this->isBlacklisted($currentUrl, $this->config['url']['blacklist'])) {
            return [
                'result' => false,
                'name'   => $this->name,
                'status' => 'unsuccessful',
                'data'   => [
                    'client_url' => $currentUrl,
                    'in_blacklisted' => true,
                ],
            ];
        }

        return [
            'result' => true,
            'name'   => $this->name,
            'status' => 'successful',
            'data'   => [
                'client_url'    => $currentUrl,
                'in_blacklisted' => false,
            ],
        ];
    }

    /**
     * Get full request URL
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public function getRequestUrl(ServerRequestInterface $request): string
    {
        return (string) $request->getUri();
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