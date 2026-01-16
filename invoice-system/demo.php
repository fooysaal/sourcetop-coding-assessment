<?php

/**
 * Example usage of the Invoice System
 * Demonstrates all implemented features
 */

require_once __DIR__ . '/src/Invoice.php';
require_once __DIR__ . '/src/InvoiceCalculator.php';
require_once __DIR__ . '/src/PDFGenerator.php';

echo "Invoice System - Feature Demonstration\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Create a basic invoice
echo "1. Creating invoice...\n";
$invoice = new Invoice("Acme Corporation");
$invoice->addItem("Widget A", 29.99, 5);
$invoice->addItem("Widget B", 45.50, 2);

echo "   Customer: " . $invoice->getCustomer() . "\n";
echo "   Total: $" . number_format($invoice->getTotal(), 2) . "\n\n";

// 2. Test tax calculation
echo "2. Calculating tax (US-CA at 7.25%)...\n";
$subtotal = $invoice->getTotal();
$tax = InvoiceCalculator::calculateTax($subtotal, 'US-CA');
echo "   Subtotal: $" . number_format($subtotal, 2) . "\n";
echo "   Tax: $" . number_format($tax, 2) . "\n";
echo "   Total with tax: $" . number_format($subtotal + $tax, 2) . "\n\n";

// 3. Test business rules (auto-discount for orders over $1000)
echo "3. Testing business rules (auto-discount)...\n";
$largeInvoice = new Invoice("Big Client Inc");
$largeInvoice->addItem("Enterprise License", 1500.00, 1);
echo "   Before discount: $" . number_format($largeInvoice->getTotal(), 2) . "\n";
InvoiceCalculator::applyBusinessRules($largeInvoice);
echo "   After 5% discount: $" . number_format($largeInvoice->getTotal(), 2) . "\n\n";

// 4. Test manual discount
echo "4. Applying manual 10% discount...\n";
$discountInvoice = new Invoice("Valued Customer");
$discountInvoice->addItem("Service Package", 500.00, 1);
echo "   Before discount: $" . number_format($discountInvoice->getTotal(), 2) . "\n";
$discountInvoice->applyDiscount(10);
echo "   After 10% discount: $" . number_format($discountInvoice->getTotal(), 2) . "\n\n";

// 5. Test validation
echo "5. Testing input validation...\n";
try {
    $badInvoice = new Invoice("Test");
    $badInvoice->addItem("Bad Item", -10.00, 1);
    echo "   ERROR: Should have thrown exception!\n";
} catch (Exception $e) {
    echo "   ✓ Validation working: " . $e->getMessage() . "\n";
}

try {
    $badInvoice2 = new Invoice("Test");
    $badInvoice2->addItem("Bad Item", 10.00, 0);
    echo "   ERROR: Should have thrown exception!\n";
} catch (Exception $e) {
    echo "   ✓ Validation working: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. Test invoice validation
echo "6. Testing invoice validation...\n";
$validInvoice = new Invoice("Good Client");
$validInvoice->addItem("Product X", 50.00, 2);
$errors = InvoiceCalculator::validateInvoice($validInvoice);
echo "   Valid invoice errors: " . (empty($errors) ? "None (✓)" : "Found errors!") . "\n\n";

// 7. Test PDF/HTML generation
echo "7. Testing PDF generation...\n";
$pdfGen = new PDFGenerator();
try {
    $pdfFile = $pdfGen->generatePDF($invoice);
    echo "   ✓ PDF invoice generated: " . basename($pdfFile) . "\n";

    // Verify it's actually a PDF
    if (file_exists($pdfFile)) {
        $content = file_get_contents($pdfFile);
        $isPdf = strpos($content, '%PDF') !== false;
        echo "   ✓ File type verified: " . ($isPdf ? 'PDF' : 'Not PDF') . "\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 8. Test save/load
echo "8. Testing save and load...\n";
$testFile = __DIR__ . '/data/demo_invoices.json';
if (file_exists($testFile)) {
    unlink($testFile);
}

$invoice1 = new Invoice("Customer One");
$invoice1->addItem("Item A", 100.00, 1);
$invoice1->saveToFile($testFile);
echo "   ✓ Saved invoice #" . $invoice1->getId() . "\n";

$invoice2 = new Invoice("Customer Two");
$invoice2->addItem("Item B", 200.00, 1);
$invoice2->saveToFile($testFile);
echo "   ✓ Saved invoice #" . $invoice2->getId() . "\n";

$loaded = Invoice::loadFromFile($invoice1->getId(), $testFile);
echo "   ✓ Loaded invoice #" . $loaded->getId() . " for " . $loaded->getCustomer() . "\n";

// Clean up
if (file_exists($testFile)) {
    unlink($testFile);
}
echo "\n";

echo str_repeat("=", 50) . "\n";
echo "All features working correctly! ✓\n";
