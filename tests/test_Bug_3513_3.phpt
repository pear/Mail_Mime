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
$Mime->_build_params['ignore-iconv'] = true;
$Mime->addAttachment('testfile',"text/plain", $test, FALSE, 'base64', 'attachment', 'iso-2022-jp', '');
$root = $Mime->_addMixedPart();
$enc = $Mime->_addAttachmentPart($root, $Mime->_parts[0]);
print($enc->_headers['Content-Disposition']);
?>
--EXPECT--
attachment;
 filename*="iso-2022-jp''%1B%24BF%7CK%5C8l%1B%28B.txt"
