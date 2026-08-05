--TEST--
RFC 2047: control characters must not appear raw inside an encoded-word
--FILE--
<?php
include("Mail/mimePart.php");

// RFC 2047: encoded-text = 1*<Any printable ASCII character other than "?" or SPACE>
//
// The "Q" encoder escaped \x22-\x29, \x2C, \x2E, \x3A-\x40, \x5B-\x60, \x7B-\x7E
// and \x80-\xFF, but neither \x00-\x1F (the C0 control characters) nor \x7F
// (DEL). Those were emitted into the header verbatim, which is illegal, and for
// CR or LF is a header injection vector. A raw LF was worse still with
// ext/mbstring: encodeMB() uses "\n" as its own chunk separator, so the byte was
// silently swallowed and turned into a fold.
//
// This reproduces with ext/mbstring present, so no --INI-- section is needed.

function decode_word($matches)
{
    if ($matches[1] == 'B') {
        return base64_decode($matches[2]);
    }

    return quoted_printable_decode(str_replace('_', ' ', $matches[2]));
}

// Adjacent encoded-words split by folding whitespace decode without that whitespace
function decode_words($value)
{
    $value = preg_replace('/\?=\r\n =\?/', '?==?', $value);

    return preg_replace_callback('/=\?[^?]+\?([QB])\?([^?]*)\?=/', 'decode_word', $value);
}

$failed = 0;

// Every C0 control character, plus DEL. The "ä" (\xC3\xA4 in UTF-8) is only
// there to make the value non-ASCII: a pure ASCII subject is emitted verbatim
// and never reaches the RFC 2047 encoder at all.
$bytes   = range(0x00, 0x1F);
$bytes[] = 0x7F;

foreach (array('quoted-printable', 'base64') as $encoding) {
    foreach ($bytes as $byte) {
        $subject = "a\xC3\xA4" . chr($byte) . 'b';
        $encoded = Mail_mimePart::encodeHeader('Subject', $subject, 'UTF-8', $encoding);

        // The encoded-text may only hold printable ASCII other than "?" and SPACE
        preg_match_all('/=\?[^?]+\?[QB]\?([^?]*)\?=/', $encoded, $matches);
        foreach ($matches[1] as $text) {
            if (preg_match('/[^\x21-\x3E\x40-\x7E]/', $text, $m)) {
                printf(
                    'FAIL [%s 0x%02X]: raw 0x%02X inside encoded-text' . PHP_EOL,
                    $encoding, $byte, ord($m[0])
                );
                $failed++;
            }
        }

        // ... and the byte must survive the round trip rather than be dropped
        if (decode_words($encoded) !== $subject) {
            printf(
                'FAIL [%s 0x%02X]: does not decode back to the subject' . PHP_EOL,
                $encoding, $byte
            );
            $failed++;
        }
    }
}

printf('failures: %d' . PHP_EOL, $failed);

// Every one of them at once: "a" + "ä" + TAB + LF + CR + NUL + DEL + "b"
foreach (array('quoted-printable', 'base64') as $encoding) {
    echo Mail_mimePart::encodeHeader(
        'Subject', "a\xC3\xA4\t\n\r\x00\x7Fb", 'UTF-8', $encoding
    ) . PHP_EOL;
}
?>
--EXPECT--
failures: 0
=?UTF-8?Q?a=C3=A4=09=0A=0D=00=7Fb?=
=?UTF-8?B?YcOkCQoNAH9i?=
