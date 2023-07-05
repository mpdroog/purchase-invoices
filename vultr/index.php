<?php
/**
 * Download all purchase invoices for given year/quarter
 * for quicker billing.
 * CLI: php index.php -y=2021 -q=4
 */
define("HIDE_READONLY", true);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/mpdroog/core/init-cli.php';
require __DIR__ . '/_api.php';

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
    sprintf("%d-%02d-01", $year, $quarters[$quarter][0]),
    date("Y-m-d", strtotime("+1 month", strtotime(sprintf("%d-%d-01", $year, $quarters[$quarter][1])))-1),
];
if (VERBOSE) var_dump($dateRange);

$out = $year . "Q" . $quarter;
if (! file_exists($out) && ! mkdir($out)) {
    echo "mkdir($out) failed?\n";
    exit(1);
}

$invoices = vultr("billing/invoices", []);
if (DEBUG) var_dump($invoices);

$sum = file_exists(sprintf("%s/sum.json", $out)) ? json_decode(file_get_contents(sprintf("%s/sum.json", $out)), true) : [];
$sum["vultr"] = $sum["vultr"] ?? [];

foreach ($invoices->billing_invoices as $invoice) {
    if (DEBUG) var_dump($invoice);

    $date = date("Y-m-d", strtotime($invoice->date));
    if ($date >= $dateRange[0] && $date <= $dateRange[1]) {
        $balance = $invoice->balance;
        $lines = vultr(sprintf("billing/invoices/%s/items", $invoice->id), []);

        $linesum = "0.00";
        foreach ($lines->invoice_items as $item) {
            $v = round(bcmul($item->units, $item->unit_price, 5), 2, PHP_ROUND_HALF_UP);
            $linesum = bcadd($linesum, $v, 5);
        }
        $tax = "0.00";
        if ($balance < 0) {
            // Only allow half for some weird reason
            $linesum = bcdiv($linesum, 2, 5);
        }
        $tax = round(bcmul($linesum, "0.21", 5), 2, PHP_ROUND_HALF_UP);

        echo sprintf("Invoice %s amount=%s tax=%s date=%s\n", $invoice->id, $invoice->amount, $tax, $invoice->date);
        // https://my.vultr.com/billing/invoice/20969207
        // $bin = $billing->getPDF($client, $invoice->invoice_uuid);
        // file_put_contents(sprintf("%s/%s.pdf", $out, $invoice->id), $bin);
        $sum["vultr"][] = [
            "id" => $invoice->id,
            "paydate" => $invoice->date,
            "sum" => $invoice->amount,
            "tax" => $tax, /* trying but can be wrong.. */
        ];
    }
}

file_put_contents(sprintf("%s/sum.json", $out), json_encode($sum));
