<?php

declare(strict_types=1);

namespace Pi\Core\Service;

use DateTime;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\Cache\Storage\Plugin\Serializer;
use Redis;

class CacheService implements ServiceInterface
{
    /* @var array */
    public array $userValuePattern
        = [
            'account'       => [],
            'roles'         => [],
            'access_keys'   => [],
            'refresh_keys'  => [],
            'otp'           => [],
            'device_tokens' => [],
            'multi_factor'  => [],
            'authorization' => [],
        ];

    /* @var array */
    public array $userAccountValuePattern
        = [
            'id'                  => 0,
            'name'                => null,
            'email'               => null,
            'identity'            => null,
            'mobile'              => null,
            'first_name'          => null,
            'last_name'           => null,
            'avatar'              => null,
            'time_created'        => 0,
            'last_login'          => 0,
            'status'              => 0,
            'has_password'        => 0,
            'multi_factor_global' => 0,
            'multi_factor_status' => 0,
            'multi_factor_verify' => 0,
            'company_id'          => 0,
            'company_title'       => null,
        ];

    /* @var SimpleCacheDecorator */
    protected SimpleCacheDecorator $cache;

    /* @var string */
    protected string $userKeyPattern = 'user_%s';

    /* @var array */
    protected array $config;

    public function __construct(StorageAdapterFactoryInterface $storageFactory, $config)
    {
        // Set cache
        $cache = $storageFactory->create($config['storage'], $config['options'], $config['plugins']);
        $cache->addPlugin(new Serializer());
        $this->cache  = new SimpleCacheDecorator($cache);
        $this->config = $config;
    }

    public function hasItem($key): bool
    {
        return $this->cache->has($key);
    }

    public function getItem($key): array
    {
        $item = [];
        if ($this->cache->has($key)) {
            $item = $this->cache->get($key);
        }

        return $item;
    }

    public function setItem(string $key, array $value = [], $ttl = null): array
    {
        $this->cache->set($key, $value, $ttl);

        return $this->getItem($key);
    }

    public function deleteItem(string $key): void
    {
        $this->cache->delete($key);
    }

    public function deleteItems(array $array): void
    {
        foreach ($array as $key) {
            $this->cache->delete($key);
        }
    }

    public function setUser(int $userId, array $params): array
    {
        $key = sprintf($this->userKeyPattern, $userId);

        // Get and check user
        $user = $this->getUser($userId);
        if (empty($user)) {
            $user = $this->userValuePattern;
        }

        // Set params
        if (isset($params['account'])) {
            // Set user
            $user['account']       = $params['account'];
            $user['account']['id'] = (int)$user['account']['id'];

            // Set user account template
            foreach ($this->userAccountValuePattern as $accountKey => $accountValue) {
                if (!isset($user['account'][$accountKey])) {
                    $user['account'][$accountKey] = $accountValue;
                }
            }
        }

        foreach ($this->userValuePattern as $accountKey => $accountValue) {
            if (isset($params[$accountKey])) {
                $user[$accountKey] = $params[$accountKey];
            }
        }

        /* if (isset($params['access_keys'])) {
            $user['access_keys'] = $params['access_keys'];
        }
        if (isset($params['refresh_keys'])) {
            $user['refresh_keys'] = $params['refresh_keys'];
        }
        if (isset($params['roles'])) {
            $user['roles'] = $params['roles'];
        }
        if (isset($params['otp'])) {
            $user['otp'] = $params['otp'];
        }
        if (isset($params['device_tokens'])) {
            $user['device_tokens'] = $params['device_tokens'];
        }
        if (isset($params['multi_factor'])) {
            $user['multi_factor'] = $params['multi_factor'];
        }
        if (isset($params['permission'])) {
            $user['permission'] = $params['permission'];
        }
        if (isset($params['authorization'])) {
            $user['authorization'] = $params['authorization'];
        } */

        // Set/Reset cache
        return $this->setItem($key, $user);
    }

    public function getUser(int $userId): array
    {
        $key  = sprintf($this->userKeyPattern, $userId);
        $user = $this->getItem($key);
        if (!empty($user)) {
            $user['account']['id'] = (int)$user['account']['id'];
        }

        return $user;
    }

    public function deleteUser($userId): void
    {
        $key = sprintf($this->userKeyPattern, $userId);
        $this->deleteItem($key);
    }

    public function setUserItem(int $userId, string $key, string|array $value): void
    {
        $user = $this->getUser($userId);
        if (!empty($user) && !empty($value)) {
            switch ($key) {
                case 'access_keys':
                    $this->setUser($userId, ['access_keys' => $value]);
                    break;

                case 'refresh_keys':
                    $this->setUser($userId, ['refresh_keys' => $value]);
                    break;

                case 'roles':
                    $this->setUser($userId, ['roles' => $value]);
                    break;

                case 'multi_factor':
                    $this->setUser($userId, ['multi_factor' => $value]);
                    break;

                case 'device_tokens':
                    $this->setUser($userId, ['device_tokens' => $value]);
                    break;
            }
        }
    }

    public function deleteUserItem(int $userId, string $key, string $value): void
    {
        $user = $this->getUser($userId);
        if (!empty($user)) {
            switch ($key) {
                case 'all_keys':
                    $this->setUser($userId, ['access_keys' => [], 'refresh_keys' => [], 'multi_factor' => []]);
                    break;

                case 'access_keys':
                    $user['access_keys'] = array_combine($user['access_keys'], $user['access_keys']);
                    if (isset($user['access_keys'][$value])) {
                        unset($user['access_keys'][$value]);
                    }
                    $this->setUser($userId, ['access_keys' => array_values($user['access_keys'])]);
                    break;

                case 'refresh_keys':
                    $user['refresh_keys'] = array_combine($user['refresh_keys'], $user['refresh_keys']);
                    if (isset($user['refresh_keys'][$value])) {
                        unset($user['refresh_keys'][$value]);
                    }
                    $this->setUser($userId, ['refresh_keys' => array_values($user['refresh_keys'])]);
                    break;

                case 'roles':
                    $user['roles'] = array_combine($user['roles'], $user['roles']);
                    if (isset($user['roles'][$value])) {
                        unset($user['roles'][$value]);
                    }
                    $this->setUser($userId, ['roles' => array_values($user['roles'])]);
                    break;

                case 'multi_factor':
                    $user['multi_factor'] = array_combine($user['multi_factor'], $user['multi_factor']);
                    if (isset($user['multi_factor'][$value])) {
                        unset($user['multi_factor'][$value]);
                    }
                    $this->setUser($userId, ['multi_factor' => array_values($user['multi_factor'])]);
                    break;

                // TODO: review this solution
                case 'device_tokens':
                    $user['device_tokens'] = $value;// array_unique(array_merge($user['device_tokens'], [$value]));
                    $this->setUser($userId, ['device_tokens' => $user['device_tokens']]);
                    break;
            }
        }
    }

    public function updateUserRoles(int $userId, array $roles, string $section = 'api'): array
    {
        // Get and check user
        $key  = sprintf($this->userKeyPattern, $userId);
        $user = $this->getUser($userId);

        if (!empty($user)) {
            // Update roles
            switch ($section) {
                case 'api':
                    $user['roles'] = array_unique(array_merge($user['roles'], $roles));
                    break;

                case 'admin':
                    // Todo
                    break;
            }

            // Set/Reset cache
            $this->setItem($key, $user);
        }

        return $user;
    }

    public function getCacheList(): array
    {
        // Setup redis
        $redis = new Redis();
        $redis->connect($this->config['options']['server']['host'], (int)$this->config['options']['server']['port']);
        if (isset($this->config['options']['password']) && !empty($this->config['options']['password'])) {
            $redis->auth($this->config['options']['password']);
        }
        if (isset($this->config['options']['server']['database']) && is_numeric($this->config['options']['server']['database'])) {
            $redis->select((int)$this->config['options']['server']['database']);
        }

        // Get keys
        $keys = $redis->keys(sprintf('%s:*', $this->config['options']['namespace']));

        // Set list
        $list = [];
        foreach ($keys as $key) {
            // Set new key name
            $simpleKey = str_replace(sprintf('%s:', $this->config['options']['namespace']), '', $key);

            // Get ttl
            $ttl = $redis->ttl($key);

            // Set date
            $currentTime    = new DateTime();
            $expirationTime = new DateTime();
            $expirationTime->setTimestamp(time() + $ttl);

            // Set date
            $expirationDate = $expirationTime->format('Y-m-d H:i:s');
            $interval       = $currentTime->diff($expirationTime);

            // Status
            $status = 'Active';
            if ($ttl === -1) {
                $status = 'Perpetual (No Expiration)';
            } elseif ($ttl === -2) {
                $status = 'Deleted';
            }

            // Add to list
            $list[] = [
                'key'        => $simpleKey,
                'ttl'        => $ttl,
                'status'     => $status,
                'expiration' => [
                    'date'     => $expirationDate,
                    'interval' => [
                        'days'    => $interval->days,
                        'hours'   => $interval->h,
                        'minutes' => $interval->i,
                    ],
                ],
            ];
        }

        return $list;
    }

    public function setPersist(string $key): void
    {
        // Set key
        $key = sprintf('%s:%s', $this->config['options']['namespace'], $key);

        // Setup redis
        $redis = new Redis();
        $redis->connect($this->config['options']['server']['host'], (int)$this->config['options']['server']['port']);
        if (isset($this->config['options']['password']) && !empty($this->config['options']['password'])) {
            $redis->auth($this->config['options']['password']);
        }
        if (isset($this->config['options']['server']['database']) && is_numeric($this->config['options']['server']['database'])) {
            $redis->select((int)$this->config['options']['server']['database']);
        }

        // Set persist
        $redis->persist($key);
    }
}