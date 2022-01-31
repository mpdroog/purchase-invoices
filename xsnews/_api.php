<?php
$ch = curl_init();
if ($ch === false) {
    user_error("curl_init error?");
}

class API {
  private static $config = null;
  public static function init(array $c) {
    self::$config = $c;
  }

  public static function call ($method, $url, $args = []) {
    // Convert time to UTC to circumvent API-bug and have in the right format (with timezone offsets)
    if (isset($args["expiry"]) && strpos($args["expiry"], "T") === false) {
      $date = new DateTime($args["expiry"]);
      $date->setTimezone(new DateTimeZone('UTC'));
      $args["expiry"] = $date->format(\DateTime::RFC3339);
    }
    if (isset($args["created"]) && strpos($args["created"], "T") === false) {
      $date = new DateTime($args["created"]);
      $date->setTimezone(new DateTimeZone('UTC'));
      $args["created"] = $date->format(\DateTime::RFC3339);
    }
    $ret = self::rcall($method, $url, $args);

    if (DEBUG) {
        echo sprintf(
            "%s %s (%s) => %s\n",
            $method, $url,
            http_build_query($args),
            print_r($args, true)
        );
    }

    return $ret;
  }

  public static function rcall ($method, $url, $args = []) {
    global $ch;
    $conf = self::$config;
    $url = sprintf("%s%s", $conf["api"], $url);

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        sprintf('Authorization: Bearer %s', $conf["token"])
    ];

    $opt = 1;
    $opt &= curl_setopt($ch, CURLOPT_URL, $url);
    $opt &= curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $opt &= curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    $opt &= curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $opt &= curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10sec connecting
    $opt &= curl_setopt($ch, CURLOPT_TIMEOUT, 20);        // 20sec waiting for response

    $opt &= curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $opt &= curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    //$opt &= curl_setopt($ch, CURLOPT_CAINFO, "/data01/web/xsnews-action/lib/cacert.pem"); // TODO: Something cleaner?

    if ($opt !== 1) {
        user_error("one or more curl_setopt failed?");
    }

    if (count($args) > 0) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($args));
    }

    $res = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    return [
        "url" => $url,
        "ok" => $http >= 200 && $http <= 299,
        "http" => $http,
        "res" => $res,
        "json" => json_decode($res, true),
    ];
  }
}
