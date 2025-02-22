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
        $injectionPatterns = [
            // Basic SQL keywords, scoped to query-like structures
            '/\b(select|insert|update|delete|drop|create|alter|truncate|exec|execute|grant|revoke|declare)\b\s+[\w\(\*]/i',

            // SQL comments and operators, scoped to likely SQL contexts
            '/(--|\#)/', // SQL single-line comment or # as comment
            '/\/\*.*?\*\//s', // SQL multi-line comment
            '/\b(or|and)\s+1\s*=\s*1\b/i', // Boolean conditions
            '/\b(or|and)\s+\'[^\']{1,255}\'\s*=\s*\'[^\']{1,255}\'/i', // String comparisons (limit length to avoid matching long tokens)
            '/\b(or|and)\s+\"[^\"]{1,255}\"\s*=\s*\"[^\"]{1,255}\"/i', // Double-quoted string comparisons (same limit)

            // SQL functions and expressions, scoped to query-like patterns
            '/\b(select|insert|update|delete|exec|execute|db_name|user|version|ifnull|sleep|benchmark)\b\s*\(/i', // Functions with opening parentheses
            '/\binformation_schema\b/i', // Targeting database metadata
            '/\bcase\s+when\b/i', // Case statements
            '/\bnull\b/i', // Handling null values

            // Unions and conditional operations, scoped to query-like structures
            '/\bunion\b\s+select\b/i', // Union select pattern
            '/\bunion\s+all\b\s+select\b/i', // Union all select pattern
            '/\bexists\s*\(\s*select\b/i', // Checking for subquery existence

            // Other suspicious characters, scoped to SQL contexts
            '/;/', // Statement terminators
            '/\bcast\b\s*\(/i', // Cast functions
            '/\bconvert\b\s*\(/i', // Convert functions
            '/\bdrop\s+database\b/i', // Dropping databases
            '/\bshutdown\b/i', // Attempting to shut down the database
            '/\bwaitfor\s+delay\b/i', // Time delay operations

            // Hex or binary injection with limits to avoid false positives
            '/\b0x[0-9a-fA-F]{2,32}\b/i', // Hexadecimal injection (limit length to reasonable size)
            '/\bx\'[0-9a-fA-F]{2,32}\'/i', // Hex-encoded strings (limit length)

            // Miscellaneous suspicious patterns, scoped more contextually
            '/\b(select.*from|union.*select|insert.*into|update.*set|delete\s+from|drop\s+table|create\s+table|alter\s+table|truncate\s+table)\b/i',

            // Catching numeric or chained conditions in SQL injection
            '/\b(or|and)\s+\d{1,255}\s*=\s*\d{1,255}/i', // Numeric equality checks (limit length)
            '/\b(?:like|regexp)\b/i', // Check for pattern matching (like or regexp)
            '/\b(if|case)\s*\(/i', // If/Case conditions
            '/\s*;\s*(select|insert|update|delete|drop|create|alter|truncate)\s+/i', // Chained queries

            // Handling encoded input, expanded to cover more encoding variants
            '/(?:%27|%22|%3D|%3B|%23|%2D|%2F|%5C|%25|%2C|%5B|%5D|%7B|%7D)/i', // URL encoded equivalents of ', ", =, ;, #, -, /, \, %, [, ], {, }

            // Additional defensive patterns to detect obfuscated payloads
            '/\b(select|insert|update|delete)\b.*\bfrom\b/i', // Checking if SELECT/INSERT/UPDATE/DELETE are combined with FROM clause
            '/\bselect\b\s*\*\s*\bfrom\b\s*\binformation_schema\b/i', // Specific check for SQL injection targeting information_schema
        ];

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
        if (is_null($input) || is_int($input) || is_float($input) || is_bool($input)) {
            return false;
        }

        // Ensure only strings are checked, error on other types
        if (!is_string($input)) {
            return true;
        }

        // Check for SQL injection patterns in strings
        $input = urldecode($input);
        foreach ($injectionPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false; // No SQL injection detected
    }
}