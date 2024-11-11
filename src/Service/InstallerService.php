<?php

namespace Pi\Core\Service;

use User\Service\PermissionService;
use User\Service\ServiceInterface;

class InstallerService implements ServiceInterface
{
    /** @var PermissionService */
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function installPermission($module, array $permissionConfig): void
    {
        // Canonize
        $permissionConfig = $this->canonizePermission($permissionConfig);

        // Get list of exist pages and permission
        $installerList = $this->permissionService->getInstallerList(['module' => $module]);

        // Set list
        $insertList = [
            'permission' => [],
            'page'       => [],
            'role'       => [],
        ];

        // inset
        foreach ($permissionConfig as $permissionList) {
            foreach ($permissionList as $permissionSingle) {
                if (
                    !isset($installerList['page_list'][$permissionSingle['page']])
                    && !isset($installerList['resource_list'][$permissionSingle['permissions']])
                ) {
                    // Check for duplicate
                    if (!in_array($permissionSingle['permissions'], $insertList['permission'])) {
                        // Set resource params
                        $resourceParams = array_merge(
                            $permissionSingle,
                            [
                                'key' => $permissionSingle['permissions'],
                            ]
                        );

                        // Add resource
                        $resource = $this->permissionService->addPermissionResource($resourceParams);
                    } else {
                        $resource = [
                            'key' => $permissionSingle['permissions'],
                        ];
                    }

                    // Check for duplicate
                    if (!in_array($permissionSingle['page'], $insertList['page'])) {
                        // Set page params
                        $pageParams = array_merge(
                            $permissionSingle,
                            [
                                'key'      => $permissionSingle['page'],
                                'resource' => $resource['key'],
                            ]
                        );

                        // Add page
                        $this->permissionService->addPermissionPage($pageParams);
                    }

                    // Check roles
                    foreach ($permissionSingle['role'] as $role) {
                        // Set key
                        $key = sprintf('%s-%s', $role, $permissionSingle['permissions']);

                        // Check for duplicate
                        if (!in_array($key, $insertList['role'])) {
                            // Set role params
                            $roleParams = array_merge(
                                $permissionSingle,
                                [
                                    'key'      => $key,
                                    'resource' => $resource['key'],
                                    'role'     => $role,
                                ]
                            );

                            // Add role
                            $this->permissionService->addPermissionRole($roleParams);

                            // Add to list
                            $insertList['role'][$key] = $key;
                        }
                    }

                    // Add to list
                    $insertList['permission'][$permissionSingle['permissions']] = $permissionSingle['permissions'];
                    $insertList['page'][$permissionSingle['page']]              = $permissionSingle['page'];
                }
            }
        }
    }

    public function canonizePermission(array $permissionConfig): array
    {
        foreach ($permissionConfig as $permissionSection => $permissionList) {
            foreach ($permissionList as $permissionSingleKey => $permissionSingle) {
                if (!isset($permissionSingle['page']) || empty($permissionSingle['page'])) {
                    $permissionSingle['page'] = sprintf(
                        '%s-%s-%s-%s',
                        $permissionSingle['section'],
                        $permissionSingle['module'],
                        $permissionSingle['package'],
                        $permissionSingle['handler']
                    );
                }

                if (!isset($permissionSingle['permissions']) || empty($permissionSingle['permissions'])) {
                    $permissionSingle['permissions'] = sprintf(
                        '%s-%s-%s-%s',
                        $permissionSingle['section'],
                        $permissionSingle['module'],
                        $permissionSingle['package'],
                        $permissionSingle['handler']
                    );
                }

                $permissionConfig[$permissionSection][$permissionSingleKey] = $permissionSingle;
            }
        }

        return $permissionConfig;
    }
}