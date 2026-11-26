--TEST--
Bug #12165  Dot at the end of the line disappeared
--INI--
error_reporting=E_ALL
--FILE--
<?php
include ("Mail/mime.php");
$string='http://aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.com';
$mime = new Mail_mime();
$mime->setHTMLBody($string);
print_r($mime->get());
    
--EXPECT--
http://aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa=
=2Ecom
