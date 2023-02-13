<?php

namespace modmore\Commerce_Payrexx\Gateways;

use modmore\Commerce\Gateways\Interfaces\RedirectTransactionInterface;
use modmore\Commerce\Gateways\Interfaces\TransactionInterface;
use modmore\Commerce\Gateways\Interfaces\WebhookTransactionInterface;

class Transaction implements TransactionInterface, RedirectTransactionInterface, WebhookTransactionInterface
{
    private \Payrexx\Models\Response\Gateway $response;

    public function __construct(\Payrexx\Models\Response\Gateway $response)
    {
        $this->response = $response;
    }

    public function isPaid(): bool
    {
        return $this->response->getStatus() === \Payrexx\Models\Response\Transaction::CONFIRMED;
    }

    public function isAwaitingConfirmation(): bool
    {
        return $this->response->getStatus() === \Payrexx\Models\Response\Transaction::WAITING;
    }

    public function isFailed(): bool
    {
        return in_array($this->response->getStatus(), [
            \Payrexx\Models\Response\Transaction::DECLINED,
            \Payrexx\Models\Response\Transaction::ERROR,
            \Payrexx\Models\Response\Transaction::EXPIRED,
        ], true);
    }

    public function isCancelled(): bool
    {
        return $this->response->getStatus() === \Payrexx\Models\Response\Transaction::CANCELLED;
    }

    public function getErrorMessage(): string
    {
        return ''; // nothing seems available in the response
    }

    public function getPaymentReference(): string
    {
        return (string)$this->response->getId();
    }

    public function getExtraInformation(): array
    {
        $data = [
            'payrexx_psp' => implode(', ', $this->response->getPsp()),
            'payrexx_pm' => implode(', ', $this->response->getPsp()),
        ];

        $invoices = $this->response->getInvoices();
        if (!empty($invoices) && is_array($invoices)) {
            foreach ($invoices as $invoice) {
                $transactions = !empty($invoice['transactions']) && is_array($invoice['transactions'])
                    ? $invoice['transactions']
                    : [];

                foreach ($transactions as $y => $transaction) {
                    $data["payrexx_transaction_{$y}"] = $transaction['uuid'] ?? '';
                    $data["payrexx_transaction_{$y}_status"] = $transaction['status'] ?? '';
                    $data["payrexx_transaction_{$y}_psp"] = $transaction['psp'] ?? '';
                    $data["payrexx_transaction_{$y}_amount"] = round($transaction['amount'] / 100, 2);
                    $data["payrexx_transaction_{$y}_fee"] = round($transaction['payrexx_fee'] / 100, 2);

                    if (!empty($transaction['payment']) && !empty($transaction['payment']['brand'])) {
                        $data["payrexx_transaction_{$y}_brand"] = $transaction['payment']['brand'];
                    }
                }
            }
        }

        return $data;
    }

    public function getData(): array
    {
        return $this->getExtraInformation();
    }

    public function isRedirect(): bool
    {
        return $this->response->getStatus() === \Payrexx\Models\Response\Transaction::WAITING;
    }

    public function getRedirectMethod(): string
    {
        return 'GET';
    }

    public function getRedirectUrl(): string
    {
        return $this->response->getLink();
    }

    public function getRedirectData(): array
    {
        return [];
    }

    public function getWebhookResponse(): string
    {
        return 'OK';
    }

    public function getWebhookResponseCode(): int
    {
        return 200;
    }
}
