--TEST--
Test for correct "." encoding when doing linebreaks
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // ignore E_STRICT
include("Mail/mime.php");
$text     = '0123456789012345678901234567890123456789012345678901234567890123456789012...6';
$params   = Array(
    'content_type' => 'text/plain',
    'encoding'     => 'quoted-printable',
);

for ($i=74; $i <= strlen($text); $i++) {
    $input = substr($text, 0, $i);
    $mimePart = new Mail_mimePart($input, $params);
    $encoded  =  $mimePart->encode();
    $output = $encoded['body'];
    printf("input: %02d: %s\n", strlen($input), $input);

    $lines = explode("\r\n", $output);
    for($j=0; $j < count($lines); $j++) {
        $line = $lines[$j];
        if ($j + 1 < count($lines)) {
            $line_vis = $line.'\r\n';
        } else {
            $line_vis = $line;
        }
        printf("output:%02d: %s\n", strlen($line), $line_vis);
    }

    print("---\n");

}
--EXPECT--
input: 74: 0123456789012345678901234567890123456789012345678901234567890123456789012.
output:74: 0123456789012345678901234567890123456789012345678901234567890123456789012.
---
input: 75: 0123456789012345678901234567890123456789012345678901234567890123456789012..
output:75: 0123456789012345678901234567890123456789012345678901234567890123456789012..
---
input: 76: 0123456789012345678901234567890123456789012345678901234567890123456789012...
output:76: 0123456789012345678901234567890123456789012345678901234567890123456789012...
---
input: 77: 0123456789012345678901234567890123456789012345678901234567890123456789012...6
output:76: 0123456789012345678901234567890123456789012345678901234567890123456789012..=\r\n
output:04: =2E6
---
