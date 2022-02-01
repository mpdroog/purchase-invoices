<?php
/**
 * Download all purchase invoices for given year/quarter
 * for quicker billing.
 * CLI: php index.php -y=2021 -q=4
 */
define("HIDE_READONLY", true);
use DigitalOceanV2\HttpClient\Util\QueryStringBuilder;

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

$client = new DigitalOceanV2\Client();
$client->authenticate(file_get_contents(__DIR__ . "/_token.txt")); // https://cloud.digitalocean.com/settings/applications
//$pager = new DigitalOceanV2\ResultPager($client);

// https://docs.digitalocean.com/reference/api/api-reference/#operation/list_billing_history
class BillingDO extends \DigitalOceanV2\Api\AbstractApi {
    private const URI_PREFIX = '/v2/';

    public function getInvoices()
    {
        return $this->get('customers/my/invoices');
    }
    public function getHistoricalInvoices()
    {
        return $this->get('customers/my/billing_history');
    }
    public function getPDF($client, $uuid)
    {
        $uri = "customers/my/invoices/$uuid/pdf";
        $res = $client->getHttpClient()->get(self::prepareUri($uri, []), []);
        return $res->getBody();
        /*return $this->get(
            [],
            ['Content-Type' => 'application/pdf']
        );*/
    }

    // https://github.com/DigitalOceanPHP/Client/blob/108781a9495d84e68041c77c42eca6c0bf0cb2c0/src/Api/AbstractApi.php#L81
    private static function prepareUri(string $uri, array $query = []): string
    {
        return \sprintf('%s%s%s', self::URI_PREFIX, $uri, QueryStringBuilder::build($query));
    }
}
$billing = new BillingDO($client);
$invoices = $billing->getHistoricalInvoices();
if (DEBUG) var_dump($invoices);

foreach ($invoices->billing_history as $invoice) {
    if (DEBUG) var_dump($invoice);
    if (strtolower($invoice->type) !== "invoice") continue;

    $date = date("Y-m-d", strtotime($invoice->date));
    if ($date >= $dateRange[0] && $date <= $dateRange[1]) {
        echo sprintf("Invoice %s amount=%s date=%s\n", $invoice->invoice_id, $invoice->amount, $invoice->date);
        $bin = $billing->getPDF($client, $invoice->invoice_uuid);
        file_put_contents(sprintf("%s/%s.pdf", $out, $invoice->invoice_id), $bin);
    }
}
