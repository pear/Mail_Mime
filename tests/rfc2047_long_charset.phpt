--TEST--
RFC 2047: an over-long charset name must not hang encodeHeaderValue()
--INI--
disable_functions=mb_substr,mb_strlen
memory_limit=64M
--FILE--
<?php
include("Mail/mime.php");

// encodeHeaderValue() splits the value into chunks of
// 75 - strlen("=?<charset>?B?" . "?=") characters. That size was never
// clamped, so a charset name of 65 characters or more drove it to zero:
// substr($value, 0, 0) returned '' and substr($value, 0) returned the value
// unchanged, so the loop appended an encoded-word prefix forever.
//
// mbstring is disabled above because encodeMB() would otherwise handle the
// value and the splitting below would never run. The memory_limit turns a
// regression into a failing test rather than a hung one.

// Some non-ASCII value: "söme välue héré"
// written as escapes so the test does not depend on this file's encoding.
$value = "s\xC3\xB6me v\xC3\xA4lue h\xC3\xA9r\xC3\xA9";

$calls = 0;
$empty = 0;

foreach (array('base64', 'quoted-printable') as $encoding) {
    foreach (array(1, 20, 48, 49, 64, 65, 70, 200) as $charset_len) {
        foreach (array(0, 9, 70, 200) as $prefix_len) {
            $encoded = Mail_mimePart::encodeHeaderValue(
                $value, str_repeat('X', $charset_len), $encoding, $prefix_len
            );

            $calls++;

            // Reaching this point at all is the assertion: before the fix the
            // 65, 70 and 200 cases never returned
            if (!strlen($encoded)) {
                printf(
                    'FAIL [%s charset=%d prefix=%d]: empty result' . PHP_EOL,
                    $encoding, $charset_len, $prefix_len
                );
                $empty++;
            }
        }
    }
}

printf('returned from %d calls, %d empty' . PHP_EOL, $calls, $empty);

// The charset is capped at 48 characters so a chunk always fits.
// This case drives encodeHeaderValue() directly because $prefix_len
// cannot be reached through Mail_mime
echo Mail_mimePart::encodeHeaderValue($value, str_repeat('X', 65), 'base64', 9) . PHP_EOL;

// This last case comes in through the public API instead,
// which is how an application would hit the defect.
$mime = new Mail_mime(array(
    'eol'           => "\r\n",
    'head_charset'  => str_repeat('X', 65),
    'head_encoding' => 'base64',
));

$mime->setTXTBody('body');
$mime->setSubject($value);
$mime->get();

$headers = $mime->headers();

echo $headers['Subject'] . PHP_EOL;
?>
--EXPECT--
returned from 64 calls, 0 empty
=?XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX?B?c8O2bWUg?=
 =?XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX?B?dsOkbHVlIGjDqXLDqQ==?=
=?XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX?B?c8O2bWUg?=
 =?XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX?B?dsOkbHVlIGjDqXLDqQ==?=
