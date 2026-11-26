--TEST--
Bug #20563  isMultipart() method tests
--INI--
error_reporting=E_ALL
--FILE--
<?php
include("Mail/mime.php");

$mime = new Mail_mime;

echo ($mime->isMultipart() ? 'TRUE' : 'FALSE') . "\n";

$mime->setTXTBody('test');

echo ($mime->isMultipart() ? 'TRUE' : 'FALSE') . "\n";

$mime->setHTMLBody('test');

echo ($mime->isMultipart() ? 'TRUE' : 'FALSE') . "\n";

--EXPECT--
FALSE
FALSE
TRUE
