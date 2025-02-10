<?php

declare(strict_types=1);

namespace Pi\Core\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Update;
use Pi\Core\Security\Signature;
use RuntimeException;

class SignatureRepository implements SignatureRepositoryInterface
{
    /**
     * @var AdapterInterface
     */
    private AdapterInterface $db;

    /**
     * @var Signature
     */
    private Signature $signature;

    /* @var array */
    protected array $config;

    public function __construct(
        AdapterInterface $db,
        Signature        $signature,
                         $config
    ) {
        $this->db        = $db;
        $this->signature = $signature;
        $this->config    = $config;
    }

    public function updateSignature(string $table, array $params): void
    {
        // Set fields
        $fields = $this->config['signature_fields'][$table] ?? [];

        // Check table is active for signature process
        if (in_array($table, $this->config['allowed_tables']) && !empty($fields)) {
            $sql    = new Sql($this->db);
            $select = $sql->select($table)->where($params);

            $statement = $sql->prepareStatementForSqlObject($select);
            $result    = $statement->execute()->current();

            if (!$result) {
                throw new RuntimeException("Record with selected ID not found in table {$table}.");
            }

            // Extract specified fields
            $data = array_intersect_key($result, array_flip($fields));

            // Generate new signature
            $signature = $this->signature->signData($data);

            // Update signature field
            $update = new Update($table);
            $update->set(['signature' => $signature])->where($params);

            $updateStatement = $sql->prepareStatementForSqlObject($update);
            $updateResult    = $updateStatement->execute();

            if (!$updateResult instanceof ResultInterface) {
                throw new RuntimeException("Database error while updating signature for selected ID");
            }
        }
    }

    public function updateAllSignatures(string $table): void
    {
        // Set fields
        $fields = $this->config['signature_fields'][$table] ?? [];

        // Check table is active for signature process
        if (in_array($table, $this->config['allowed_tables']) && !empty($fields)) {
            $sql    = new Sql($this->db);
            $select = $sql->select($table);

            $statement = $sql->prepareStatementForSqlObject($select);
            $result    = $statement->execute();

            foreach ($result as $row) {
                $data      = array_intersect_key($row, array_flip($fields));
                $signature = $this->signature->signData($data);

                $update = new Update($table);
                $update->set(['signature' => $signature])->where(['id' => (int)$row['id']]);

                $updateStatement = $sql->prepareStatementForSqlObject($update);
                $updateResult    = $updateStatement->execute();

                if (!$updateResult instanceof ResultInterface) {
                    throw new RuntimeException("Database error while updating signature for ID: {$row['id']}");
                }
            }
        }
    }

    public function checkSignature(string $table, array $params): bool
    {
        // Set fields
        $fields = $this->config['signature_fields'][$table] ?? [];

        // Check table is active for signature process
        if (!in_array($table, $this->config['allowed_tables']) || empty($fields)) {
            return false;
        }

        $sql    = new Sql($this->db);
        $select = $sql->select($table)->columns(array_merge($fields, ['signature']))->where($params);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute()->current();

        if (!$result) {
            return false;
        }

        $data = array_intersect_key($result, array_flip($fields));
        return $this->signature->verifySignature($data, $result['signature']);
    }

    public function checkAllSignatures(string $table): array
    {
        // Set fields
        $fields = $this->config['signature_fields'][$table] ?? [];

        // Check table is active for signature process
        if (!in_array($table, $this->config['allowed_tables']) || empty($fields)) {
            return [];
        }

        $sql    = new Sql($this->db);
        $select = $sql->select($table)->columns(array_merge(['id'], $fields, ['signature']));

        $statement = $sql->prepareStatementForSqlObject($select);
        $results   = $statement->execute();

        $result = [];
        foreach ($results as $row) {
            $data               = array_intersect_key($row, array_flip($fields));
            $result[$row['id']] = $this->signature->verifySignature($data, $row['signature']);
        }

        return $result;
    }
}