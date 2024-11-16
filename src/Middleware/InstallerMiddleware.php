<?php

namespace Pi\Core\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Handler\ErrorHandler;
use Pi\User\Service\PermissionService;
use Pi\User\Service\RoleService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class InstallerMiddleware implements MiddlewareInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var RoleService */
    protected RoleService $roleService;

    /** @var PermissionService */
    protected PermissionService $permissionService;

    /** @var ErrorHandler */
    protected ErrorHandler $errorHandler;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        RoleService              $roleService,
        PermissionService        $permissionService,
        ErrorHandler             $errorHandler
    ) {
        $this->responseFactory   = $responseFactory;
        $this->streamFactory     = $streamFactory;
        $this->roleService       = $roleService;
        $this->permissionService = $permissionService;
        $this->errorHandler      = $errorHandler;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $userRoles = $request->getAttribute('roles');
        $userRoles = array_combine($userRoles, $userRoles);

        if (!in_array('admin', $userRoles)) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_FORBIDDEN);
            $request = $request->withAttribute(
                'error',
                [
                    'message' => 'You dont have access to this area !',
                    'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        return $handler->handle($request);
    }
}