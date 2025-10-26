<?php

declare(strict_types=1);

namespace Pi\Core\Service;

class SystemService implements ServiceInterface
{
    /**
     * Get system and PHP info as an associative array
     *
     * @return array
     */
    public static function getSystemInfo(): array
    {
        $info = [];

        // -----------------------------
        // PHP Info
        // -----------------------------
        $info['php'] = [
            'version' => PHP_VERSION,
            'sapi' => php_sapi_name(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'loaded_extensions' => get_loaded_extensions(),
            'disabled_functions' => ini_get('disable_functions'),
            'ini_file' => php_ini_loaded_file(),
        ];

        // -----------------------------
        // OS Info
        // -----------------------------
        $info['os'] = [
            'name' => php_uname(),
            'kernel' => php_uname('r'),
            'architecture' => php_uname('m'),
            'hostname' => gethostname(),
        ];

        // -----------------------------
        // CPU Info (Linux)
        // -----------------------------
        $info['cpu'] = [
            'cores' => 0,
            'model' => null,
            'mhz' => null,
        ];
        if (file_exists('/proc/cpuinfo')) {
            $cpuInfo = file('/proc/cpuinfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $info['cpu']['cores'] = preg_match_all('/^processor/m', implode("\n", $cpuInfo));
            foreach ($cpuInfo as $line) {
                if (strpos($line, 'model name') !== false) {
                    $info['cpu']['model'] = trim(explode(':', $line)[1]);
                }
                if (strpos($line, 'cpu MHz') !== false) {
                    $info['cpu']['mhz'] = floatval(trim(explode(':', $line)[1]));
                }
            }
        }

        // -----------------------------
        // RAM Info (Linux)
        // -----------------------------
        $info['memory'] = [
            'total_mb' => 0,
            'swap_total_mb' => 0,
        ];
        if (file_exists('/proc/meminfo')) {
            $memInfo = file_get_contents('/proc/meminfo');
            if (preg_match('/MemTotal:\s+(\d+) kB/', $memInfo, $matches)) {
                $info['memory']['total_mb'] = round($matches[1] / 1024, 2);
            }
            if (preg_match('/SwapTotal:\s+(\d+) kB/', $memInfo, $matches)) {
                $info['memory']['swap_total_mb'] = round($matches[1] / 1024, 2);
            }
        }

        // -----------------------------
        // Disk Info
        // -----------------------------
        $info['disk'] = [
            'total_bytes' => disk_total_space('/') ?: 0,
            'free_bytes' => disk_free_space('/') ?: 0,
        ];

        // -----------------------------
        // OPcache & JIT
        // -----------------------------
        $info['opcache'] = [
            'enabled' => false,
        ];
        if (extension_loaded('Zend OPcache')) {
            $status = @opcache_get_status(false);
            $info['opcache'] = [
                'enabled' => ini_get('opcache.enable') ? true : false,
                'memory_consumption' => $status['memory_usage']['used_memory'] ?? 0,
                'free_memory' => $status['memory_usage']['free_memory'] ?? 0,
                'wasted_memory' => $status['memory_usage']['wasted_memory'] ?? 0,
                'num_cached_scripts' => $status['opcache_statistics']['num_cached_scripts'] ?? 0,
                'jit' => ini_get('opcache.jit') ?: 'disabled',
                'jit_buffer_size' => ini_get('opcache.jit_buffer_size') ?: 0,
            ];
        }

        // -----------------------------
        // Database Extensions
        // -----------------------------
        $databases = [
            'mysqli','pdo_mysql','pdo_pgsql','mongodb','redis','pdo_sqlite','sqlsrv','oci8'
        ];
        foreach ($databases as $db) {
            $info['databases'][$db] = [
                'loaded' => extension_loaded($db),
                'version' => extension_loaded($db) ? phpversion($db) : null
            ];
        }

        // -----------------------------
        // Docker Info
        // -----------------------------
        $info['docker'] = [
            'inside_docker' => file_exists('/.dockerenv'),
            'container_id' => null,
            'cpu_limit' => null,
            'memory_limit_bytes' => null,
        ];

        if (file_exists('/proc/self/cgroup')) {
            $lines = file('/proc/self/cgroup', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (preg_match('/[0-9]+:[^:]+:\/docker\/([0-9a-f]+)/', $line, $matches)) {
                    $info['docker']['container_id'] = $matches[1];
                    break;
                }
            }
        }

        // Docker CPU/memory limits via cgroups
        $cpuQuota = @file_get_contents('/sys/fs/cgroup/cpu/cpu.cfs_quota_us');
        $cpuPeriod = @file_get_contents('/sys/fs/cgroup/cpu/cpu.cfs_period_us');
        if ($cpuQuota && $cpuPeriod) {
            $info['docker']['cpu_limit'] = $cpuQuota / $cpuPeriod;
        }

        $memLimit = @file_get_contents('/sys/fs/cgroup/memory/memory.limit_in_bytes');
        if ($memLimit) {
            $info['docker']['memory_limit_bytes'] = (int)$memLimit;
        }

        return $info;
    }
}