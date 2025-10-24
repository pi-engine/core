<?php

declare(strict_types=1);

namespace Pi\Core\Middleware;

use Pi\Core\Handler\ErrorHandler;
use Pi\Core\Security\Request\Csrf as RequestSecurityCsrf;
use Pi\Core\Security\Request\Injection as RequestSecurityInjection;
use Pi\Core\Security\Request\InputSizeLimit as RequestSecurityInputSizeLimit;
use Pi\Core\Security\Request\InputValidation as RequestSecurityInputValidation;
use Pi\Core\Security\Request\Ip as RequestSecurityIp;
use Pi\Core\Security\Request\Method as RequestSecurityMethod;
use Pi\Core\Security\Request\Origin as RequestSecurityOrigin;
use Pi\Core\Security\Request\RequestLimit as RequestSecurityRequestLimit;
use Pi\Core\Security\Request\Url as RequestSecurityUrl;
use Pi\Core\Security\Request\UserData as RequestUserData;
use Pi\Core\Security\Request\Xss as RequestSecurityXss;
use Pi\Core\Security\Response\Compress as ResponseCompress;
use Pi\Core\Security\Response\Headers as ResponseHeaders;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\CsrfService;
use Pi\Core\Service\UtilityService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SecurityMiddleware implements MiddlewareInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /* @var CacheService */
    protected CacheService $cacheService;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /** @var CsrfService */
    protected CsrfService $csrfService;

    /** @var ErrorHandler */
    protected ErrorHandler $errorHandler;

    /* @var array */
    protected array $config;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        CacheService             $cacheService,
        UtilityService           $utilityService,
        CsrfService              $csrfService,
        ErrorHandler             $errorHandler,
                                 $config
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->cacheService    = $cacheService;
        $this->utilityService  = $utilityService;
        $this->csrfService     = $csrfService;
        $this->errorHandler    = $errorHandler;
        $this->config          = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Start security checks in request
        $securityStream = [];
        foreach ($this->securityRequestList() as $key => $security) {
            $securityStream[$key] = $security->check($request, $securityStream);

            // Set error
            if (!$securityStream[$key]['result']) {
                $request = $request->withAttribute('status', $security->getStatusCode());
                $request = $request->withAttribute(
                    'error',
                    [
                        'message' => $security->getErrorMessage(),
                        'code'    => $security->getStatusCode(),
                    ]
                );
                return $this->errorHandler->handle($request);
            }
        }

        // Set security attribute
        $request = $request->withAttribute('security_stream', $securityStream);

        // Call the next middleware or handler
        $response = $handler->handle($request);

        // Start security checks in response
        foreach ($this->securityResponseList() as $security) {
            $response = $security->process($request, $response);
        }

        // Set response
        return $response;
    }

    protected function securityRequestList(): array
    {
        $list = [];
        if (isset($this->config['ip']['is_active']) && $this->config['ip']['is_active']) {
            $list['ip'] = new RequestSecurityIp($this->cacheService, $this->utilityService, $this->config);
        }
        if (isset($this->config['url']['is_active']) && $this->config['url']['is_active']) {
            $list['url'] = new RequestSecurityUrl($this->config);
        }
        if (isset($this->config['origin']['is_active']) && $this->config['origin']['is_active']) {
            $list['origin'] = new RequestSecurityOrigin($this->config);
        }
        if (isset($this->config['userData']['is_active']) && $this->config['userData']['is_active']) {
            $list['userData'] = new RequestUserData($this->cacheService, $this->utilityService, $this->config);
        }
        if (isset($this->config['method']['is_active']) && $this->config['method']['is_active']) {
            $list['method'] = new RequestSecurityMethod($this->config);
        }
        if (isset($this->config['inputSizeLimit']['is_active']) && $this->config['inputSizeLimit']['is_active']) {
            $list['inputSizeLimit'] = new RequestSecurityInputSizeLimit($this->config);
        }
        if (isset($this->config['requestLimit']['is_active']) && $this->config['requestLimit']['is_active']) {
            $list['requestLimit'] = new RequestSecurityRequestLimit($this->cacheService, $this->utilityService, $this->config);
        }
        if (isset($this->config['xss']['is_active']) && $this->config['xss']['is_active']) {
            $list['xss'] = new RequestSecurityXss($this->config);
        }
        if (isset($this->config['injection']['is_active']) && $this->config['injection']['is_active']) {
            $list['injection'] = new RequestSecurityInjection($this->config);
        }
        if (isset($this->config['inputValidation']['is_active']) && $this->config['inputValidation']['is_active']) {
            $list['inputValidation'] = new RequestSecurityInputValidation($this->config);
        }
        if (isset($this->config['csrf']['is_active']) && $this->config['csrf']['is_active']) {
            $list['csrf'] = new RequestSecurityCsrf($this->csrfService, $this->config);
        }

        return $list;
    }

    protected function securityResponseList(): array
    {
        $list = [];
        if (isset($this->config['header']['is_active']) && $this->config['header']['is_active']) {
            $list['header'] = new ResponseHeaders($this->config);
        }
        //if (isset($this->config['escape']['is_active']) && $this->config['escape']['is_active']) {
        //    $list['escape'] = new ResponseEscape($this->config);
        //}
        if (isset($this->config['compress']['is_active']) && $this->config['compress']['is_active']) {
            $list['compress'] = new ResponseCompress($this->config);
        }

        return $list;
    }
}