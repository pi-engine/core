<?php

declare(strict_types=1);

namespace Pi\Core\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Sql\Predicate\Expression;
use Laminas\Db\Sql\Predicate\IsNull;
use Laminas\Db\Sql\Predicate\PredicateSet;
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
        $fields = array_unique(array_merge(['id'], $fields));

        // Check table is active for signature process
        if (in_array($table, $this->config['allowed_tables']) && !empty($fields)) {
            $sql    = new Sql($this->db);
            $select = $sql->select($table)->where($params);

            $statement = $sql->prepareStatementForSqlObject($select);
            $result    = $statement->execute()->current();

            if (!$result) {
                throw new RuntimeException("Record with selected ID not found in table {$table}.");
            }

            $data      = array_intersect_key($result, array_flip($fields));
            $data      = $this->signature->sortByFields($data, $fields);
            $signature = $this->signature->signData($data);

            $update = new Update($table);
            $update->set(['signature' => $signature])->where($params);

            $updateStatement = $sql->prepareStatementForSqlObject($update);
            $updateResult    = $updateStatement->execute();

            if (!$updateResult instanceof ResultInterface) {
                throw new RuntimeException("Database error while updating signature for selected ID");
            }
        }
    }

    public function updateAllSignatures(string $table, array $params = []): void
    {
        // Set fields
        $fields = $this->config['signature_fields'][$table] ?? [];
        $fields = array_unique(array_merge(['id'], $fields));

        // Set where
        if (!empty($params['just_empty'])) {
            $where[] = new PredicateSet([
                new IsNull('signature'),
                new Expression("signature = ''"),
            ], PredicateSet::COMBINED_BY_OR);
        }

        // Check table is active for signature process
        if (in_array($table, $this->config['allowed_tables']) && !empty($fields)) {
            $sql    = new Sql($this->db);
            $select = $sql->select($table);

            // Set limit if set
            if (!empty($params['just_empty']) && !empty($params['limit'])) {
                $select->limit($params['limit']);
            }

            $statement = $sql->prepareStatementForSqlObject($select);
            $result    = $statement->execute();

            foreach ($result as $row) {
                $data      = array_intersect_key($row, array_flip($fields));
                $data      = $this->signature->sortByFields($data, $fields);
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
        $fields = array_unique(array_merge(['id'], $fields, ['signature']));

        // Check table is active for signature process
        if (!in_array($table, $this->config['allowed_tables']) || empty($fields)) {
            return false;
        }

        $sql    = new Sql($this->db);
        $select = $sql->select($table)->columns($fields)->where($params);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute()->current();

        if (!$result) {
            return false;
        }

        $data = array_intersect_key($result, array_flip($fields));
        $data = $this->signature->sortByFields($data, $fields);
        return $this->signature->verifySignature($data, $result['signature']);
    }

    public function checkAllSignatures(string $table): array
    {
        // Set fields
        $fields = $this->config['signature_fields'][$table] ?? [];
        $fields = array_unique(array_merge(['id'], $fields, ['signature']));

        // Check table is active for signature process
        if (!in_array($table, $this->config['allowed_tables']) || empty($fields)) {
            return [];
        }

        $sql    = new Sql($this->db);
        $select = $sql->select($table)->columns($fields);

        $statement = $sql->prepareStatementForSqlObject($select);
        $results   = $statement->execute();

        $result = [
            'total'      => 0,
            'verified'   => 0,
            'unverified' => 0,
            'list'       => [],
        ];
        foreach ($results as $row) {
            $data   = array_intersect_key($row, array_flip($fields));
            $data   = $this->signature->sortByFields($data, $fields);
            $verify = $this->signature->verifySignature($data, $row['signature']);

            // Set result
            $result['total'] = $result['total'] + 1;
            if ($verify) {
                $result['verified'] = $result['verified'] + 1;
            } else {
                $result['unverified'] = $result['unverified'] + 1;
                $result['list'][]     = $row['id'];
            }
        }

        return $result;
    }
}