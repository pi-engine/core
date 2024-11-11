<?php

namespace Pi\Core\Handler;

use Laminas\Diactoros\Response\JsonResponse;
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

        // Set result
        return new JsonResponse(
            [
                'result' => false,
                'data'   => new stdClass,
                'error'  => $error,
                'status' => $status,
            ],
            $status
        );
    }
}
