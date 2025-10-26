<?php

declare(strict_types=1);

namespace Pi\Core\Handler\Admin\System;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Response\EscapingJsonResponse;
use Pi\Core\Service\SystemService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class InformationHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var SystemService */
    protected SystemService $systemService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        SystemService            $systemService
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->systemService   = $systemService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $information = $this->systemService->getSystemInfo();

        $result = [
            'result' => true,
            'data'   => $information,
            'error'  => [],
        ];

        return new EscapingJsonResponse($result, StatusCodeInterface::STATUS_OK);
    }
}
