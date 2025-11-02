<?php

declare(strict_types=1);

namespace Pi\Core\Security\Response;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Headers implements ResponseSecurityInterface
{
    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'header';

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function process(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response
            // Content Security Policy (CSP)
            ->withHeader('Content-Security-Policy', $this->config['header']['options']['content_security_policy'])

            // Strict-Transport-Security (HSTS)
            ->withHeader('Strict-Transport-Security', $this->config['header']['options']['strict_transport_security'])

            // X-Content-Type-Options
            ->withHeader('X-Content-Type-Options', $this->config['header']['options']['x_content_type_options'])

            // X-Frame-Options
            ->withHeader('X-Frame-Options', $this->config['header']['options']['x_frame_options'])

            // X-XSS-Protection
            ->withHeader('X-XSS-Protection', $this->config['header']['options']['x_xss_protection'])

            // Referrer-Policy
            ->withHeader('Referrer-Policy', $this->config['header']['options']['referrer_policy'])

            // Permissions-Policy
            ->withHeader('Permissions-Policy', $this->config['header']['options']['permissions_policy'])

            // X-Permitted-Cross-Domain-Policies
            ->withHeader('X-Permitted-Cross-Domain-Policies', 'none')

            // Cross-Origin Resource Sharing (CORS)
            ->withHeader('Access-Control-Allow-Origin', $this->config['cors']['allowed_origins'])
            ->withHeader('Access-Control-Allow-Methods', $this->config['cors']['allowed_methods'])
            ->withHeader('Access-Control-Allow-Headers', $this->config['cors']['allowed_headers'])
            ->withHeader('Access-Control-Max-Age', '3600')

            // Expect-CT
            ->withHeader('Expect-CT', 'max-age=86400, enforce')

            // Cache-Control
            ->withHeader('Cache-Control', $this->config['header']['options']['cache_control'])

            // X-Download-Options
            ->withHeader('X-Download-Options', 'noopen')

            // X-Powered-By
            ->withoutHeader('X-Powered-By');
    }
}