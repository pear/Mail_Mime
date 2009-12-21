--TEST--
Bug #11238  From address encoding
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // ignore E_STRICT
include "Mail/mime.php";
$m = new Mail_mime();
print_r($m->encodeRecipients("Testø Testå <me@example.com>"));
?>
--EXPECT--
=?ISO-8859-1?Q?Test=F8_Test=E5?= <me@example.com>
