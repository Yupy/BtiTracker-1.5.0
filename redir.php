<?php
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

dbconn();

if (!isset(user::$current)) {
    die();
}

$url = '';

while (list($var, $val) = each($_GET)) {
    $url .= "&" . $var . "=" . $val;
}

if (preg_match("/([<>'\"]|&#039|&#33;|&#34|%27|%22|%3E|%3C|&#x27|&#x22|&#x3E|&#x3C|\.js)/i", $url)) {
    header("Location: http://www.google.ro");
}

$i = strpos($url, "&url=");

if ($i !== false) {
    $url = utf8::substr($url, $i + 5);
}

if (utf8::substr($url, 0, 4) == "www.") {
    $url = "http://" . $url;
}

if (strlen($url) < 10) {
    die();
}

echo("<html><head><meta http-equiv='refresh' content='3;url=" . $url . "'></head><body>\n");
echo("<div style='width:100%;text-align:center;background: #E9D58F;border: 1px solid #CEAA49;margin: 5px 0 5px 0;padding: 0 5px 0 5px;font-weight: bold;'>Redirecting you to...<br />\n");
echo(security::html_safe($url) . "</div></body></html>\n");

?>
