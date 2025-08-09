<?php

/**
 * SalesFunnel library for managing deals and rendering a Kanban board.
 *
 * This class provides methods to load funnel stages and deals from the
 * database and renders a simple Bootstrap based Kanban board. Drag & drop
 * handling is implemented in the accompanying JavaScript file
 * `www/js/salesfunnel.js`.
 */
class SalesFunnel
{
    /** @var \PDO|mixed Database connection */
    protected $db;

    /**
     * @param mixed $db Database connection from Application
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Returns all funnel stages.
     *
     * @return array
     */
    public function getStages()
    {
        return $this->db->SelectArr('SELECT id, name FROM crm_sales_funnel_stage ORDER BY sortorder');
    }

    /**
     * Returns all deals for the given stage.
     *
     * @param int $stageId
     *
     * @return array
     */
    public function getDealsByStage($stageId)
    {
        $stageId = (int)$stageId;
        return $this->db->SelectArr(
            "SELECT id, title, customer, value FROM crm_sales_funnel_deal WHERE stage_id = {$stageId} ORDER BY sortorder"
        );
    }

    /**
     * Render simple Kanban board HTML.
     *
     * @return string
     */
    public function renderBoard()
    {
        $stages = $this->getStages();
        $html = '<div class="row" id="sales-funnel-board">';
        foreach ($stages as $stage) {
            $html .= '<div class="col-md-3 kanban-stage" data-stage="' . (int)$stage['id'] . '">';
            $html .= '<h4>' . htmlspecialchars($stage['name']) . '</h4>';
            $html .= '<ul class="list-group kanban-items">';
            $deals = $this->getDealsByStage($stage['id']);
            foreach ($deals as $deal) {
                $html .= '<li class="list-group-item kanban-item" data-id="' . (int)$deal['id'] . '">';
                $html .= htmlspecialchars($deal['title']) . '<br/>';
                $html .= '<small>' . htmlspecialchars($deal['customer']) . '</small>';
                $html .= '<span class="badge badge-secondary float-right">' .
                    number_format((float)$deal['value'], 2, ',', '.') . '</span>';
                $html .= '</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '<script src="./js/salesfunnel.js"></script>';

        return $html;
    }
}
