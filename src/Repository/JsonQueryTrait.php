<?php

declare(strict_types=1);

namespace Pi\Core\Repository;

use Laminas\Db\Sql\Predicate\Expression as PredicateExpression;
use Laminas\Db\Sql\Where;

/**
 * JsonQueryTrait - safely build JSON_EXTRACT expressions.
 *
 * Security considerations:
 * - All user-controlled identifiers (path segments, column, table prefix) are validated
 *   against strict patterns before being used in SQL fragments.
 * - Values are bound as parameters (no direct concatenation).
 * - Empty arrays are skipped (avoid IN ()).
 * - There is a configurable maximum array size to avoid huge SQL.
 */
trait JsonQueryTrait
{
    /**
     * Maximum number of elements allowed in an IN() array (defense-in-depth)
     */
    protected int $maxInArraySize = 100;

    /**
     * Allowed pattern for each JSON path segment (letters, numbers, underscore, dash)
     */
    protected function isValidPathSegment(string $segment): bool
    {
        return (bool)preg_match('/^[a-zA-Z0-9_-]{1,128}$/', $segment);
    }

    /**
     * Validate json column name (only allow alnum + underscore)
     */
    protected function isValidJsonColumn(string $column): bool
    {
        return (bool)preg_match('/^[a-zA-Z0-9_]{1,64}$/', $column);
    }

    /**
     * Validate optional table prefix like 'risk.' or 'log.' (alnum + underscore + trailing dot)
     */
    protected function isValidTablePrefix(?string $prefix): bool
    {
        if ($prefix === null || $prefix === '') {
            return true;
        }
        return (bool)preg_match('/^[a-zA-Z0-9_]{1,64}\.$/', $prefix);
    }

    /**
     * Build JSON_EXTRACT expressions from an array of conditions and return a Where object
     *
     * @param array       $conditions  Key => value (value scalar or array)
     * @param array       $whitelist   Allowed keys (these should be defined in each repository)
     * @param string      $jsonColumn  JSON column name, default 'information'
     * @param string|null $tablePrefix Optional table prefix (e.g., 'risk.', 'log.')
     *
     * @return ?Where  Returns null if no predicates were built
     */
    protected function buildJsonWhere(
        array $conditions,
        array $whitelist,
        string $jsonColumn = 'information',
        ?string $tablePrefix = null
    ): ?Where {
        $where = new Where();

        // Validate column and prefix early (fail-fast)
        if (!$this->isValidJsonColumn($jsonColumn) || !$this->isValidTablePrefix($tablePrefix)) {
            return null;
        }

        $columnQualified = ($tablePrefix ?? '') . $jsonColumn; // safe because validated

        foreach ($conditions as $key => $value) {
            // Key must be whitelisted
            if (!in_array($key, $whitelist, true)) {
                continue;
            }

            // Allow dot notation but validate each segment strictly
            $segments      = explode('.', $key);
            $validSegments = true;
            foreach ($segments as $seg) {
                if (!$this->isValidPathSegment($seg)) {
                    $validSegments = false;
                    break;
                }
            }
            if (!$validSegments) {
                continue;
            }

            // Build JSON path safely as $.segment1.segment2 ...
            $jsonPath = '$.' . implode('.', $segments);

            if (is_array($value)) {
                // Skip empty arrays or arrays too large
                if (count($value) === 0 || count($value) > $this->maxInArraySize) {
                    continue;
                }

                $placeholders = implode(', ', array_fill(0, count($value), '?'));
                $params       = array_merge([$jsonPath], array_values($value));

                $where->addPredicate(
                    new PredicateExpression(
                        sprintf("JSON_UNQUOTE(JSON_EXTRACT(%s, ?)) IN (%s)", $columnQualified, $placeholders),
                        $params
                    )
                );
            } else {
                // Scalar comparison
                $where->addPredicate(
                    new PredicateExpression(
                        sprintf("JSON_UNQUOTE(JSON_EXTRACT(%s, ?)) = ?", $columnQualified),
                        [$jsonPath, $value]
                    )
                );
            }
        }

        // Return null if no predicates added
        return count($where->getPredicates()) > 0 ? $where : null;
    }
}
