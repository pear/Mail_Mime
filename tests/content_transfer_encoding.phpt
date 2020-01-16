--TEST--
Test empty Content-Transfer-Encoding on multipart messages
--SKIPIF--
--FILE--
<?php
include "Mail/mime.php";
$mime = new Mail_mime("\r\n");
$mime->setParam('text_encoding', 'quoted-printable');
$mime->setParam('html_encoding', 'quoted-printable');
$mime->setParam('head_encoding', 'quoted-printable');

// This specific order used to set Content-Transfer-Encoding: quoted-printable
// which is invalid according to RFC 2045 on multipart messages
$mime->setTXTBody('text');
$mime->headers(array('From' => 'from@domain.tld'));
$mime->addAttachment('file.pdf', 'application/pdf', 'file.pdf', false, 'base64', 'inline');
echo $mime->txtHeaders();
list ($header, $body) = explode("\r\n\r\n", $mime->getMessage());
echo $header;
?>
--EXPECTF--
MIME-Version: 1.0
From: from@domain.tld
Content-Type: multipart/mixed;
 boundary="=_%x"
MIME-Version: 1.0
From: from@domain.tld
Content-Type: multipart/mixed;
 boundary="=_%x"
