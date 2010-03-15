--TEST--
Bug #17226  Mail_mimePart::encodeHeader is not RFC2047 full compliant
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // suppress E_STRICT errors

require_once 'Mail/mimePart.php';
$head_name = 'From';
$head_value = 'test@nàme <user@domain.com>';

$mmp = new Mail_mimePart();
$encoded_header = $mmp->encodeHeader($head_name, $head_value);
print $encoded_header; 
?>
--EXPECT--
=?ISO-8859-1?Q?test=40n=C3=A0me?= <user@domain.com>
