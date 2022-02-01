<?php
const MOLLIE_BASE_URL = "https://api.mollie.com/v2/%s";

function mollie($endpoint, array $post) {
  $url = sprintf(MOLLIE_BASE_URL, $endpoint);
  $curl = curl_init($url);
  $headers = [
    'Content-Type: application/json',
    sprintf('Authorization: Bearer %s', file_get_contents(__DIR__ . "/_bearer.txt"))
  ];
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  if (count($post) > 0) {
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post)); // sub-array support
  }
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

  $res = curl_exec($curl);
  $http = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  if ($http < 200 || $http > 299) {
    user_error(sprintf("HTTP(%s) => (%d) %s", $url, $http, $res));
  }
  curl_close($curl);
  $json = json_decode($res, true);
  return $json;
}
function download($url) {
  $curl = curl_init($url);
  $headers = [
    'Accept: application/pdf',
  ];
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

  $res = curl_exec($curl);
  $http = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  if ($http < 200 || $http > 299) {
    curl_close($curl);
    user_error(sprintf("HTTP(%s) => (%d) %s", $url, $http, $res));
  }
  curl_close($curl);
  return $res;
}
