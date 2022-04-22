<?php
/**
 * Download all purchase invoices for given year/quarter
 * for quicker billing.
 * CLI: php index.php -y=2021 -q=4
 */
define("HIDE_READONLY", true);
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
$year = intval($year);

$tsRange = [
    strtotime(sprintf("%d-%02d-01", $year, $quarters[$quarter][0])),
    strtotime("+1 month", strtotime(sprintf("%d-%d-01", $year, $quarters[$quarter][1])))-1,
];
if (VERBOSE) var_dump($tsRange);

$out = $year . "Q" . $quarter;
if (! file_exists($out) && ! mkdir($out)) {
    echo "mkdir($out) failed?\n";
    exit(1);
}

API::init(json_decode(file_get_contents(__DIR__ . "/_config.json"), true));
$invoices = API::call("GET", "/finance/invoices", []);
if (DEBUG) var_dump($invoices);
if ($invoices["http"] !== 200) {
    echo "ERR: API didn't gave valid response.\n";
    exit(1);
}

$sum = file_exists(sprintf("%s/sum.json", $out)) ? json_decode(file_get_contents(sprintf("%s/sum.json", $out)), true) : [];
$sum["xsnews"] = $sum["xsnews"] ?? [];

foreach ($invoices["json"] as $invoice) {
    if ($invoice["Date"] >= $tsRange[0] && $invoice["Date"] <= $tsRange[1]) {
        $sum_notax = $invoice["TotalAmount"]["Decimal"];
        $sumtxt = round(bcmul($sum_notax, "1.21", 4), 2); // TODO: Risky here..
        $tax = bcsub($sumtxt, $sum_notax, 2);

        echo sprintf("Add invoice=%s date=%s total=%s tax=%s\n", $invoice["ID"], date("Y-m-d", $invoice["Date"]), $sumtxt, $tax);
        $bin = API::call("GET", sprintf("/finance/invoice/%s/pdf", $invoice["ID"]));
        file_put_contents(sprintf("%s/%s.pdf", $out, $invoice["ID"]), $bin["res"]);

        $sum["xsnews"][] = [
            "id" => $invoice["ID"],
            "paydate" => $invoice["Date"],
            "sum" => $sumtxt,
            "tax" => $tax,
        ];
    }
}

file_put_contents(sprintf("%s/sum.json", $out), json_encode($sum));
