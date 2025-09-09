<?php

declare(strict_types=1);

namespace Pi\Core\Handler\Admin\Module;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Response\EscapingJsonResponse;
use Pi\Core\Service\ModuleService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ListHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var ModuleService */
    protected ModuleService $moduleService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        ModuleService            $moduleService
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->moduleService   = $moduleService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $moduleList = $this->moduleService->getModuleList();

        $result = [
            'result' => true,
            'data'   => $moduleList,
            'error'  => [],
        ];

        return new EscapingJsonResponse($result, StatusCodeInterface::STATUS_OK);
    }
}
