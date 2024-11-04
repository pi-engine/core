<?php

namespace Core\Security\Response;

use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Compress implements ResponseSecurityInterface
{
    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'compress';

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function process(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (isset($this->config['compress']['is_active']) && $this->config['compress']['is_active']) {

            if ($this->canCompress($request)) {
                $body           = (string)$response->getBody();
                $compressedBody = gzencode($body, 9);

                // Create a new stream with the compressed body
                $stream = new Stream('php://temp', 'wb+');
                $stream->write($compressedBody);
                $stream->rewind();

                // Return the response with the compressed body
                return $response
                    ->withBody($stream)
                    ->withHeader('Content-Encoding', 'gzip')
                    ->withHeader('Content-Length', strlen($compressedBody));
            }
        }

        return $response;
    }

    private function canCompress(ServerRequestInterface $request): bool
    {
        // Look for gzip in the Accept-Encoding header of the request
        $acceptEncoding = $request->getHeaderLine('Accept-Encoding');
        return str_contains($acceptEncoding, 'gzip');
    }
}