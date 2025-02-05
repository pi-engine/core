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

    public function __construct(
        AdapterInterface $db,
        Signature        $signature
    ) {
        $this->db        = $db;
        $this->signature = $signature;
    }

    public function updateSignature(string $table, int $id, array $fields): void
    {
        $sql    = new Sql($this->db);
        $select = $sql->select($table)->where(['id' => $id]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute()->current();

        if (!$result) {
            throw new RuntimeException("Record with ID {$id} not found in table {$table}.");
        }

        // Extract specified fields
        $data = array_intersect_key($result, array_flip($fields));

        // Generate new signature
        $signature = $this->signature->signData($data);

        // Update signature field
        $update = new Update($table);
        $update->set(['signature' => $signature])->where(['id' => $id]);

        $updateStatement = $sql->prepareStatementForSqlObject($update);
        $updateResult    = $updateStatement->execute();

        if (!$updateResult instanceof ResultInterface) {
            throw new RuntimeException("Database error while updating signature for ID: {$id}");
        }
    }

    public function updateAllSignatures(string $table, array $fields): void
    {
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

    public function checkSignature(string $table, int $id, array $fields): bool
    {
        $sql    = new Sql($this->db);
        $select = $sql->select($table)->columns(array_merge($fields, ['signature']))->where(['id' => $id]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute()->current();

        if (!$result) {
            return false;
        }

        $data = array_intersect_key($result, array_flip($fields));
        return $this->signature->verifySignature($data, $result['signature']);
    }

    public function checkAllSignatures(string $table, array $fields): array
    {
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