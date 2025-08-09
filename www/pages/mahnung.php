<?php
/**
 * Seite zur Konfiguration und Automatisierung des Mahnwesens
 */
class Mahnung
{
    /** @var Application */
    public $app;

    /**
     * @param Application $app
     * @param bool        $intern
     */
    public function __construct($app, $intern = false)
    {
        $this->app = $app;
        if ($intern) {
            return;
        }
        $this->app->ActionHandlerInit($this);
        $this->app->ActionHandler('config', 'MahnungConfig');
        $this->app->DefaultAction('config');
        $this->app->erp->RegisterHook('onOverdueInvoice', 'mahnung', 'HookOverdueInvoice');
    }

    /**
     * Konfigurationsoberfl&auml;che anzeigen und speichern
     */
    public function MahnungConfig()
    {
        if (!empty($_POST['submit'])) {
            $this->app->DB->Update(
                sprintf(
                    "REPLACE INTO einstellungen (parameter,value) VALUES ('mahnung_email_template','%s')",
                    $this->app->DB->real_escape_string($_POST['email_template'])
                )
            );
            $this->app->DB->Update(
                sprintf(
                    "REPLACE INTO einstellungen (parameter,value) VALUES ('mahnung_sms_template','%s')",
                    $this->app->DB->real_escape_string($_POST['sms_template'])
                )
            );
            $this->app->DB->Update(
                sprintf(
                    "REPLACE INTO einstellungen (parameter,value) VALUES ('mahnung_sender','%s')",
                    $this->app->DB->real_escape_string($_POST['sender'])
                )
            );
            $this->app->DB->Update(
                sprintf(
                    "REPLACE INTO einstellungen (parameter,value) VALUES ('mahnung_email_subject','%s')",
                    $this->app->DB->real_escape_string($_POST['email_subject'])
                )
            );
        }
        $emailTpl = $this->app->DB->Select("SELECT value FROM einstellungen WHERE parameter='mahnung_email_template' LIMIT 1");
        $smsTpl = $this->app->DB->Select("SELECT value FROM einstellungen WHERE parameter='mahnung_sms_template' LIMIT 1");
        $sender = $this->app->DB->Select("SELECT value FROM einstellungen WHERE parameter='mahnung_sender' LIMIT 1");
        $subject = $this->app->DB->Select("SELECT value FROM einstellungen WHERE parameter='mahnung_email_subject' LIMIT 1");

        $stats = (new MahnungManager($this->app))->getDelayStatistics();
        $this->app->Tpl->Set('EMAIL_TEMPLATE', $emailTpl);
        $this->app->Tpl->Set('SMS_TEMPLATE', $smsTpl);
        $this->app->Tpl->Set('SENDER', $sender);
        $this->app->Tpl->Set('EMAIL_SUBJECT', $subject);
        $this->app->Tpl->Set('AVG_DELAY', (int)$stats['avg_delay']);
        $this->app->Tpl->Set('MAX_DELAY', (int)$stats['max_delay']);

        $this->app->Tpl->Parse('PAGE', 'mahnung_config.tpl');
    }

    /**
     * Hook f&uuml;r &uuml;berf&auml;llige Rechnungen
     *
     * @param int $invoiceId
     */
    public function HookOverdueInvoice($invoiceId)
    {
        $manager = new MahnungManager($this->app);
        $manager->handleOverdueInvoice($invoiceId);
    }
}
