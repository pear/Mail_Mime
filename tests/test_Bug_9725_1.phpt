--TEST--
Bug #9725   multipart/related & alternative wrong order
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // ignore E_STRICT
include("Mail/mime.php");

$mime = new Mail_mime();
$mime->setTXTBody("test");
$mime->setHTMLBody("test");
$mime->addHTMLImage("test", 'application/octet-stream', '', false);
$body = $mime->get();
$head = $mime->headers();
$headCT = $head['Content-Type'];
$headCT = explode(";", $headCT);
$headCT = $headCT[0];

$ct = preg_match_all('|Content-Type: (.*);|', $body, $matches);
print($headCT);
print("\n");
foreach ($matches[1] as $match){
    print($match);
    print("\n");
}
--EXPECT--
multipart/related
multipart/alternative
text/plain
text/html
application/octet-stream
