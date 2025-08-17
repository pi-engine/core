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
use Pi\Core\Service\UtilityService;
use RuntimeException;

class SlugRepository implements SlugRepositoryInterface
{
    /**
     * @var AdapterInterface
     */
    private AdapterInterface $db;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /* @var array */
    protected array $config;

    public function __construct(
        AdapterInterface $db,
        UtilityService   $utilityService,
                         $config
    ) {
        $this->db             = $db;
        $this->utilityService = $utilityService;
        $this->config         = $config;
    }

    public function updateAllSlugs(string $table, array $params = []): void
    {
        // Set where
        $where[] = new PredicateSet([
            new IsNull('slug'),
            new Expression("slug = ''"),
        ], PredicateSet::COMBINED_BY_OR);

        // Check table is active for a slug process
        $sql    = new Sql($this->db);
        $select = $sql->select($table)->where($where);

        // Set limit if set
        if (isset($params['limit']) && !empty($params['limit'])) {
            $select->limit($params['limit']);
        }

        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        foreach ($result as $row) {
            $update = new Update($table);
            $update->set(['slug' => $this->utilityService->slug()])->where(['id' => (int)$row['id']]);

            $updateStatement = $sql->prepareStatementForSqlObject($update);
            $updateResult    = $updateStatement->execute();
            if (!$updateResult instanceof ResultInterface) {
                throw new RuntimeException("Database error while updating slug for ID: {$row['id']}");
            }
        }
    }
}