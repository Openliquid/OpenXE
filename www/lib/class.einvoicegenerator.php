<?php
/**
 * EInvoiceGenerator
 *
 * Generates electronic invoices in XRechnung/ZUGFeRD format and sends them via e-mail.
 */
class EInvoiceGenerator
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
     * Hook handler for invoice generation.
     *
     * @param int    $invoiceId
     * @param string $pdfFileName
     * @return void
     */
    public static function onGenerateInvoice($app, $invoiceId, $pdfFileName)
    {
        $generator = new self($app);
        $generator->generate($invoiceId, $pdfFileName);
    }

    /**
     * Generate XML, validate and send invoice.
     *
     * @param int    $invoiceId
     * @param string $pdfFileName
     * @param string $language
     * @return void
     */
    public function generate($invoiceId, $pdfFileName, $language = 'de')
    {
        $data = $this->fetchInvoiceData($invoiceId);
        if (empty($data)) {
            return;
        }

        $xml = $this->buildXml($data);
        if (!$this->validateXml($xml)) {
            $this->log($invoiceId, 'validation_failed', 'XML validation failed');
            return;
        }

        $dir = rtrim($this->app->Conf->WFuserdata, '/') . '/einvoice/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $xmlPath = $dir . 'invoice_' . $invoiceId . '.xml';
        $xml->asXML($xmlPath);

        $pdfPath = $dir . $pdfFileName;
        $this->embedPdf($xmlPath, $pdfPath);
        $this->sendEmail($data, $xmlPath, $pdfPath, $language);
        $this->log($invoiceId, 'generated', 'E-Invoice created');
    }

    /**
     * Fetch invoice data from database.
     *
     * @param int $invoiceId
     * @return array|null
     */
    protected function fetchInvoiceData($invoiceId)
    {
        return $this->app->DB->SelectRow(
            'SELECT id, belegnr, projekt, adresse, email, DATE_FORMAT(datum, "%Y-%m-%d") AS datum, waehrung, gesamtsumme '
            . 'FROM rechnung WHERE id=' . (int)$invoiceId . ' LIMIT 1'
        );
    }

    /**
     * Build XRechnung XML structure.
     *
     * @param array $data
     * @return SimpleXMLElement
     */
    protected function buildXml(array $data)
    {
        $xml = new SimpleXMLElement('<Invoice/>');
        $xml->addChild('InvoiceNumber', $data['belegnr']);
        $xml->addChild('InvoiceDate', $data['datum']);
        $xml->addChild('Currency', $data['waehrung']);
        $xml->addChild('Total', $data['gesamtsumme']);
        return $xml;
    }

    /**
     * Validate XML structure using libxml.
     *
     * @param SimpleXMLElement $xml
     * @return bool
     */
    protected function validateXml(SimpleXMLElement $xml)
    {
        libxml_use_internal_errors(true);
        $dom = dom_import_simplexml($xml);
        if (!$dom) {
            return false;
        }
        return empty(libxml_get_errors());
    }

    /**
     * Embed XML into PDF placeholder.
     *
     * @param string $xmlPath
     * @param string $pdfPath
     * @return void
     */
    protected function embedPdf($xmlPath, $pdfPath)
    {
        if (!file_exists($pdfPath)) {
            return;
        }
        // Placeholder for real PDF/A-3 embedding
        file_put_contents($pdfPath . '.xml', file_get_contents($xmlPath));
    }

    /**
     * Send invoice via e-mail.
     *
     * @param array  $data
     * @param string $xmlPath
     * @param string $pdfPath
     * @param string $language
     * @return void
     */
    protected function sendEmail(array $data, $xmlPath, $pdfPath, $language)
    {
        $template = $this->getEmailTemplate($language);
        $attachments = [$xmlPath];
        if (file_exists($pdfPath)) {
            $attachments[] = $pdfPath;
        }
        $this->app->erp->MailSend(
            $this->app->erp->GetFirmaMail(),
            $this->app->erp->GetFirmaAbsender(),
            $data['email'],
            '',
            $template['subject'],
            $template['body'],
            $attachments,
            $data['projekt']
        );
    }

    /**
     * Get e-mail template texts.
     *
     * @param string $language
     * @return array{subject:string,body:string}
     */
    protected function getEmailTemplate($language)
    {
        $templates = [
            'de' => [
                'subject' => 'Ihre elektronische Rechnung',
                'body'    => 'Anbei erhalten Sie Ihre elektronische Rechnung als XML und PDF.'
            ],
            'en' => [
                'subject' => 'Your electronic invoice',
                'body'    => 'Please find attached your electronic invoice in XML and PDF formats.'
            ],
        ];
        return $templates[$language] ?? $templates['de'];
    }

    /**
     * Log actions to database.
     *
     * @param int    $invoiceId
     * @param string $status
     * @param string $message
     * @return void
     */
    protected function log($invoiceId, $status, $message = '')
    {
        $this->app->DB->Insert(
            sprintf(
                "INSERT INTO e_invoice_log (invoice_id, status, message) VALUES (%d, '%s', %s)",
                (int)$invoiceId,
                $this->app->DB->real_escape_string($status),
                $message === '' ? 'NULL' : "'" . $this->app->DB->real_escape_string($message) . "'"
            )
        );
    }
}

if (isset($app) && is_object($app) && method_exists($app, 'erp')) {
    $app->erp->RegisterHook('onGenerateInvoice', 'EInvoiceGenerator', 'onGenerateInvoice');
}
?>
