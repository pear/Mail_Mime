--TEST--
Bug #3513   support of RFC2231 in header fields. (ISO-8859-1)
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL);
$test = "Süper gröse tolle grüße.txt";
require_once('Mail/mime.php');

$Mime=new Mail_Mime();
$Mime->setTXTBody('');
$Mime->addAttachment('testfile',"text/plain", $test, FALSE, 'base64', 'attachment', 'iso-8859-1');

$body = $Mime->get();
$bodyarr=explode("\r\n",$body);
print_r($bodyarr[3]."\r\n");
print_r($bodyarr[4]."\r\n");
?>
--EXPECT--
Content-Disposition: attachment; filename="=?iso-8859-1?Q?S=FCper?==?iso-8859-1?Q?_gr=F6se?= tolle=?iso-8859-1?Q?_gr=FC=DFe.txt?="
