<?php

declare(strict_types=1);

namespace Pi\Core\Handler\Admin\Signature;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Response\EscapingJsonResponse;
use Pi\Core\Service\SignatureService;
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

    /** @var SignatureService */
    protected SignatureService $signatureService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        SignatureService         $signatureService
    ) {
        $this->responseFactory  = $responseFactory;
        $this->streamFactory    = $streamFactory;
        $this->signatureService = $signatureService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Get request
        $requestBody = $request->getParsedBody();

        // Start update
        $this->signatureService->updateAllSignatures($requestBody);

        // Set result
        $result = [
            'result' => true,
            'data'   => [
                'message' => 'All tables updated !',
            ],
            'error'  => [],
        ];

        return new EscapingJsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
    }
}