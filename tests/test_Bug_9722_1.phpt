--TEST--
Bug #9722   _quotedPrintableEncode does not encode dot at start of line on Windows platform
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // ignore E_STRICT
include("Mail/mimePart.php");
define('MAIL_MIMEPART_CRLF', "\n");
$text = "This
is a
test
...
    It is 
//really fun//
to make :(";

print_r(Mail_mimePart::_quotedPrintableEncode($text));

--EXPECT--
This
is a
test
=2E..
    It is=20
//really fun//
to make :(
