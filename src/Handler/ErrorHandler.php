<?php

declare(strict_types=1);

namespace Pi\Core\Handler;

use Pi\Core\Response\EscapingJsonResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use stdClass;

class ErrorHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $error  = $request->getAttribute('error');
        $status = $request->getAttribute('status');
        $header = $request->getAttribute('header', []);

        // Set result
        return new EscapingJsonResponse(
            [
                'result' => false,
                'data'   => new stdClass,
                'error'  => $error,
                'status' => $status,
            ],
            $status,
            $header
        );
    }
}
