--TEST--
Bug #3513   support of RFC2231 in header fields. (UTF-8)
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL);
$test = "Süper gröse tolle grüße.txt";
require_once('Mail/mime.php');

$Mime=new Mail_Mime();
$Mime->setTXTBody('');
$Mime->addAttachment('testfile',"text/plain", $test, FALSE, 'base64', 'attachment', 'UTF-8');

$body = $Mime->get();
$bodyarr=explode("\r\n",$body);
print_r($bodyarr[3]."\r\n");
print_r($bodyarr[4]."\r\n");
?>
--EXPECT--
Content-Disposition: attachment; filename="=?UTF-8?Q?S=C3=BCper?==?UTF-8?Q?_gr=C3=B6se?= tolle=?UTF-8?Q?_gr=C3=BC=C3=9Fe.txt?="
