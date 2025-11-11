<?php

declare(strict_types=1);

namespace Pi\Core\Service;

use Laminas\Diagnostics\Check;
use Laminas\Diagnostics\Result\Failure;
use Laminas\Diagnostics\Result\Success;
use Laminas\Diagnostics\Result\Warning;
use Laminas\Diagnostics\Runner\Runner;

class SystemService implements ServiceInterface
{
    /**
     * Get system and PHP info as an associative array
     *
     * @return array
     */
    public function systemInfo(): array
    {
        $info = [];

        // PHP Info: version, SAPI, memory limit, max execution, loaded extensions, disabled functions, ini file, timezone
        $info['php'] = [
            'version'            => PHP_VERSION,
            'sapi'               => php_sapi_name(),
            'memory_limit'       => ini_get('memory_limit'),
            'max_execution_time' => (int)ini_get('max_execution_time'),
            'loaded_extensions'  => get_loaded_extensions(),
            'disabled_functions' => array_filter(array_map('trim', explode(',', ini_get('disable_functions')))),
            'ini_file'           => php_ini_loaded_file(),
            'timezone'           => date_default_timezone_get(),
            'error_reporting'    => error_reporting(),
        ];

        // OS Info: name, kernel version, architecture, hostname
        $info['os'] = [
            'name'         => function_exists('php_uname') ? php_uname() : '-',
            'kernel'       => function_exists('php_uname') ? php_uname('r') : '-',
            'architecture' => function_exists('php_uname') ? php_uname('m') : '-',
            'hostname'     => gethostname(),
        ];

        // CPU Info: cores, model, MHz (Linux)
        $info['cpu'] = [
            'cores' => 0,
            'model' => null,
            'mhz'   => null,
        ];
        if (is_readable('/proc/cpuinfo')) {
            $cpuLines             = file('/proc/cpuinfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $info['cpu']['cores'] = preg_match_all('/^processor/m', implode("\n", $cpuLines));
            $cpuData              = array_reduce($cpuLines, fn($carry, $line) => [
                'model' => $carry['model'] ?? (str_starts_with($line, 'model name') ? trim(explode(':', $line, 2)[1]) : null),
                'mhz'   => $carry['mhz'] ?? (str_starts_with($line, 'cpu MHz') ? (float)trim(explode(':', $line, 2)[1]) : null),
            ], ['model' => null, 'mhz' => null]);
            $info['cpu']['model'] = $cpuData['model'];
            $info['cpu']['mhz']   = $cpuData['mhz'];
        }

        // Memory Info: total RAM and swap in MB (Linux)
        $readMemInfo = fn($key) => is_readable('/proc/meminfo')
                                   && preg_match("/$key:\s+(\d+) kB/", file_get_contents('/proc/meminfo'), $m) ? round($m[1] / 1024, 2) : 0;

        $info['memory'] = [
            'total_mb'      => $readMemInfo('MemTotal'),
            'swap_total_mb' => $readMemInfo('SwapTotal'),
        ];

        // Disk Info: total and free bytes
        $info['disk'] = [
            'total_bytes' => @disk_total_space('/') ?: 0,
            'free_bytes'  => @disk_free_space('/') ?: 0,
        ];

        // OPCache Info: enabled, memory usage, JIT status
        $info['opcache'] = ['enabled' => false];
        if (extension_loaded('Zend OPcache')) {
            $status          = @opcache_get_status(false) ?? [];
            $info['opcache'] = [
                'enabled'            => (bool)ini_get('opcache.enable'),
                'memory_consumption' => $status['memory_usage']['used_memory'] ?? 0,
                'free_memory'        => $status['memory_usage']['free_memory'] ?? 0,
                'wasted_memory'      => $status['memory_usage']['wasted_memory'] ?? 0,
                'num_cached_scripts' => $status['opcache_statistics']['num_cached_scripts'] ?? 0,
                'jit'                => ini_get('opcache.jit') ?: 'disabled',
                'jit_buffer_size'    => ini_get('opcache.jit_buffer_size') ?: 0,
            ];
        }

        // Database Extensions: check if loaded and version
        $dbExtensions      = ['mysqli', 'pdo_mysql', 'pdo_pgsql', 'mongodb', 'redis', 'pdo_sqlite', 'sqlsrv', 'oci8'];
        $info['databases'] = array_map(fn($ext) => [
            'loaded'  => extension_loaded($ext),
            'version' => extension_loaded($ext) ? phpversion($ext) : null,
        ], array_combine($dbExtensions, $dbExtensions));

        // Docker Info: container status, ID, CPU and memory limits
        $docker = [
            'inside_docker'      => file_exists('/.dockerenv'),
            'container_id'       => null,
            'cpu_limit'          => null,
            'memory_limit_bytes' => null,
        ];

        if (is_readable('/proc/self/cgroup')) {
            foreach (file('/proc/self/cgroup', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (preg_match('/[0-9]+:[^:]+:\/docker\/([0-9a-f]+)/', $line, $m)) {
                    $docker['container_id'] = $m[1];
                    break;
                }
            }
        }

        $cpuQuota            = @file_get_contents('/sys/fs/cgroup/cpu/cpu.cfs_quota_us');
        $cpuPeriod           = @file_get_contents('/sys/fs/cgroup/cpu/cpu.cfs_period_us');
        $docker['cpu_limit'] = ($cpuQuota && $cpuPeriod) ? ($cpuQuota / $cpuPeriod) : null;

        $memLimit                     = @file_get_contents('/sys/fs/cgroup/memory/memory.limit_in_bytes');
        $docker['memory_limit_bytes'] = $memLimit ? (int)$memLimit : null;

        $info['docker'] = $docker;

        return $info;
    }

    /**
     * Run Laminas Diagnostics checks and return Docker-compatible exit code.
     * 0 = healthy, 1 = unhealthy
     */
    public function healthCheck(): array
    {
        $redisHost = getenv('REDIS_HOST') ?: 'localhost';
        $redisPort = getenv('REDIS_PORT') ?: 6379;
        $redisPass = getenv('REDIS_PASSWORD') ?: null;

        // Define checks with labels in one array
        $checks = [
            ['label' => 'Writable /tmp Directory', 'check' => new Check\DirWritable('/tmp')],
            ['label' => 'Writable /var/log Directory', 'check' => new Check\DirWritable('/var/log')],
            ['label' => 'Disk Space >= 100MB', 'check' => new Check\DiskFree(100 * 1024 * 1024, '/tmp')],
            ['label' => 'PHP Version >= 8.3.0', 'check' => new Check\PhpVersion('8.3.0')],
            ['label' => 'PDO MySQL Extension Loaded', 'check' => new Check\ExtensionLoaded('pdo_mysql')],
            ['label' => 'Intl Extension Loaded', 'check' => new Check\ExtensionLoaded('intl')],
            ['label' => 'MbString Extension Loaded', 'check' => new Check\ExtensionLoaded('mbstring')],
            ['label' => 'JSON Extension Loaded', 'check' => new Check\ExtensionLoaded('json')],
            ['label' => 'cURL Extension Loaded', 'check' => new Check\ExtensionLoaded('curl')],
            ['label' => 'OpenSSL Extension Loaded', 'check' => new Check\ExtensionLoaded('openssl')],
            ['label' => 'ZIP Extension Loaded', 'check' => new Check\ExtensionLoaded('zip')],
            ['label' => 'Redis Extension Loaded', 'check' => new Check\Redis($redisHost, $redisPort, $redisPass)],
            ['label' => 'PDO Class Exists', 'check' => new Check\ClassExists('PDO')],
            ['label' => 'DateTime Class Exists', 'check' => new Check\ClassExists('DateTime')],
            ['label' => 'CPU least 50% of EC2 micro instance', 'check' => new Check\CpuPerformance(0.5)],
            ['label' => 'OP Cache Memory Loaded', 'check' => new Check\OpCacheMemory(70, 90)],
        ];

        // Initialize runner and add all checks dynamically
        $runner = new Runner();
        array_walk($checks, fn($item) => $runner->addCheck($item['check']));

        // Run all checks
        $results = $runner->run();

        // Map results into a structured array
        return array_map(function ($item) use ($results) {
            $result = $results[$item['check']];
            $status = match (true) {
                $result instanceof Success => 'success',
                $result instanceof Failure => 'failure',
                $result instanceof Warning => 'warning',
                default                    => 'unknown',
            };

            return [
                'label'   => $item['label'],
                'status'  => $status,
                'message' => $result->getMessage() ?: 'No additional details provided.',
            ];
        }, $checks);
    }

}