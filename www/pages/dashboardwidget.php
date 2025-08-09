<?php
require_once __DIR__.'/../lib/class.dashboardwidget.php';

class Dashboardwidget
{
    /** @var ApplicationCore */
    public $app;

    public function __construct($app)
    {
        $this->app = $app;
        $this->app->ActionHandlerInit($this);
        $this->app->ActionHandler('list', 'DashboardWidgetList');
        $this->app->ActionHandler('data', 'DashboardWidgetData');
        $this->app->ActionHandler('saveorder', 'DashboardWidgetSaveOrder');
        $this->app->ActionHandlerListen($app);
        $this->Install();
    }

    public function DashboardWidgetList()
    {
        $widget = new DashboardWidget($this->app);
        $kpis = $widget->getKpis();
        $this->app->Tpl->Set('sales_today', $kpis['sales_today']);
        $this->app->Tpl->Set('open_orders', $kpis['open_orders']);
        $this->app->Tpl->Parse('PAGE', 'dashboard.tpl');
    }

    public function DashboardWidgetData()
    {
        $widget = new DashboardWidget($this->app);
        header('Content-Type: application/json');
        echo json_encode($widget->getKpis());
        exit;
    }

    public function DashboardWidgetSaveOrder()
    {
        $orderString = $this->app->Secure->GetPOST('order');
        $order = $orderString ? explode(',', $orderString) : [];
        $widget = new DashboardWidget($this->app);
        $userId = method_exists($this->app->User, 'GetID') ? $this->app->User->GetID() : 0;
        $widget->saveWidgetOrder($userId, $order);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok']);
        exit;
    }

    public function Install()
    {
        $this->app->erp->CheckTable('dashboard_widget_settings', 'user_id');
        $this->app->erp->CheckColumn('user_id', 'int(11)', 'dashboard_widget_settings', 'NOT NULL');
        $this->app->erp->CheckColumn('widget_order', 'text', 'dashboard_widget_settings', 'NOT NULL');
    }
}
