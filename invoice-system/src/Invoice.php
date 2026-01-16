<?php

/**
 * Invoice Class
 *
 * Handles invoice creation and management
 * Started: 2 weeks ago
 * Last modified: Friday (was in a hurry)
 */
class Invoice
{

    private $customer;
    private $items = [];
    private $discount = 0;
    private $id;
    private $createdAt;

    public function __construct($customerName)
    {
        $this->customer = $customerName;
        $this->id = time(); // Not sure if this is the best approach...
        $this->createdAt = date('Y-m-d H:i:s');
    }

    /**
     * Add an item to the invoice
     * Now includes validation
     */
    public function addItem($name, $price, $quantity)
    {
        // Validate inputs
        if (empty($name)) {
            throw new Exception("Item name cannot be empty");
        }
        if ($price < 0) {
            throw new Exception("Price cannot be negative");
        }
        if ($quantity <= 0) {
            throw new Exception("Quantity must be greater than zero");
        }

        $this->items[] = [
            'name' => $name,
            'price' => $price,
            'qty' => $quantity  // Using 'qty' for consistency
        ];
    }

    /**
     * Calculate total
     * Fixed: Now using 'qty' to match addItem()
     */
    public function getTotal()
    {
        $total = 0;
        foreach ($this->items as $item) {
            // Using 'qty' to match what we store in addItem()
            $total += $item['price'] * $item['qty'];
        }
        return $total - $this->discount;
    }

    /**
     * Apply discount to invoice
     * Discount is applied to subtotal (before tax)
     */
    public function applyDiscount($percent)
    {
        // Validate discount percentage
        if ($percent < 0 || $percent > 100) {
            throw new Exception("Discount percent must be between 0 and 100");
        }

        $subtotal = $this->getTotal();
        $this->discount = $subtotal * ($percent / 100);

        return $this->discount;
    }

    /**
     * Get invoice ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get customer name
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Get items array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Convert invoice to array for JSON serialization
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'customer' => $this->customer,
            'items' => $this->items,
            'discount' => $this->discount,
            'total' => $this->getTotal(),
            'created_at' => $this->createdAt
        ];
    }

    /**
     * Save invoice to file
     * Fixed: Now appends to existing invoices instead of overwriting
     */
    public function saveToFile($filename = 'data/invoices.json')
    {
        $data = $this->toArray();
        $invoices = [];

        // Load existing invoices if file exists
        if (file_exists($filename)) {
            $contents = file_get_contents($filename);
            $existing = json_decode($contents, true);

            // Handle both single invoice and array of invoices
            if (isset($existing['id'])) {
                $invoices = [$existing];
            } else if (is_array($existing)) {
                $invoices = $existing;
            }
        }

        // Append new invoice
        $invoices[] = $data;

        // Save all invoices
        file_put_contents($filename, json_encode($invoices, JSON_PRETTY_PRINT));

        return true;
    }

    /**
     * Load invoice from file by ID
     * Started this but didn't finish testing it
     */
    public static function loadFromFile($id, $filename = 'data/invoices.json')
    {
        if (!file_exists($filename)) {
            throw new Exception("Invoice file not found");
        }

        $contents = file_get_contents($filename);
        $invoices = json_decode($contents, true);

        // Handle both single invoice and array of invoices
        // (since saveToFile is broken and only saves one)
        if (isset($invoices['id'])) {
            $invoices = [$invoices];
        }

        foreach ($invoices as $invoiceData) {
            if ($invoiceData['id'] == $id) {
                $invoice = new Invoice($invoiceData['customer']);
                $invoice->id = $invoiceData['id'];
                $invoice->discount = $invoiceData['discount'];

                foreach ($invoiceData['items'] as $item) {
                    // This might break because of the qty/quantity issue
                    $qty = isset($item['quantity']) ? $item['quantity'] : $item['qty'];
                    $invoice->addItem($item['name'], $item['price'], $qty);
                }

                return $invoice;
            }
        }

        throw new Exception("Invoice not found: " . $id);
    }
}
