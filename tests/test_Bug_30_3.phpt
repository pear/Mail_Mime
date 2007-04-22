--TEST--
Bug #30     Mail_Mime: _encodeHeaders is not RFC-2047 compliant. (ISO-8859-1, base64 encoding)
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // ignore E_STRICT
include("Mail/mime.php");
include("Mail/mimeDecode.php");
$encoder = new Mail_mime();

$encoder->_build_params['ignore-iconv'] = true;

$input[] = "Just a simple test";
$input[] = "_this=?Q?U:I:T:E_a_test?=";
$input[] = "_=?Süper?=_";
$input[] = "_ = ? Süper ? = _";
$input[] = "Süper gröse tolle grüße?! Fur mir!?";
$input[] = "Süper = gröse tolle grüße von mir";
$input[] = '"German Umlauts öäü"';
$input[] = "TEST  Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir!!!?";

$encoded = $encoder->_encodeHeaders($input, array('head_encoding' => 'base64'));

print_r($encoded);
?>
--EXPECT--
Array
(
    [0] => Just a simple test
    [1] => _this=?Q?U:I:T:E_a_test?=
    [2] => =?ISO-8859-1?B?Xz0/U/xwZXI/PV8=?=
    [3] => =?ISO-8859-1?B?XyA9ID8gU/xwZXIgPyA9IF8=?=
    [4] => =?ISO-8859-1?B?U/xwZXIgZ3L2c2UgdG9sbGUgZ3L832U/ISBGdXIgbWlyIT8=?=
    [5] => =?ISO-8859-1?B?U/xwZXIgPSBncvZzZSB0b2xsZSBncvzfZSB2b24gbWly?=
    [6] => =?ISO-8859-1?B?Ikdlcm1hbiBVbWxhdXRzIPbk/CI=?=
    [7] => =?ISO-8859-1?B?VEVTVCAgU/xwZXIgZ3L2c2UgdG9sbGUgZ3L832Ugdm9uIG1pciBT?=
 =?ISO-8859-1?B?/HBlciBncvZzZSB0b2xsZSBncvzfZSB2b24gbWlyIFP8cGVyIGdy9nNl?=
 =?ISO-8859-1?B?IHRvbGxlIGdy/N9lIHZvbiBtaXIhISE/?=
)
