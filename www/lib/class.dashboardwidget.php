<?php

/**
 * DashboardWidget
 *
 * Fetches KPI data for the dashboard.
 * Provides configuration to store widget order per user.
 */
class DashboardWidget
{
    /** @var ApplicationCore */
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get total sales for today.
     *
     * @return float
     */
    public function getSalesToday()
    {
        $sum = $this->app->DB->Select("SELECT SUM(gesamtsumme) FROM auftrag WHERE DATE(datum)=CURDATE()");
        if($sum === null) {
            return 0.0;
        }
        return (float)$sum;
    }

    /**
     * Get count of open orders.
     *
     * @return int
     */
    public function getOpenOrders()
    {
        $count = $this->app->DB->Select("SELECT COUNT(*) FROM auftrag WHERE status NOT IN ('abgeschlossen','storniert')");
        return (int)$count;
    }

    /**
     * Load widget order configuration for a user.
     *
     * @param int $userId
     *
     * @return array
     */
    public function getWidgetOrder($userId)
    {
        $json = $this->app->DB->Select("SELECT widget_order FROM dashboard_widget_settings WHERE user_id='".(int)$userId."' LIMIT 1");
        if(!$json){
            return [];
        }
        $data = json_decode($json, true);
        if(!is_array($data)){
            return [];
        }
        return $data;
    }

    /**
     * Persist widget order for a user.
     *
     * @param int   $userId
     * @param array $order
     */
    public function saveWidgetOrder($userId, array $order)
    {
        $json = $this->app->DB->real_escape_string(json_encode($order));
        $this->app->DB->Insert("INSERT INTO dashboard_widget_settings (user_id, widget_order) VALUES ('".(int)$userId."', '$json') ON DUPLICATE KEY UPDATE widget_order='$json'");
    }

    /**
     * Collect KPI data.
     *
     * @return array
     */
    public function getKpis()
    {
        return [
            'sales_today'  => $this->getSalesToday(),
            'open_orders'  => $this->getOpenOrders(),
        ];
    }
}

