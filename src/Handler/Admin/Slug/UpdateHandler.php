<?php

declare(strict_types=1);

namespace Pi\Core\Handler\Admin\Slug;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Response\EscapingJsonResponse;
use Pi\Core\Service\SlugService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UpdateHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var SlugService */
    protected SlugService $slugService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        SlugService              $slugService
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->slugService     = $slugService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Get request
        $requestBody = $request->getParsedBody();

        // Start update
        $this->slugService->updateAllSlugs($requestBody);

        // Set result
        $result = [
            'result' => true,
            'data'   => [
                'message' => 'All tables updated !',
                'key'     => 'all-tables-updated',
            ],
            'error'  => [],
        ];

        return new EscapingJsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
    }
}