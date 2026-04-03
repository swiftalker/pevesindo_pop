<?php

namespace App\Engine\Odoo\Reports;

use App\Engine\Odoo\Exceptions\OdooApiException;
use App\Engine\Odoo\Sessions\SessionManager;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

/**
 * Fetches PDF reports from Odoo's /report/pdf/ web controller.
 *
 * This endpoint does NOT accept the JSON-2 API key — it requires
 * a valid web session cookie for auth. On 401, the session is
 * automatically refreshed and the request is retried once.
 */
class ReportService
{
    /**
     * Odoo report action names by short type.
     */
    protected const REPORT_MAP = [
        'quotation' => 'sale.report_saleorder',
        'order' => 'sale.report_saleorder',
        'invoice' => 'account.report_invoice_with_payments',
    ];

    private bool $sessionRefreshed = false;

    public function __construct(
        protected SessionManager $sessionManager,
    ) {}

    /**
     * Fetch a PDF report from Odoo.
     *
     * @param  string  $type  Short type: quotation, order, invoice
     * @param  int  $odooId  Odoo record ID
     * @param  int|null  $invoiceId  Specific invoice to download (for partial DP invoices)
     * @return string Raw PDF content
     *
     * @throws OdooApiException
     */
    public function fetchPdf(string $type, int $odooId, ?int $invoiceId = null): string
    {
        $this->validateType($type);

        $reportName = self::REPORT_MAP[$type];
        $targetId = $invoiceId ?? $odooId;
        $baseUrl = rtrim(config('odoo.base_url'), '/');

        return $this->withSession(function (PendingRequest $client) use ($baseUrl, $reportName, $targetId) {
            $response = $client->get("{$baseUrl}/report/pdf/{$reportName}/{$targetId}");

            // Handle session expiry — refresh and retry once.
            if (in_array($response->status(), [401, 403]) && ! $this->sessionRefreshed) {
                $this->sessionManager->invalidate();
                $this->sessionManager->getCookie();
                $this->sessionRefreshed = true;

                // Recursive retry with fresh session.
                return $this->withSession(fn (PendingRequest $c) => $this->validatePdfResponse($c->get("{$baseUrl}/report/pdf/{$reportName}/{$targetId}"))
                );
            }

            return $this->validatePdfResponse($response);
        });
    }

    /**
     * Stream the PDF directly to an HTTP response.
     *
     * @param  string  $type  Short type: quotation, order, invoice
     * @param  int  $odooId  Odoo record ID
     * @param  string  $filename  Download filename
     */
    public function streamPdf(string $type, int $odooId, string $filename): Response
    {
        $pdf = $this->fetchPdf($type, $odooId);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length' => strlen($pdf),
        ]);
    }

    /**
     * Get the Odoo report action name for a short type.
     *
     * @throws OdooApiException
     */
    public function getReportName(string $type): string
    {
        $this->validateType($type);

        return self::REPORT_MAP[$type];
    }

    /**
     * Build a sanitized download filename.
     */
    public function buildFilename(string $prefix, string $documentNumber, string $ext = 'pdf'): string
    {
        $safe = str_replace(['/', ' ', '\\'], '-', $documentNumber);

        return "{$prefix}-{$safe}.{$ext}";
    }

    /**
     * Execute a closure with a session-authenticated HTTP client.
     * Automatically retries once on 401/403.
     *
     * @template TReturn
     *
     * @param  \Closure(PendingRequest): TReturn  $fn
     * @return TReturn
     */
    protected function withSession(\Closure $fn): mixed
    {
        return $fn($this->makeClient());
    }

    /**
     * Build an HTTP client with the web session cookie.
     */
    protected function makeClient(): PendingRequest
    {
        return Http::withOptions([
            'allow_redirects' => false,
        ])
            ->timeout(config('odoo.timeout', 60))
            ->withHeaders([
                'Cookie' => $this->sessionManager->getCookie(),
            ]);
    }

    /**
     * Validate the response contains actual PDF content.
     *
     * @throws OdooApiException
     */
    protected function validatePdfResponse(\Illuminate\Http\Client\Response $response): string
    {
        if ($response->status() !== 200) {
            throw new OdooApiException(
                "Odoo report request failed (HTTP {$response->status()})",
                $response->status()
            );
        }

        $body = $response->body();

        if (empty($body)) {
            throw new OdooApiException('Odoo returned an empty PDF document.');
        }

        // Odoo returns HTML error pages on auth failure or missing report template.
        if (str_starts_with(trim($body), '<')) {
            throw new OdooApiException(
                'Odoo returned HTML instead of PDF. The session may have expired or the report template does not exist.'
            );
        }

        return $body;
    }

    /**
     * @throws OdooApiException
     */
    protected function validateType(string $type): void
    {
        if (! isset(self::REPORT_MAP[$type])) {
            throw new OdooApiException(
                "Invalid report type: [{$type}]. Valid types: ".implode(', ', array_keys(self::REPORT_MAP))
            );
        }
    }
}
