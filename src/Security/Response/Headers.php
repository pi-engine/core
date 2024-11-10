<?php

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
            ->withHeader(
                'Content-Security-Policy',
                "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self';"
            )

            // Strict-Transport-Security (HSTS)
            ->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload')

            // X-Content-Type-Options
            ->withHeader('X-Content-Type-Options', 'nosniff')

            // X-Frame-Options
            ->withHeader('X-Frame-Options', 'DENY')

            // Referrer-Policy
            ->withHeader('Referrer-Policy', 'no-referrer')

            // Permissions-Policy
            ->withHeader('Permissions-Policy', "geolocation=(), microphone=(), camera=(), fullscreen=(), payment=(), usb=(), vibrate=(), sync-xhr=()")

            // X-XSS-Protection
            ->withHeader('X-XSS-Protection', '1; mode=block')

            // X-Permitted-Cross-Domain-Policies
            ->withHeader('X-Permitted-Cross-Domain-Policies', 'none')

            // Cross-Origin Resource Sharing (CORS)
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', $this->config['method']['allow_method'])
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, token')
            ->withHeader('Access-Control-Max-Age', '3600')

            // Expect-CT
            ->withHeader('Expect-CT', 'max-age=86400, enforce')

            // Cache-Control
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, proxy-revalidate')

            // X-Download-Options
            ->withHeader('X-Download-Options', 'noopen')

            // X-Powered-By
            ->withoutHeader('X-Powered-By');
    }
}