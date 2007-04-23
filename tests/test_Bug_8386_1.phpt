--TEST--
Bug #8386   HTML body not correctly encoded if attachments present
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // ignore E_STRICT
define("MAIL_MIMEPART_CRLF", "\n#");
include("Mail/mime.php");
$encoder = new Mail_mime();
$encoder->_build_params['ignore-iconv'] = true;
$encoder->setTXTBody('test');
$encoder->setHTMLBody('<b>test</b>');
$encoder->addAttachment('Just a test', 'application/octet-stream', 'test.txt', false);
$body = $encoder->get();
if (strpos($body, '--' . MAIL_MIMEPART_CRLF . '--=')){
    print("FAILED\n");
    print("Single delimiter() between 2 parts found.\n");
    print($body);
}else{
    print("OK");
}
?>
--EXPECT--
OK
