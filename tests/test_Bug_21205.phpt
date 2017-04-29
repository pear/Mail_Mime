--TEST--
foo
--SKIPIF--
--FILE--
<?php
require_once('Mail/mimePart.php');
$tests = [
    '□京都府□',
    '∠∠∠∠',
];
$addr = ' <aaa@bbb.ccc>';
$charset = 'ISO-2022-JP';
$encoding = 'base64';
foreach ($tests as $test) {
    $test = mb_convert_encoding($test, $charset);
    print Mail_mimePart::encodeHeader("subject", $test,       $charset, $encoding) . PHP_EOL;
    print Mail_mimePart::encodeHeader("to",      $test.$addr, $charset, $encoding) . PHP_EOL;
    $test = '"' . $test . '"';
    print Mail_mimePart::encodeHeader("subject", $test,       $charset, $encoding) . PHP_EOL;
    print Mail_mimePart::encodeHeader("to",      $test.$addr, $charset, $encoding) . PHP_EOL;
}
?>
--EXPECT--
=?ISO-2022-JP?B?GyRCIiI1fkVUSVwiIhsoQg==?=
=?ISO-2022-JP?B?GyRCIiI1fkVUSVwiIhsoQg==?= <aaa@bbb.ccc>
=?ISO-2022-JP?B?GyRCIiI1fkVUSVwiIhsoQg==?=
=?ISO-2022-JP?B?GyRCIiI1fkVUSVwiIhsoQg==?= <aaa@bbb.ccc>
=?ISO-2022-JP?B?GyRCIlwiXCJcIlwbKEI=?=
=?ISO-2022-JP?B?GyRCIlwiXCJcIlwbKEI=?= <aaa@bbb.ccc>
=?ISO-2022-JP?B?GyRCIlwiXCJcIlwbKEI=?=
=?ISO-2022-JP?B?GyRCIlwiXCJcIlwbKEI=?= <aaa@bbb.ccc>
