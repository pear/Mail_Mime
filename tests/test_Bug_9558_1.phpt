--TEST--
Bug #9558   Broken multiline headers
--SKIPIF--
--FILE--
<?php
include("Mail/mime.php");

$encoder = new Mail_mime();
$input[] = "received by me
    at some point
    from some server";

$encoded = $encoder->_encodeHeaders($input, array('head_encoding' => 'quoted-printable'));
print_r($encoded);
--EXPECT--
Array
(
    [0] => received by me
    at some point
    from some server
)
