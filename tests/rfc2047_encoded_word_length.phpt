--TEST--
RFC 2047 length limits: encoded-word <= 75 chars, header line <= 76 chars
--FILE--
<?php
include("Mail/mimePart.php");

// RFC 2047: "An 'encoded-word' may not be more than 75 characters long" and
// "each line of a header field that contains one or more 'encoded-word's is
// limited to 76 characters".
function check_limits($name, $value, $charset, $encoding)
{
    $header = $name . ': '
        . Mail_mimePart::encodeHeader($name, $value, $charset, $encoding);

    $errors = array();

    // Loop lines
    foreach (explode("\r\n", $header) as $line) {
        if (strlen($line) > 76) {
            $errors[] = sprintf('line of %d chars: %s', strlen($line), $line);
        }
    }

    // Loop words
    preg_match_all('/=\?[^?]+\?[QB]\?[^?]*\?=/', $header, $matches);
    foreach ($matches[0] as $word) {
        if (strlen($word) > 75) {
            $errors[] = sprintf('encoded-word of %d chars: %s', strlen($word), $word);
        }
    }

    // The encoded header must still decode back to the input
    $decoded = '';
    foreach (preg_split('/\r\n /', substr($header, strlen($name) + 2)) as $word) {
        $decoded .= base64_decode(
            preg_replace('/^=\?' . preg_quote($charset, '/') . '\?B\?(.*)\?=$/', '$1', $word)
        );
    }
    if ($decoded !== $value) {
        $errors[] = 'does not decode back to the input value';
    }

    return $errors;
}

// The last encoded-word used to be emitted without a length check, so these
// input lengths produced a 76-character encoded-word on a 77-character line
$failed = 0;

// Written as escapes so the test does not depend on this file's encoding:
// - "ä" (U+00E4, 2 bytes)
// - "字" (U+5B57, 3 bytes)
// - "😀" (U+1F600, 4 bytes)
foreach (array("\xC3\xA4", "\xE5\xAD\x97", "\xF0\x9F\x98\x80") as $char) {
    for ($n = 1; $n <= 120; $n++) {
        $errors = check_limits('Subject', str_repeat($char, $n), 'UTF-8', 'base64');
        foreach ($errors as $error) {
            printf('FAIL [%d x %s]: %s' . PHP_EOL, $n, bin2hex($char), $error);
            $failed++;
        }
    }
}

printf('failures: %d' . PHP_EOL, $failed);

// Used to be emitted as a single 76-character encoded-word on a 77-char line
echo 'Subject: ' . Mail_mimePart::encodeHeader(
    'Subject', str_repeat("\xE5\xAD\x97", 13) . "\xC3\xBC", 'UTF-8', 'base64'
) . PHP_EOL;
?>
--EXPECT--
failures: 0
Subject: =?UTF-8?B?5a2X5a2X5a2X5a2X5a2X5a2X5a2X5a2X5a2X5a2X5a2X5a2X5a2X?=
 =?UTF-8?B?w7w=?=
