<?php

namespace App\Contracts;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Contract for models that can generate receipts.
 *
 * Any model implementing this interface can generate a receipt/invoice.
 * This allows payment and receipt flow to be reusable across Sales, Contracts, and other models.
 */
interface Receipable
{
    /**
     * Get the receipt/invoice number for this model.
     * Examples: invoice_number, contract_number, ticket_number.
     *
     * @return string The receipt identifier
     */
    public function getReceiptNumber(): string;

    /**
     * Get the date when this transaction occurred.
     *
     * @return Carbon The transaction date
     */
    public function getReceiptDate(): Carbon;

    /**
     * Get the total amount for this receipt.
     *
     * @return int The total in currency cents/units
     */
    public function getReceiptTotal(): int;

    /**
     * Get the receipt items (line items).
     * Each item should have: name, quantity, unit_price, sub_total, applied_tax, tax_amount.
     *
     * @return array Array of receipt items
     */
    public function getReceiptItems(): array;

    /**
     * Get the payments associated with this receipt.
     *
     * @return array Array of payments with details (amount, method, date)
     */
    public function getReceiptPayments(): array;

    /**
     * Get the subtotal (sum of item subtotals before tax).
     *
     * @return int The subtotal in currency units
     */
    public function getReceiptSubtotal(): int;

    /**
     * Get the total tax amount.
     *
     * @return int The tax total in currency units
     */
    public function getReceiptTaxTotal(): int;

    /**
     * Get the payments relationship for this receipt.
     *
     * @return MorphMany The morphMany relationship to payments
     */
    public function payments(): MorphMany;

    /**
     * Check if this receipt can generate a printable receipt.
     *
     * @return bool True if the receipt is ready to be printed
     */
    public function canGenerateReceipt(): bool;
}
