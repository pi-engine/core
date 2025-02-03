<?php

declare(strict_types=1);

namespace Pi\Core\Service;

class ConfigService implements ServiceInterface
{
    /* @var CacheService */
    protected CacheService $cacheService;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    protected array $config;

    protected array $customConfig
        = [
            'file_upload' => [
                'title'   => 'Upload file rules',
                'configs' => [
                    [
                        'key'            => 'allowed_size',
                        'config_key'     => 'allowed_size',
                        'config_sub_key' => 'max',
                        'title'          => 'Set maximum upload size',
                        'description'    => '',
                        'type'           => 'select',
                        'value'          => null,
                        'value_options'  => [
                            [
                                'title' => '1MB',
                                'key'   => '1MB',
                                'value' => '1MB',
                            ],
                            [
                                'title' => '10MB',
                                'key'   => '10MB',
                                'value' => '10MB',
                            ],
                            [
                                'title' => '25MB',
                                'key'   => '25MB',
                                'value' => '25MB',
                            ],
                            [
                                'title' => '50MB',
                                'key'   => '50MB',
                                'value' => '50MB',
                            ],
                            [
                                'title' => '100MB',
                                'key'   => '100MB',
                                'value' => '100MB',
                            ],
                        ],
                        'is_required'    => true,
                    ],
                    [
                        'key'           => 'allowed_extension',
                        'config_key'    => 'allowed_extension',
                        'title'         => 'Allowed extension for upload file',
                        'description'   => '',
                        'type'          => 'textarea',
                        'value'         => null,
                        'value_options' => [],
                        'is_required'   => true,
                    ],
                ],
            ],
            'password'    => [
                'title'   => 'Manage password rules',
                'configs' => [
                    [
                        'key'           => 'password_has_uppercase',
                        'config_key'    => 'password_has_uppercase',
                        'title'         => 'Contains uppercase letters',
                        'description'   => '',
                        'type'          => 'radio',
                        'value'         => null,
                        'value_options' => [
                            [
                                'title' => 'Yes',
                                'key'   => 'yes',
                                'value' => 1,
                            ],
                            [
                                'title' => 'No',
                                'key'   => 'no',
                                'value' => 0,
                            ],
                        ],
                        'is_required'   => true,
                    ],
                    [
                        'key'           => 'password_has_lowercase',
                        'config_key'    => 'password_has_lowercase',
                        'title'         => 'Contains lowercase letters',
                        'description'   => '',
                        'type'          => 'radio',
                        'value'         => null,
                        'value_options' => [
                            [
                                'title' => 'Yes',
                                'key'   => 'yes',
                                'value' => 1,
                            ],
                            [
                                'title' => 'No',
                                'key'   => 'no',
                                'value' => 0,
                            ],
                        ],
                        'is_required'   => true,
                    ],
                    [
                        'key'           => 'password_has_number',
                        'config_key'    => 'password_has_number',
                        'title'         => 'Contains numbers',
                        'description'   => '',
                        'type'          => 'radio',
                        'value'         => null,
                        'value_options' => [
                            [
                                'title' => 'Yes',
                                'key'   => 'yes',
                                'value' => 1,
                            ],
                            [
                                'title' => 'No',
                                'key'   => 'no',
                                'value' => 0,
                            ],
                        ],
                        'is_required'   => true,
                    ],
                    [
                        'key'           => 'password_has_symbol',
                        'config_key'    => 'password_has_symbol',
                        'title'         => 'Contains symbols',
                        'description'   => '',
                        'type'          => 'radio',
                        'value'         => null,
                        'value_options' => [
                            [
                                'title' => 'Yes',
                                'key'   => 'yes',
                                'value' => 1,
                            ],
                            [
                                'title' => 'No',
                                'key'   => 'no',
                                'value' => 0,
                            ],
                        ],
                        'is_required'   => true,
                    ],
                ],
            ],
        ];

    protected string $cacheKey = 'systemConfig';

    protected int $cacheTtl = 60 * 60 * 24 * 365;

    public function __construct(
        CacheService   $cacheService,
        UtilityService $utilityService,
                       $config
    ) {
        $this->cacheService   = $cacheService;
        $this->utilityService = $utilityService;
        $this->config         = $config;
    }

    public function gtyConfigList(): array
    {
        // Set or get cached config
        $cachedConfig = $this->cachedData();

        // Set value data to the list
        foreach ($this->customConfig as $sectionKey => $section) {
            foreach ($section['configs'] as $configKey => $config) {
                // Get value data
                $value = null;
                if (isset($cachedConfig[$config['key']])) {
                    $value = $cachedConfig[$config['key']];
                } elseif (isset($this->config[$config['config_key']])) {
                    if (is_array($this->config[$config['config_key']]) && isset($this->config[$config['config_key']][$config['config_sub_key']])) {
                        $value = $this->config[$config['config_key']][$config['config_sub_key']];
                    } else {
                        $value = $this->config[$config['config_key']];
                    }
                }

                // Set value to config list
                $this->customConfig[$sectionKey]['configs'][$configKey]['value'] = $value;
            }
        }

        return $this->customConfig;
    }

    public function updateConfig($params): array
    {
        // Set or get cached config
        $data = $this->cachedData();

        // Update data
        foreach ($params as $key => $value) {
            if (in_array($key, array_keys($data))) {
                $data[$key] = $value;
            }
        }

        return $this->cacheService->setItem($this->cacheKey, $data, $this->cacheTtl);
    }

    protected function cachedData(): array
    {
        if ($this->cacheService->hasItem($this->cacheKey)) {
            $cache = $this->syncCachedData();
        } else {
            $cache = $this->createCachedData();
        }

        return $cache;
    }

    protected function createCachedData(): array
    {
        $data = [];
        foreach ($this->customConfig as $sectionKey => $section) {
            foreach ($section['configs'] as $configKey => $config) {
                // set value data
                $data[$config['key']] = null;
                if (isset($this->config[$config['config_key']])) {
                    if (is_array($this->config[$config['config_key']]) && isset($this->config[$config['config_key']][$config['config_sub_key']])) {
                        $data[$config['key']] = $this->config[$config['config_key']][$config['config_sub_key']];
                    } else {
                        $data[$config['key']] = $this->config[$config['config_key']];
                    }
                }
            }
        }

        return $this->cacheService->setItem($this->cacheKey, $data, $this->cacheTtl);
    }

    protected function syncCachedData(): array
    {
        // Get cache
        $data = $this->cacheService->getItem($this->cacheKey);

        // Check and set new changes list
        foreach ($this->customConfig as $sectionKey => $section) {
            foreach ($section['configs'] as $configKey => $config) {
                if (!isset($data[$config['key']])) {
                    $data[$config['key']] = null;
                    if (isset($this->config[$config['config_key']])) {
                        if (is_array($this->config[$config['config_key']]) && isset($this->config[$config['config_key']][$config['config_sub_key']])) {
                            $data[$config['key']] = $this->config[$config['config_key']][$config['config_sub_key']];
                        } else {
                            $data[$config['key']] = $this->config[$config['config_key']];
                        }
                    }
                }
            }
        }

        return $this->cacheService->setItem($this->cacheKey, $data, $this->cacheTtl);
    }
}