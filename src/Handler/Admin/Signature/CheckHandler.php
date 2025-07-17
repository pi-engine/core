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

class CheckHandler implements RequestHandlerInterface
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

        // Start check
        $checkResult = $this->signatureService->checkAllSignatures($requestBody);

        // Set result
        $result = [
            'result' => true,
            'data'   => $checkResult,
            'error'  => [],
        ];

        return new EscapingJsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
    }
}