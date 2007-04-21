--TEST--
Bug #30     Mail_Mime: _encodeHeaders is not RFC-2047 compliant. (UTF-8)
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // ignore E_STRICT
include("Mail/mime.php");
include("Mail/mimeDecode.php");
$encoder = new Mail_mime();
$decoder = new Mail_mimeDecode("");

$input[] = "Just a simple test";
$input[] = "UTF-8 test for bug #30. #2 so Helgi Þormar Þorbjörnsson <dufuz@php.net> doesn't complain";
$input[] = "Just a simple test";
$input[] = "_this=?Q?U:I:T:E_a_test?=";
$input[] = "_=?Süper?=_";
$input[] = "_ = ? Süper ? = _";
$input[] = "Süper gröse tolle grüße?! Fur mir!?";
$input[] = "Süper = gröse tolle grüße von mir";
$input[] = "TEST  Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir!!!?";
$input[] = '"German Umlauts öäü"';

$encoded = $encoder->_encodeHeaders($input, array('head_encoding' => 'quoted-printable'));
$decoded = array();

foreach ($encoded as $encodedString){
    $decoded[] = $decoder->_decodeHeader($encodedString);
}
if ($input === $decoded){
    print("MATCH");
}else{
    print("FAIL");
}
?>
--EXPECT--
MATCH
