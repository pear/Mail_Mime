--TEST--
Bug #30     Mail_Mime: _encodeHeaders is not RFC-2047 compliant. (UTF-8)
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // ignore E_STRICT
include("Mail/mime.php");
include("Mail/mimeDecode.php");
$encoder = new Mail_mime();
$encoder->_build_params['ignore-iconv'] = true;

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

$encoded = $encoder->_encodeHeaders($input, array('head_encoding' => 'base64', 'head_charset' => 'utf-8'));
print_r($encoded);
?>
--EXPECT--
Array
(
    [0] => Just a simple test
    [1] => =?utf-8?B?VVRGLTggdGVzdCBmb3IgYnVnICMzMC4gIzIgc28gSGVsZ2kgw55vcm1h?=
 =?utf-8?B?ciDDnm9yYmrDtnJuc3NvbiA8ZHVmdXpAcGhwLm5ldD4gZG9lc24ndCBjb21w?=
 =?utf-8?B?bGFpbg==?=
    [2] => Just a simple test
    [3] => _this=?Q?U:I:T:E_a_test?=
    [4] => =?utf-8?B?Xz0/U8O8cGVyPz1f?=
    [5] => =?utf-8?B?XyA9ID8gU8O8cGVyID8gPSBf?=
    [6] => =?utf-8?B?U8O8cGVyIGdyw7ZzZSB0b2xsZSBncsO8w59lPyEgRnVyIG1pciE/?=
    [7] => =?utf-8?B?U8O8cGVyID0gZ3LDtnNlIHRvbGxlIGdyw7zDn2Ugdm9uIG1pcg==?=
    [8] => =?utf-8?B?VEVTVCAgU8O8cGVyIGdyw7ZzZSB0b2xsZSBncsO8w59lIHZvbiBtaXIg?=
 =?utf-8?B?U8O8cGVyIGdyw7ZzZSB0b2xsZSBncsO8w59lIHZvbiBtaXIgU8O8cGVyIGdy?=
 =?utf-8?B?w7ZzZSB0b2xsZSBncsO8w59lIHZvbiBtaXIhISE/?=
    [9] => =?utf-8?B?Ikdlcm1hbiBVbWxhdXRzIMO2w6TDvCI=?=
)
