<?php

declare(strict_types=1);

namespace Pi\Core\Security\Request;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Service\Utility\Url as UrlUtility;
use Psr\Http\Message\ServerRequestInterface;

class Url implements RequestSecurityInterface
{
    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'url';

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function check(ServerRequestInterface $request, array $securityStream = []): array
    {
        $urlUtility = new UrlUtility();
        $clientUrl  = $urlUtility->getCallerUrl($request, $securityStream);
        $clientType = $urlUtility->getCallerUrlType($clientUrl, $securityStream, $this->config['url']['internal_urls']);


        // Check block list first
        if (!empty($clientUrl)) {
            if ($this->isBlacklisted($clientUrl, $this->config['url']['blacklist'])) {
                return [
                    'result' => false,
                    'name'   => $this->name,
                    'status' => 'unsuccessful',
                    'data'   => [
                        'client_url'     => $clientUrl,
                        'client_type'    => $clientType,
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
                'client_url'     => $clientUrl,
                'client_type'    => $clientType,
                'in_blacklisted' => false,
            ],
        ];
    }

    /**
     * Check if URL is in the blocked list
     */
    public function isBlacklisted(string $clientUrl, array $blockedUrls): bool
    {
        foreach ($blockedUrls as $url) {
            if (str_starts_with($clientUrl, $url)) {
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