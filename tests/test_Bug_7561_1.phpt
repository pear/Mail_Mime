--TEST--
Bug #7561   Mail_mimePart::_quotedPrintableEncode() misbehavior with mbstring overload
--SKIPIF--
<?php
include "PEAR.php";
if (!extension_loaded('mbstring')){
    if (!PEAR::loadExtension('mbstring')){
        print('SKIP could not load mbstring module');
    }
}
--FILE--
<?php
error_reporting(E_ALL); // ignore E_STRICT
ini_set('mbstring.language',            'Neutral');
ini_set('mbstring.func_overload',       6);
ini_set('mbstring.internal_encoding',   'UTF-8');
ini_set('mbstring.http_output',         'UTF-8');

define('MAIL_MIMEPART_CRLF', "\n");
include("Mail/mimePart.php");

// string is UTF-8 encoded
$input = "Micha\xC3\xABl \xC3\x89ric St\xC3\xA9phane";
$rv = Mail_mimePart::_quotedPrintableEncode($input);
echo $rv, "\n";
--EXPECT--
Micha=C3=ABl =C3=89ric St=C3=A9phane
