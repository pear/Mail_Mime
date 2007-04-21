--TEST--
Bug #3513   support of RFC2231 in header fields. (ISO-2022-JP)
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL);
mb_internal_encoding('ISO-2022-JP');
$testEncoded="GyRCRnxLXDhsGyhCLnR4dA==";
$test = base64_decode($testEncoded); // Japanese filename in ISO-2022-JP charset.
require_once('Mail/mime.php');

$Mime=new Mail_Mime();
$Mime->setTXTBody('');
$Mime->addAttachment('testfile',"text/plain", $test, FALSE, 'base64', 'attachment', 'iso-2022-jp');

$body = $Mime->get();
$bodyarr=explode("\r\n",$body);
print_r($bodyarr[3]."\r\n");
print_r($bodyarr[4]."\r\n");
?>
--EXPECT--
Content-Disposition: attachment; filename="=?iso-2022-jp?=1B=24BF=7CK=5C8l=1B=28B.txt?="
