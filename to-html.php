<?php
/**
 * Convert sum.json into sum.html to easily use for tax paying.
 * CLI: php to-html.php -y=2021 -q=4
 */
define("HIDE_READONLY", true);
require __DIR__ . '/mollie/vendor/autoload.php';
require __DIR__ . '/mollie/vendor/mpdroog/core/init-cli.php';

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

$p = sprintf("%s/%sQ%s/sum.json", __DIR__, $year, $quarter);
if (! file_exists($p)) {
    echo "ERR: $p not found\n";
    exit(1);
}
$j = json_decode(file_get_contents($p), true);
if (! is_array($j)) {
    echo "ERR: $p not parsable\n";
    exit(1);
}
if (DEBUG) var_dump($j);

$html = '<html><head><title>Voorbelasting</title><style>.quote { padding: 1rem; border: 1px solid #dee2e6; }</style><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous"></head><body><div class="container">';
$html .= sprintf("<h1 class='pt-4'>💸 Voorbelasting (Pre-tax) %s/%s</h1>", $year, $quarter);
$html .= '<figure class="py-4 quote"><blockquote class="blockquote"><p>Voorbelasting is de btw die ondernemers aan andere ondernemers in rekening brengen voor de levering van goederen of diensten. De in rekening gebrachte btw kan door de ondernemer die de aankoop heeft gedaan worden afgetrokken als voorbelasting tijdens de btw-aangifte.</p></blockquote><figcaption class="blockquote-footer"><a href="https://www.finler.nl/kennis/voorbelasting/">Finler</a></figcaption></figure>';
$html .= "<table class='table'><thead><tr><th>Supplier</th><th>ID</th><th>Sum (incl.TAX)</th><th>Tax</th></tr></thead><tbody>";

$sum = "0.00";
$tax = "0.00";
foreach ($j as $entity => $invoices) {
    foreach ($invoices as $invoice) {
        $sum = bcadd($sum, $invoice["sum"], 2);
        if (isset($invoice["tax"])) $tax = bcadd($tax, $invoice["tax"], 2);
        $html .= sprintf("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>", $entity, $invoice["id"], $invoice["sum"], $invoice["tax"]);
    }
}
$sumRounded = ceil($sum);
$taxRounded = ceil($tax);

$html .= "</tbody>";
$html .= "<tfooter><tr><td colspan='2'>Total</td><td>$sum</td><td>$tax</td></tr>";
$html .= "<tr class='table-primary'><td colspan='2'>Total(rounded)</td><td>$sumRounded</td><td>$taxRounded</td></tr></tfooter>";
$html .= "</table>";
$html .= "<p>5b. Voorbelasting = $taxRounded</p></div></body></html>";
file_put_contents(str_replace(".json", ".html", $p), $html);

