<?php

namespace Pi\Core\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\Mvc\Exception\RuntimeException;
use Pi\Core\Handler\ErrorHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class ErrorMiddleware implements MiddlewareInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var ErrorHandler */
    protected ErrorHandler $errorHandler;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        ErrorHandler             $errorHandler
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->errorHandler    = $errorHandler;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (RuntimeException $e) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_NOT_FOUND);
            $request = $request->withAttribute(
                'error',
                [
                    'message' => $e->getMessage(),
                    //'trace'   => $e->getTraceAsString(),
                    'code'    => StatusCodeInterface::STATUS_NOT_FOUND,
                ]
            );
            return $this->errorHandler->handle($request);
        } catch (Throwable $e) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
            $request = $request->withAttribute(
                'error',
                [
                    'message' => $e->getMessage(),
                    //'trace'   => $e->getTraceAsString(),
                    'code'    => StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR,
                ]
            );
            return $this->errorHandler->handle($request);
        }
    }
}