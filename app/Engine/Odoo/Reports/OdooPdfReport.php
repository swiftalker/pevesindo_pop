<?php

namespace App\Engine\Odoo\Reports;

use App\Engine\Odoo\Exceptions\OdooApiException;

/**
 * Downloads and streams Odoo QWeb PDF reports via the Odoo web controller.
 *
 * Odoo's /report/pdf/ endpoint does NOT accept the JSON-2 API key.
 * Instead, it requires a valid web session cookie.
 */
class OdooPdfReport
{
    /**
     * Odoo QWeb report name -> short type mapping.
     */
    protected const REPORT_MAP = [
        'quotation' => 'sale.report_saleorder',
        'order' => 'sale.report_saleorder',
        'invoice' => 'account.report_invoice_with_payments',
    ];

    public function __construct(
        protected OdooPdfReportSession $session,
    ) {}

    /**
     * Fetch a PDF report from Odoo.
     *
     * @param  string  $type  Short type: quotation, order, invoice
     * @param  int  $odooId  Odoo record ID
     *
     * @throws OdooApiException
     */
    public function fetchPdf(string $type, int $odooId): string
    {
        $this->validateType($type);

        $reportName = self::REPORT_MAP[$type];

        return $this->session->withSession(function ($client) use ($reportName, $odooId) {
            $response = $client->post($reportName.'/'.$odooId.'/');

            if ($response->status() === 429) {
                throw new OdooApiException('Rate limited by Odoo (report)', 429);
            }

            if ($response->status() === 401 || $response->status() === 403) {
                throw new OdooApiException('Session expired or unauthorized', $response->status());
            }

            if ($response->status() >= 400) {
                throw new OdooApiException(
                    "Odoo report error (HTTP {$response->status()})",
                    $response->status()
                );
            }

            // Odoo may return an HTML error page instead of PDF.
            $body = $response->body();

            if (empty($body)) {
                throw new OdooApiException('Odoo returned an empty report document.');
            }

            // Quick HTML sniff — Odoo returns HTML on auth failure or missing report.
            if (str_starts_with(trim($body), '<') || str_contains($body, '<!DOCTYPE')) {
                throw new OdooApiException('Odoo returned HTML instead of PDF. The report or session may be invalid.');
            }

            return $body;
        });
    }

    /**
     * Get the Odoo report filename for a given type.
     */
    public function getReportName(string $type): string
    {
        $this->validateType($type);

        return self::REPORT_MAP[$type];
    }

    /**
     * @throws OdooApiException
     */
    protected function validateType(string $type): void
    {
        if (! isset(self::REPORT_MAP[$type])) {
            throw new OdooApiException(
                "Invalid report type: {$type}. Must be one of: ".implode(', ', array_keys(self::REPORT_MAP))
            );
        }
    }
}
