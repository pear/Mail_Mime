--TEST--
Bug #3513   support of RFC2231 in header fields. (UTF-8)
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL);
$test = "Süper gröse tolle grüße.txt";
require_once('Mail/mime.php');
$Mime=new Mail_Mime();
$Mime->_build_params['ignore-iconv'] = true;
$Mime->addAttachment('testfile',"text/plain", $test, FALSE, 'base64', 'attachment', 'UTF-8', 'de');
$root = $Mime->_addMixedPart();
$enc = $Mime->_addAttachmentPart($root, $Mime->_parts[0]);
print($enc->_headers['Content-Disposition']);
--EXPECT--
attachment;
 filename*0*="UTF-8'de'S%C3%BCper%20gr%C3%B6se%20tolle%20gr%C3%BC%C3%9Fe."
 filename*1*="txt";
