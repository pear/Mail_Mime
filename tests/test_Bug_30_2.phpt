--TEST--
Bug #30     _encodeHeaders is not RFC-2047 compliant. (UTF-8)
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // ignore E_STRICT
include("Mail/mime.php");
$encoder = new Mail_mime();

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

$encoded = $encoder->_encodeHeaders($input, array('head_encoding' => 'quoted-printable', 'head_charset' => 'utf-8'));
print_r($encoded);
?>
--EXPECT--
Array
(
    [0] => Just a simple test
    [1] => =?utf-8?Q?UTF-8_test_for_bug_=2330=2E_=232_so_Helgi_=C3=9Eormar_=C3?=
 =?utf-8?Q?=9Eorbj=C3=B6rnsson_=3Cdufuz=40php=2Enet=3E_doesn=27t_complai?=
 =?utf-8?Q?n?=
    [2] => Just a simple test
    [3] => _this=?Q?U:I:T:E_a_test?=
    [4] => =?utf-8?Q?=5F=3D=3FS=C3=BCper=3F=3D=5F?=
    [5] => =?utf-8?Q?=5F_=3D_=3F_S=C3=BCper_=3F_=3D_=5F?=
    [6] => =?utf-8?Q?S=C3=BCper_gr=C3=B6se_tolle_gr=C3=BC=C3=9Fe=3F!_Fur_mir!=3F?=
    [7] => =?utf-8?Q?S=C3=BCper_=3D_gr=C3=B6se_tolle_gr=C3=BC=C3=9Fe_von_mir?=
    [8] => =?utf-8?Q?TEST__S=C3=BCper_gr=C3=B6se_tolle_gr=C3=BC=C3=9Fe_von_mir_S?=
 =?utf-8?Q?=C3=BCper_gr=C3=B6se_tolle_gr=C3=BC=C3=9Fe_von_mir_S=C3=BCper?=
 =?utf-8?Q?_gr=C3=B6se_tolle_gr=C3=BC=C3=9Fe_von_mir!!!=3F?=
    [9] => =?utf-8?Q?German_Umlauts_=C3=B6=C3=A4=C3=BC?=
)
