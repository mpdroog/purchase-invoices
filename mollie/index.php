<?php
/**
 * Download all purchase invoices for given year/quarter
 * for quicker billing.
 * CLI: php index.php -y=2021 -q=4
 */
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/_api.php';
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

$out = __DIR__ . "/" . $year . "Q" . $quarter;
if (! file_exists($out) && ! mkdir($out)) {
    echo "mkdir($out) failed?\n";
    exit(1);
}

$res = mollie("invoices?year=$year", []);
foreach ($res["_embedded"]["invoices"] as $invoice) {
    if ($invoice["resource"] !== "invoice") continue;
    if ($invoice["issuedAt"] >= $dateRange[0] && $invoice["issuedAt"] <= $dateRange[1]) {
        if (DEBUG) var_dump($invoice);
        echo sprintf("Invoice=%s issuedate=%s tax=%s\n", $invoice["reference"], $invoice["issuedAt"], $invoice["vatAmount"]["value"]);

        $link = $invoice["_links"]["pdf"]["href"];
        $bin = download($link);
        file_put_contents(sprintf("%s/%s.pdf", $out, $invoice["reference"]), $bin);
    }
}
