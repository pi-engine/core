<?php

declare(strict_types=1);

namespace Pi\Core\Repository;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;

class ModuleRepository implements ModuleRepositoryInterface
{
    /**
     * @var AdapterInterface
     */
    private AdapterInterface $db;

    public function __construct(AdapterInterface $db)
    {
        $this->db = $db;
    }

    public function createTables(array $installStatements): array
    {
        $message = [];
        foreach ($installStatements as $sql) {
            // Try to detect the table name if it's CREATE TABLE
            if (preg_match('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?(\w+)`?/i', $sql, $matches)
                || preg_match('/CREATE\s+TABLE\s+`?(\w+)`?/i', $sql, $matches)
            ) {
                $tableName = $matches[1];

                // Check if table exists
                $result = $this->db->query(
                    "SELECT COUNT(*) AS cnt 
                 FROM INFORMATION_SCHEMA.TABLES 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = ?",
                    [$tableName]
                )->current();

                if ((int)$result['cnt'] === 0) {
                    // Table does not exist → run the CREATE TABLE
                    $this->db->query($sql, Adapter::QUERY_MODE_EXECUTE);

                    $message[] = [
                        'action'  => 'created',
                        'table'   => $tableName,
                        'message' => "Created table: {$tableName}",
                    ];
                } else {
                    $message[] = [
                        'action'  => 'skipped',
                        'table'   => $tableName,
                        'message' => "Table {$tableName} already exists, skipped.",
                    ];
                }
            } /* else {
                // For non-table statements (INSERT, ALTER, etc.) → just run them
                try {
                    $this->db->query($sql, Adapter::QUERY_MODE_EXECUTE);
                } catch (\Exception $e) {
                    echo "Error running SQL: " . $sql . "\n" . $e->getMessage() . "\n";
                }
            } */
        }

        return $message;
    }

    public function updateTables(array $updateStatements): array
    {
        return [];
    }
}