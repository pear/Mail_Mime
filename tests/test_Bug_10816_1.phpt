--TEST--
Bug #10816   Unwanted linebreak at the end of output
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // ignore E_STRICT
define("MAIL_MIMEPART_CRLF", "#");
include("Mail/mime.php");
$encoder = new Mail_mime();
$encoder->_build_params['ignore-iconv'] = true;
$encoder->setTXTBody('test');
$encoder->setHTMLBody('<b>test</b>');
$encoder->addAttachment('Just a test', 'application/octet-stream', 'test.txt', false);
$body = $encoder->get();
$taillength = -1 * strlen(MAIL_MIMEPART_CRLF) * 2;
if (substr($body, $taillength) == (MAIL_MIMEPART_CRLF . MAIL_MIMEPART_CRLF)){
    print("FAILED\n");
    print("Body:\n");
    print("..." . substr($body, -10) . "\n");
}else{
    print("OK\n");
}
--EXPECT--
OK

