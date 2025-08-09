<?php
/**
**** COPYRIGHT & LICENSE NOTICE *** DO NOT REMOVE ****
*
* This file is licensed under the Embedded Projects General Public License *Version 3.1.
*
**** END OF COPYRIGHT & LICENSE NOTICE *** DO NOT REMOVE ****
*/
?>
<?php
require_once __DIR__ . '/../lib/class.salesfunnel.php';
require_once __DIR__ . '/../lib/class.wiedervorlagemanager.php';
require_once __DIR__ . '/../lib/class.linkedin_sync.php';

class CrmExtensions
{
    /** @var Application */
    var $app;
    const MODULE_NAME = 'CrmExtensions';

    public function __construct($app, $intern = false)
    {
        $this->app = $app;
        if ($intern) {
            return;
        }
        $this->app->ActionHandlerInit($this);
        $this->app->ActionHandler('salesfunnel', 'CrmExtensionsSalesFunnel');
        $this->app->ActionHandler('reminders', 'CrmExtensionsReminders');
        $this->app->ActionHandler('linkedin', 'CrmExtensionsLinkedIn');
        $this->app->DefaultActionHandler('salesfunnel');
        $this->app->ActionHandlerListen($app);
    }

    public function Install()
    {
        $erp = $this->app->erp;
        $erp->CheckTable('crm_sales_funnel_stage');
        $erp->CheckTable('crm_sales_funnel_deal');
        $erp->CheckTable('crm_reminder');
    }

    protected function Menu()
    {
        $this->app->erp->MenuEintrag('index.php?module=crm_extensions&action=salesfunnel', 'Sales Funnel');
        $this->app->erp->MenuEintrag('index.php?module=crm_extensions&action=reminders', 'Wiedervorlagen');
        $this->app->erp->MenuEintrag('index.php?module=crm_extensions&action=linkedin', 'LinkedIn Sync');
    }

    public function CrmExtensionsSalesFunnel()
    {
        $funnel = new SalesFunnel($this->app->DB);
        $board = $funnel->renderBoard();
        $this->Menu();
        $this->app->Tpl->Set('PAGE', $board);
    }

    public function CrmExtensionsReminders()
    {
        $mgr = new WiedervorlageManager($this->app->DB);
        $reminders = $mgr->getPendingReminders();
        $content = '<h3>Offene Wiedervorlagen</h3><ul>';
        foreach ($reminders as $row) {
            $content .= '<li>' . (int)$row['contact_id'] . ' - ' . htmlspecialchars($row['remind_at']) . '</li>';
        }
        $content .= '</ul>';
        $this->Menu();
        $this->app->Tpl->Set('PAGE', $content);
    }

    public function CrmExtensionsLinkedIn()
    {
        $sync = new LinkedInSync(
            $this->app->DB,
            'CLIENT_ID',
            'CLIENT_SECRET',
            $this->app->erp->GetBaseUrl() . 'index.php?module=crm_extensions&action=linkedin'
        );

        $authUrl = $sync->getAuthorizationUrl('crm');
        $content = '<a href="' . htmlspecialchars($authUrl) . '">Mit LinkedIn verbinden</a>';
        $this->Menu();
        $this->app->Tpl->Set('PAGE', $content);
    }
}
