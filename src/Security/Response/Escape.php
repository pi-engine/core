<?php

declare(strict_types=1);

namespace Pi\Core\Security\Response;

use Laminas\Diactoros\Stream;
use Laminas\Escaper\Escaper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Escape implements ResponseSecurityInterface
{
    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'escape';

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function process(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $escaper = new Escaper('utf-8');

        // Decode the JSON body into an array
        $bodyContent = (string)$response->getBody();
        $bodyArray   = json_decode($bodyContent, true);

        // Check if decoding was successful and that we have an array to work with
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($bodyArray)) {
            // Return the response unchanged if JSON decoding failed
            return $response;
        }

        // Escape all strings in the array recursively
        array_walk_recursive($bodyArray, function (&$value) use ($escaper) {
            if (is_string($value)) {
                $value = $escaper->escapeHtml($value);
            }
        });

        // Re-encode the modified array back to JSON
        $escapedBody = json_encode(
            $bodyArray,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK
        );

        // Handle JSON encoding errors
        if ($escapedBody === false) {
            // Return the response unchanged if JSON encoding failed
            return $response;
        }

        // Write the escaped JSON back to a new stream and attach it to the response
        $stream = new Stream('php://temp', 'wb+');
        $stream->write($escapedBody);
        $stream->rewind();

        return $response->withBody($stream);
    }
}