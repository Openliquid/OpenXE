<?php

namespace Xentral\Modules\Migration;

use ApplicationCore;
use DateTimeInterface;

/**
 * Service to perform incremental migrations from Xentral data sources.
 * Demonstrates use of prepared statements and index optimisations.
 */
class XentralIncrementalMigration
{
    /** @var ApplicationCore */
    private $app;

    public function __construct(ApplicationCore $app)
    {
        $this->app = $app;
    }

    /**
     * Ensure necessary indexes exist for fast incremental queries.
     * Example adds index on column `letztebearbeitung` of table `auftrag`.
     */
    public function ensureIndexes(): void
    {
        $sql = 'CREATE INDEX IF NOT EXISTS idx_auftrag_letztebearbeitung ON auftrag (letztebearbeitung)';
        @$this->app->DB->Query($sql);
    }

    /**
     * Fetch orders changed since last sync using prepared statements.
     *
     * @param DateTimeInterface $since Timestamp of last migration
     * @param int               $limit Batch size
     *
     * @return array<int,array<string,mixed>>
     */
    public function fetchUpdatedOrders(DateTimeInterface $since, int $limit = 100): array
    {
        $this->ensureIndexes();
        $mysqli = $this->app->DB->connection;
        $sql = 'SELECT id, letztebearbeitung FROM auftrag
                WHERE letztebearbeitung > ?
                ORDER BY letztebearbeitung ASC
                LIMIT ?';
        $stmt = $mysqli->prepare($sql);
        $time = $since->format('Y-m-d H:i:s');
        $stmt->bind_param('si', $time, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
