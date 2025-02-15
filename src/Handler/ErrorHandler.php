<?php

declare(strict_types=1);

namespace Pi\Core\Handler;

use Pi\Core\Response\EscapingJsonResponse;
use Pi\Core\Service\UtilityService;
use Pi\Logger\Service\LoggerService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LogLevel;
use stdClass;

class ErrorHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /** @var LoggerService */
    protected LoggerService $loggerService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        UtilityService           $utilityService,
        LoggerService            $loggerService
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->utilityService  = $utilityService;
        $this->loggerService   = $loggerService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $error  = $request->getAttribute('error');
        $status = $request->getAttribute('status');
        $header = $request->getAttribute('header', []);

        // Set result
        $response = new EscapingJsonResponse(
            [
                'result' => false,
                'data'   => new stdClass,
                'error'  => $error,
                'status' => $status,
            ],
            $status,
            $header
        );

        // Post-handler logic
        $this->writeRequestResponse($request, $response, $error);

        return $response;
    }

    private function writeRequestResponse(ServerRequestInterface $request, ResponseInterface $response, $error): void
    {
        // Get attributes
        $attributes = $request->getAttributes();

        // Get route information
        $routeMatch  = $request->getAttribute('Laminas\Router\RouteMatch');
        $routeParams = $routeMatch->getParams();

        // Set path
        $path = sprintf(
            '%s-%s-%s-%s',
            $routeParams['module'],
            $routeParams['section'],
            $routeParams['package'],
            $routeParams['handler']
        );

        // Set message
        $message = 'An unspecified error occurred';
        if (isset($error['message']) && !empty($error['message']) && isset($routeParams['title']) && !empty($routeParams['title'])) {
            $message = sprintf('%s - %s', $error['message'], $routeParams['title']);
        }

        // Set log params
        $params = [
            'path'        => $path,
            'message'     => $message,
            'user_id'     => $attributes['account']['id'] ?? 0,
            'company_id'  => $attributes['company_authorization']['company_id'] ?? 0,
            'ip'          => $this->utilityService->getClientIp(),
            'route'       => $routeParams,
            'timestamp'   => $this->utilityService->getTime(),
            'time_create' => time(),
            'request'     => [
                'method'          => $request->getMethod(),
                'uri'             => (string)$request->getUri(),
                'headers'         => $request->getHeaders(),
                'body'            => (string)$request->getBody(),
                'protocolVersion' => $request->getProtocolVersion(),
                'serverParams'    => $request->getServerParams(),
                'queryParams'     => $request->getQueryParams(),
                'parsedBody'      => $request->getParsedBody(),
                'uploadedFiles'   => $request->getUploadedFiles(),
                'cookies'         => $request->getCookieParams(),
                'attributes'      => $request->getAttributes(),
                'target'          => $request->getRequestTarget(),
            ],
            'response'    => [
                //'body'            => $response->getBody(),
                'headers'         => $response->getHeaders(),
                'protocolVersion' => $response->getProtocolVersion(),
                'encodingOptions' => $response->getEncodingOptions(),
                //'payload'         => $response->getPayload(),
                'reasonPhrase'    => $response->getReasonPhrase(),
                'statusCode'      => $response->getStatusCode(),
            ],
        ];

        // Set log
        $this->loggerService->write($params, LogLevel::ERROR);
    }
}
