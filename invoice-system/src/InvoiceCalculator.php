<?php

/**
 * InvoiceCalculator - Helper class for invoice calculations
 *
 * Static utility methods for business logic
 * Client keeps changing their mind on requirements...
 */
class InvoiceCalculator
{

    /**
     * Calculate tax for an invoice
     *
     * Now loads tax rates from data/tax_rates.json
     *
     * @param float $subtotal The subtotal before tax
     * @param string $region Region code (e.g., "US-CA", "CA-ON")
     * @return float Tax amount
     */
    public static function calculateTax($subtotal, $region = 'US-CA')
    {
        // Load tax rates from JSON
        $taxFile = __DIR__ . '/../data/tax_rates.json';

        if (!file_exists($taxFile)) {
            // Fallback to default if file not found
            $taxRate = 0.10;
        } else {
            $taxData = json_decode(file_get_contents($taxFile), true);

            // Parse region (format: "COUNTRY-STATE" or just "COUNTRY")
            $parts = explode('-', $region);
            $country = $parts[0];
            $state = isset($parts[1]) ? $parts[1] : 'default';

            // Look up tax rate
            if (isset($taxData[$country][$state])) {
                $taxRate = $taxData[$country][$state];
            } else if (isset($taxData[$country]['default'])) {
                $taxRate = $taxData[$country]['default'];
            } else {
                // Ultimate fallback
                $taxRate = 0.10;
            }
        }

        return $subtotal * $taxRate;
    }

    /**
     * Apply business rules to an invoice
     *
     * Rules implemented:
     * 1. Orders over $1000 (before tax) get automatic 5% discount
     * 2. Discount applies to entire order
     *
     * @param Invoice $invoice
     * @return Invoice Modified invoice
     */
    public static function applyBusinessRules($invoice)
    {
        $total = $invoice->getTotal();

        // Rule: Orders over $1000 get automatic 5% discount
        if ($total > 1000) {
            $invoice->applyDiscount(5);
        }

        return $invoice;
    }

    /**
     * Calculate line item total
     * This one actually works correctly!
     *
     * @param array $item Item with price and quantity/qty
     * @return float Line item total
     */
    public static function calculateLineItem($item)
    {
        $price = $item['price'];

        // Handle both 'quantity' and 'qty' naming
        // (Someone was inconsistent with naming)
        $quantity = isset($item['quantity']) ? $item['quantity'] : $item['qty'];

        return $price * $quantity;
    }

    /**
     * Format currency for display
     * Quick helper I added
     *
     * @param float $amount
     * @return string Formatted currency
     */
    public static function formatCurrency($amount)
    {
        return '$' . number_format($amount, 2);
    }

    /**
     * Validate invoice data
     * 
     * Checks:
     * - No negative prices
     * - No negative quantities
     * - Customer name not empty
     * - At least one item
     *
     * @param Invoice $invoice
     * @return array Array of error messages (empty if valid)
     */
    public static function validateInvoice($invoice)
    {
        $errors = [];

        // Check customer name
        if (empty($invoice->getCustomer())) {
            $errors[] = "Customer name cannot be empty";
        }

        // Check items
        $items = $invoice->getItems();
        if (empty($items)) {
            $errors[] = "Invoice must have at least one item";
        }

        // Check each item
        foreach ($items as $index => $item) {
            if (empty($item['name'])) {
                $errors[] = "Item #" . ($index + 1) . " has no name";
            }
            if ($item['price'] < 0) {
                $errors[] = "Item '{$item['name']}' has negative price";
            }
            $qty = isset($item['qty']) ? $item['qty'] : 0;
            if ($qty <= 0) {
                $errors[] = "Item '{$item['name']}' has invalid quantity";
            }
        }

        return $errors;
    }
}
