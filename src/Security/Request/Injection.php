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
            // Core SQL commands, scoped to real SQL structures
            '/\bselect\b\s+.*\bfrom\b/i',             // SELECT ... FROM
            '/\binsert\b\s+into\b/i',                 // INSERT INTO ...
            '/\bupdate\b\s+\w+\s+set\b/i',            // UPDATE table SET ...
            '/\bdelete\b\s+from\b/i',                 // DELETE FROM ...
            '/\bdrop\s+(table|database)\b/i',         // DROP TABLE / DROP DATABASE
            '/\bcreate\s+(table|database)\b/i',       // CREATE TABLE / DATABASE
            '/\balter\s+table\b/i',                   // ALTER TABLE
            '/\btruncate\s+table\b/i',                // TRUNCATE TABLE
            '/\b(exec|execute|grant|revoke|declare)\b/i', // Dangerous execution keywords

            // SQL comments and operators
            '/(--|\#)/',                              // SQL single-line comment
            '/\/\*.*?\*\//s',                         // SQL multi-line comment
            '/\b(or|and)\s+1\s*=\s*1\b/i',            // Boolean conditions
            '/\b(or|and)\s+\'[^\']{1,255}\'\s*=\s*\'[^\']{1,255}\'/i', // String comparisons
            '/\b(or|and)\s+\"[^\"]{1,255}\"\s*=\s*\"[^\"]{1,255}\"/i', // Double-quoted comparisons

            // SQL functions and expressions
            '/\b(select|insert|update|delete|exec|execute|db_name|user|version|ifnull|sleep|benchmark)\b\s*\(/i',
            '/\binformation_schema\b/i',              // Database metadata
            '/\bcase\s+when\b/i',                     // CASE WHEN
            '/\bnull\b/i',                            // NULL values

            // Unions and subqueries
            '/\bunion\b\s+select\b/i',                // UNION SELECT
            '/\bunion\s+all\b\s+select\b/i',          // UNION ALL SELECT
            '/\bexists\s*\(\s*select\b/i',            // EXISTS (SELECT ...)

            // Suspicious characters in SQL context
            '/;/',                                    // Statement terminators
            '/\bcast\b\s*\(/i',                       // CAST(...)
            '/\bconvert\b\s*\(/i',                    // CONVERT(...)
            '/\bshutdown\b/i',                        // SHUTDOWN command
            '/\bwaitfor\s+delay\b/i',                 // WAITFOR DELAY (time-based)

            // Hex or binary injection
            '/\b0x[0-9a-fA-F]{2,32}\b/i',             // Hexadecimal injection
            '/\bx\'[0-9a-fA-F]{2,32}\'/i',            // Hex-encoded strings

            // Miscellaneous suspicious patterns
            '/\b(select.*from|union.*select|insert.*into|update.*set|delete\s+from)\b/i',
            '/\b(or|and)\s+\d{1,255}\s*=\s*\d{1,255}/i', // Numeric equality
            '/\b(?:like|regexp)\b/i',                 // LIKE / REGEXP
            '/\b(if|case)\s*\(/i',                    // IF(...) or CASE(...)
            '/\s*;\s*(select|insert|update|delete|drop|create|alter|truncate)\s+/i', // Chained queries

            // URL encoded injection attempts
            '/(?:%27|%22|%3D|%3B|%23|%2D|%2F|%5C|%25|%2C|%5B|%5D|%7B|%7D)/i',

            // Obfuscated payloads
            '/\bselect\b\s*\*\s*\bfrom\b\s*\binformation_schema\b/i', // SELECT * FROM information_schema
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
        if (is_int($input) || is_float($input) || is_bool($input) || empty($input)) {
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