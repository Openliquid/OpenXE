<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Verwaltung des Mahnwesens
 */
class MahnungManager
{
    /** @var Application */
    protected $app;

    /**
     * @param Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Vorlage mit Platzhaltern wie {HEUTE} ersetzen
     *
     * @param string $template
     * @param array  $vars
     *
     * @return string
     */
    public function parseTemplate($template, array $vars = [])
    {
        $vars['HEUTE'] = date('d.m.Y');
        foreach ($vars as $key => $value) {
            $template = str_replace('{' . strtoupper($key) . '}', $value, $template);
        }
        return $template;
    }

    /**
     * Automatisches Verarbeiten aller &uuml;berf&auml;lligen Rechnungen
     */
    public function processOverdueInvoices()
    {
        $ids = $this->app->DB->SelectArr("SELECT id FROM rechnung WHERE status='offen' AND faelligkeit < NOW()");
        if (empty($ids)) {
            return;
        }
        foreach ($ids as $row) {
            $this->handleOverdueInvoice($row['id']);
        }
    }

    /**
     * Einzelne &uuml;berf&auml;llige Rechnung bearbeiten
     *
     * @param int $invoiceId
     */
    public function handleOverdueInvoice($invoiceId)
    {
        $invoice = $this->app->DB->SelectRow(
            "SELECT id, kundennummer, betrag, faelligkeit, mahnstufe, email, sms FROM rechnung WHERE id = %d",
            (int)$invoiceId
        );
        if (empty($invoice)) {
            return;
        }

        $vars = [
            'RECHNUNG' => $invoice['id'],
            'BETRAG' => number_format((float)$invoice['betrag'], 2, ',', '.'),
            'KUNDENNUMMER' => $invoice['kundennummer'],
        ];

        $this->sendReminder($invoice, 'email', $vars);
        if (!empty($invoice['sms'])) {
            $this->sendReminder($invoice, 'sms', $vars);
        }
        $this->app->DB->Update("UPDATE rechnung SET mahnstufe = mahnstufe + 1 WHERE id = %d", (int)$invoiceId);
    }

    /**
     * Zahlungsaufforderung per E-Mail oder SMS versenden
     *
     * @param array  $invoice
     * @param string $channel
     * @param array  $vars
     */
    public function sendReminder(array $invoice, $channel = 'email', array $vars = [])
    {
        $template = $channel === 'sms' ? $this->getSmsTemplate() : $this->getEmailTemplate();
        $body = $this->parseTemplate($template, $vars);

        $mail = new PHPMailer(true);
        try {
            if ($channel === 'email') {
                $mail->addAddress($invoice['email']);
                $mail->Subject = $this->parseTemplate($this->getEmailSubject(), $vars);
            } else {
                $mail->addAddress($invoice['sms']);
            }
            $mail->setFrom($this->getSender());
            $mail->Body = $body;
            $mail->send();
        } catch (Exception $e) {
            $this->app->erp->InternesEvent($this->app->User->GetID(), 'Mahnung Versandfehler: '.$e->getMessage(), 'error');
        }
    }

    /**
     * Analyse der Zahlungsverz&ouml;gerungen
     *
     * @return array
     */
    public function getDelayStatistics()
    {
        return $this->app->DB->SelectRow(
            "SELECT AVG(DATEDIFF(zahldatum, faelligkeit)) AS avg_delay,
                    MAX(DATEDIFF(zahldatum, faelligkeit)) AS max_delay
             FROM rechnung
             WHERE zahldatum IS NOT NULL AND zahldatum > faelligkeit"
        );
    }

    /**
     * Absenderadresse f&uuml;r Mahnungen
     *
     * @return string
     */
    protected function getSender()
    {
        return $this->app->DB->Select("SELECT value FROM einstellungen WHERE parameter='mahnung_sender' LIMIT 1");
    }

    /**
     * Vorlage f&uuml;r E-Mails
     *
     * @return string
     */
    protected function getEmailTemplate()
    {
        return $this->app->DB->Select("SELECT value FROM einstellungen WHERE parameter='mahnung_email_template' LIMIT 1");
    }

    /**
     * Betreff f&uuml;r E-Mails
     *
     * @return string
     */
    protected function getEmailSubject()
    {
        return $this->app->DB->Select("SELECT value FROM einstellungen WHERE parameter='mahnung_email_subject' LIMIT 1");
    }

    /**
     * Vorlage f&uuml;r SMS
     *
     * @return string
     */
    protected function getSmsTemplate()
    {
        return $this->app->DB->Select("SELECT value FROM einstellungen WHERE parameter='mahnung_sms_template' LIMIT 1");
    }
}
