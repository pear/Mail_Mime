--TEST--
Quoted Semicolons in Content-Type (bug #1724)
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // suppress E_STRICT errors

require_once('Mail/mime.php');

$Mime = new Mail_Mime();
$Mime->setTXTBody('Test message.');
$Mime->addAttachment('test file contents', 'text/plain; testparam="test1;semicolon"', 'test.txt', FALSE);

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
$test_param = $Decoded->parts[1]->ctype_parameters['testparam'];

echo $test_param;

?>
--EXPECT--
test1;semicolon