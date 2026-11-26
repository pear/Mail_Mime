--TEST--
Bug #20273  Mail_mimePart::encodeHeader() and TAB character
--INI--
error_reporting=E_ALL
--FILE--
<?php
include("Mail/mimePart.php");

$refs = "<test@domain.tld>\t<test2@domain.tld>";
$mime = new Mail_mimePart();
echo $mime->encodeHeader('References', $refs);
?>
--EXPECT--
<test@domain.tld> <test2@domain.tld>
