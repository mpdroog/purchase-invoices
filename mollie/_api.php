<?php
const MOLLIE_BASE_URL = "https://api.mollie.com/v2/%s";

function mollie($endpoint, array $post) {
  $url = sprintf(MOLLIE_BASE_URL, $endpoint);
  $curl = curl_init($url);
  if ($curl === false) user_error("curl_init fail");
  $headers = [
    'Content-Type: application/json',
    sprintf('Authorization: Bearer %s', file_get_contents(__DIR__ . "/_bearer.txt"))
  ];

  $opt = 1;
  $opt &= curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  $opt &= curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  if (count($post) > 0) {
    $opt &= curl_setopt($curl, CURLOPT_POST, true);
    $opt &= curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post)); // sub-array support
  }
  $opt &= curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10); // 10sec connecting
  $opt &= curl_setopt($curl, CURLOPT_TIMEOUT, 20);        // 20sec waiting for response
  $opt &= curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
  $opt &= curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
  $opt &= curl_setopt($curl, CURLOPT_CAINFO, "cacert.pem");

  if ($opt !== 1) user_error("curl_setopt fail");

  $res = curl_exec($curl);
  $http = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  if ($http < 200 || $http > 299) {
    curl_close($curl);
    user_error(sprintf("HTTP(%s) => (%d) %s", $url, $http, $res));
  }
  curl_close($curl);
  $json = json_decode($res, true);
  return $json;
}

function download($url) {
  $curl = curl_init($url);
  if ($curl === false) user_error("curl_init fail");
  $headers = [
    'Accept: application/pdf',
  ];

  $opt = 1;
  $opt &= curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  $opt &= curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $opt &= curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10); // 10sec connecting
  $opt &= curl_setopt($curl, CURLOPT_TIMEOUT, 20);        // 20sec waiting for response
  $opt &= curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
  $opt &= curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
  $opt &= curl_setopt($curl, CURLOPT_CAINFO, "cacert.pem");

  if ($opt !== 1) user_error("curl_setopt fail");

  $res = curl_exec($curl);
  $http = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  if ($http < 200 || $http > 299) {
    curl_close($curl);
    user_error(sprintf("HTTP(%s) => (%d) %s", $url, $http, $res));
  }
  curl_close($curl);
  return $res;
}
