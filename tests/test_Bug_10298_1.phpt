--TEST--
Bug #10298  Mail_mime, double Quotes and Specialchars in from and to Adress
    Bug #10306     Strings with Double Quotes get encoded wrongly.
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // ignore E_STRICT
include("Mail/mime.php");
$mime = new Mail_mime();

$string = '"German Umlauts צהü" <adresse@adresse.de>';

$hdrs = $mime->_encodeHeaders(array('From'=>$string));

print($hdrs['From']);
--EXPECT--
=?ISO-8859-1?Q?German_Umlauts_=F6=E4=FC?= <adresse@adresse.de>
