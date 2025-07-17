<?php

namespace Pi\Core\Handler;

use Pi\Core\Response\EscapingJsonResponse;
use Pi\Core\Service\InstallerService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use stdClass;

class InstallerHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var InstallerService */
    protected InstallerService $installerService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        InstallerService         $installerService
    ) {
        $this->responseFactory  = $responseFactory;
        $this->streamFactory    = $streamFactory;
        $this->installerService = $installerService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $permissionFile = include realpath(__DIR__ . '/../../config/module.permission.php');

        $this->installerService->installPermission('core', $permissionFile);

        // Set result
        return new EscapingJsonResponse(
            [
                'result' => true,
                'data'   => new stdClass(),
                'error'  => new stdClass(),
            ],
        );
    }
}
