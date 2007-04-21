--TEST--
Tests for _parseHeaderValue
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // suppress E_STRICT errors

require_once('Mail/mime.php');

$Mime = new Mail_Mime();
$Mime->setTXTBody('Test message.');
$contentAppend = 'testparam1="test1;semicolon";testparam2=two; testparam3="three"; '
                .'testparam4="four\;4\;four"; testparam5=five\;5\;five; testparam6=\'six\'; '
                .'testparam7=\'seven;7\';testparam8=\'eight\;8\'';
$Mime->addAttachment('test file contents', "text/plain; $contentAppend", 'test.txt', FALSE);

$body = $Mime->get();

$hdrs = '';
foreach ($Mime->headers() AS $name => $val) {
    $hdrs .= "$name: $val\n";
}
$hdrs .= "To: Receiver <receiver@example.com>\n";
$hdrs .= "From: Sender <sender@example.com>\n";
$hdrs .= "Subject: PEAR::Mail_Mime test mail\n";

require_once('Mail/mimeDecode.php');

$mime_message = "$hdrs\n$body";
$Decoder = new Mail_mimeDecode($mime_message);
$params = array(
    'include_bodies' => TRUE,
    'decode_bodies'  => TRUE,
    'decode_headers' => TRUE
);
$Decoded = $Decoder->decode($params);
$decodedParts = $Decoded->parts[1]->ctype_parameters;
//Bug #4057: Content-type params now have a name attribute.
unset($decodedParts['name']);
print_r($decodedParts);
?>
--EXPECT--
Array
(
    [testparam1] => test1;semicolon
    [testparam2] => two
    [testparam3] => three
    [testparam4] => four;4;four
    [testparam5] => five;5;five
    [testparam6] => six
    [testparam7] => seven;7
    [testparam8] => eight;8
)
