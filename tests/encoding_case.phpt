--TEST--
tabs in _quotedPrintableEncode() (bug #2364)
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // ignore E_STRICT
define('MAIL_MIMEPART_CRLF', "\r\n");
$test = "Here's\t\na tab\n";
require_once('Mail/mimePart.php');
print Mail_mimePart::_quotedPrintableEncode($test, 7);
?>
--EXPECT--
Here's=
=09
a tab
