<?php
/**
 * Download all purchase invoices for given year/quarter
 * for quicker billing.
 * CLI: php index.php -y=2021 -q=4
 */
define("HIDE_READONLY", true);
use Transip\Api\Library\TransipAPI;
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/mpdroog/core/init-cli.php';

// Year quarters to months
$quarters = [
    "1" => [1, 3],
    "2" => [4, 6],
    "3" => [7, 9],
    "4" => [10, 12],
];

$year = $_CLI["flags"]["-y"] ?? null;
if (! is_numeric($year)) {
    echo "ERR: Missing year we want the invoices for.\n";
    echo "EXPLAIN: Missing arg -y=N\n";
    exit(1);
}
$quarter = $_CLI["flags"]["-q"] ?? null;
if (! isset($quarters[$quarter])) {
    echo "ERR: Missing quarter of year we want the invoices for.\n";
    echo "EXPLAIN: Missing arg -q=N\n";
    exit(1);
}

$dateRange = [
    sprintf("%d-%d-01", $year, $quarters[$quarter][0]),
    date("Y-m-d", strtotime("+1 month", strtotime(sprintf("%d-%d-01", $year, $quarters[$quarter][1])))-1),
];
if (VERBOSE) var_dump($dateRange);

$out = $year . "Q" . $quarter;
if (! file_exists($out) && ! mkdir($out)) {
    echo "mkdir($out) failed?\n";
    exit(1);
}

$cfg = json_decode(file_get_contents(__DIR__ . "/_config.json"));
if (DEBUG) var_dump($cfg);
// If the generated token should only be usable by whitelisted IP addresses in your Controlpanel
$generateWhitelistOnlyTokens = true;

$api = new TransipAPI(
    $cfg->login,
    $cfg->privateKey,
    $generateWhitelistOnlyTokens
);

try {
    $res = $api->invoice()->getAll();
} catch (\Exception $e) {
    echo "https://www.transip.nl/cp/account/api/\n";
    echo $e->getMessage() . "\n";
    exit(1);
}

foreach ($res as $invoice) {
    // https://github.com/transip/transip-api-php/blob/66a7524cc280e75173696109d382e19de6f3f22e/src/Entity/Invoice/InvoiceItem.php
    if ($invoice->getPayDate() >= $dateRange[0] && $invoice->getPayDate() <= $dateRange[1]) {
        $total = round(bcdiv($invoice->getTotalAmountInclVat(), 100, 3), 2);
        $tax = round(bcdiv($invoice->getTotalAmountInclVat() - $invoice->getTotalAmount(), 100, 3), 2);
        echo sprintf("Add=%s total=%s tax=%s date=%s\n", $invoice->getInvoiceNumber(), $total, $tax, $invoice->getPayDate());

        $bin = $api->invoicePdf()->getByInvoiceNumber($invoice->getInvoiceNumber())->getPdf();
        file_put_contents(sprintf("%s/%s.pdf", $out,  $invoice->getInvoiceNumber()), $bin);
    }
}
