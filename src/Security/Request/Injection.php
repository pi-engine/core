<?php

declare(strict_types=1);

namespace Pi\Core\Security\Request;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;

class Injection implements RequestSecurityInterface
{
    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'injection';

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @param ServerRequestInterface $request
     * @param array                  $securityStream
     *
     * @return array
     */
    public function check(ServerRequestInterface $request, array $securityStream = []): array
    {
        // Check if the IP is in the whitelist
        if (
            (bool)$this->config['injection']['ignore_whitelist'] === true
            && isset($securityStream['ip']['data']['in_whitelist'])
            && (bool)$securityStream['ip']['data']['in_whitelist'] === true
        ) {
            return [
                'result' => true,
                'name'   => $this->name,
                'status' => 'ignore',
                'data'   => [],
            ];
        }

        // Get request and query body
        $requestParams = $request->getParsedBody();
        $QueryParams   = $request->getQueryParams();
        $params        = array_merge($requestParams, $QueryParams);

        // Do check
        if (!empty($params)) {
            if ($this->detectInjection($params)) {
                return [
                    'result' => false,
                    'name'   => $this->name,
                    'status' => 'unsuccessful',
                    'data'   => [],
                ];
            }
        }

        return [
            'result' => true,
            'name'   => $this->name,
            'status' => 'successful',
            'data'   => [],
        ];
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return 'Access denied: Injection detected';
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return StatusCodeInterface::STATUS_BAD_REQUEST;
    }

    private function detectInjection($input): bool
    {
        // If input is an array, recursively check each item
        if (is_array($input)) {
            foreach ($input as $value) {
                if ($this->detectInjection($value)) {
                    return true;
                }
            }

            return false;
        }

        // Allow int, float, and bool without further checks
        if (is_int($input) || is_float($input) || is_bool($input) || empty($input)) {
            return false;
        }

        // Ensure only strings are checked, error on other types
        if (!is_string($input)) {
            return true;
        }

        // Check for SQL injection patterns in strings
        $input    = urldecode($input);
        $patterns = $this->getPatterns($this->config['injection']['pattern_type'] ?? 'critical');
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false; // No SQL injection detected
    }

    private function getPatterns($patternType): array
    {
        switch ($patternType) {
            case 'basic':
                return [
                    // Basic SELECT statement
                    '/\bselect\b.+\bfrom\b/i',

                    // UNION SELECT
                    '/\bunion\s+(all\s+)?select\b/i',

                    // Boolean bypasses
                    '/\b(or|and)\s+1\s*=\s*1\b/i',
                    '/\b(or|and)\s+\d+\s*=\s*\d+\b/i',

                    // Chained statements
                    '/;\s*(select|insert|update|delete|drop|create|alter|truncate)\b/i',

                    // SQL comments (context-aware)
                    '/(\bselect\b|\binsert\b|\bupdate\b|\bdelete\b).*(--|\/\*.*?\*\/)/is',
                ];
                break;

            default:
            case 'standard':
                return [
                    // Core SQL commands
                    '/\bselect\b.+\bfrom\b/i',
                    '/\binsert\b.+\binto\b/i',
                    '/\bupdate\b.+\bset\b/i',
                    '/\bdelete\b.+\bfrom\b/i',

                    // Access to database metadata
                    '/\binformation_schema\b/i',

                    // UNION-based injection
                    '/\bunion\s+(all\s+)?select\b/i',

                    // Boolean bypasses
                    '/\b(or|and)\s+1\s*=\s*1\b/i',
                    '/\b(or|and)\s+\d+\s*=\s*\d+\b/i',

                    // SQL comments (context-aware)
                    '/(\bselect\b|\binsert\b|\bupdate\b|\bdelete\b).*(--|\/\*.*?\*\/)/is',

                    // Chained statements
                    '/;\s*(select|insert|update|delete|drop|create|alter|truncate)\b/i',
                ];
                break;

            case 'critical':
                return [
                    // Core SQL commands
                    '/\bselect\b\s+.*\bfrom\b/i',
                    '/\binsert\b\s+into\b/i',
                    '/\bupdate\b\s+\w+\s+set\b/i',
                    '/\bdelete\b\s+from\b/i',
                    '/\bdrop\s+(table|database)\b/i',
                    '/\bcreate\s+(table|database)\b/i',
                    '/\balter\s+table\b/i',
                    '/\btruncate\s+table\b/i',
                    '/\b(exec|execute|grant|revoke|declare)\b/i',

                    // SQL comments (context-aware)
                    '/(\bselect\b|\binsert\b|\bupdate\b|\bdelete\b).*(--|\/\*.*?\*\/)/is',

                    // Boolean and string bypasses
                    '/\b(or|and)\s+1\s*=\s*1\b/i',
                    '/\b(or|and)\s+\d+\s*=\s*\d+\b/i',
                    '/\b(or|and)\s+\'[^\']{1,255}\'\s*=\s*\'[^\']{1,255}\'/i',
                    '/\b(or|and)\s+\"[^\"]{1,255}\"\s*=\s*\"[^\"]{1,255}\"/i',

                    // Functions & expressions
                    '/\b(sleep|benchmark|version|user|db_name|ifnull)\s*\(/i',
                    '/\binformation_schema\b/i',
                    '/\bcase\s+when\b/i',
                    '/\bnull\b/i',

                    // UNION / EXISTS
                    '/\bunion\s+(all\s+)?select\b/i',
                    '/\bexists\s*\(\s*select\b/i',

                    // Chained statements
                    '/;\s*(select|insert|update|delete|drop|create|alter|truncate)\b/i',

                    // Hex / binary injection
                    '/\b0x[0-9a-fA-F]{2,32}\b/i',
                    '/\bx\'[0-9a-fA-F]{2,32}\'/i',

                    // URL-encoded injection attempts
                    '/(?:%27|%22|%3D|%3B|%2D|%2F|%5C|%25|%2C|%5B|%5D|%7B|%7D)/i',

                    // Obfuscated payloads
                    '/\bselect\b\s*\*\s*\bfrom\b\s*\binformation_schema\b/i',
                ];
                break;
        }
    }
}