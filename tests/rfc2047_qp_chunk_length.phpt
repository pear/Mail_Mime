--TEST--
RFC 2047: quoted-printable chunks must not run past the encoded-word limit
--INI--
disable_functions=mb_substr,mb_strlen
--FILE--
<?php
include("Mail/mime.php");

// encodeHeaderValue() cuts quoted-printable text with
// "(.{0,$maxLength}[^\=][^\=])". The two trailing atoms match one character
// each, so a chunk could run two characters past $maxLength, producing
// encoded-words of up to 77 characters and header lines of up to 78.
//
// mbstring is disabled above because encodeMB() splits correctly; the defect
// is in the encodeHeaderValue() fallback that runs when mbstring is missing.

// "Lorem ipsum ... magna aliqua" plus "…" (U+2026, 3 bytes in UTF-8). The
// ellipsis is what makes the value non-ASCII; a pure ASCII value is emitted
// verbatim, never reaches the RFC 2047 encoder, and is folded at RFC 5322's
// 78 characters instead
$text     = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod '
    . 'tempor incididunt ut labore et dolore magna aliqua';
$ellipsis = "\xE2\x80\xA6";
$value    = $text . $ellipsis;

// RFC 2047: "An 'encoded-word' may not be more than 75 characters long" and
// "each line of a header field that contains one or more 'encoded-word's is
// limited to 76 characters".
function check_limits($name, $value, $encoding)
{
    $header = $name . ': '
        . Mail_mimePart::encodeHeader($name, $value, 'UTF-8', $encoding);

    $errors = array();

    foreach (explode("\r\n", $header) as $line) {
        if (strlen($line) > 76) {
            $errors[] = sprintf('line of %d chars: %s', strlen($line), $line);
        }
    }

    preg_match_all('/=\?[^?]+\?[QB]\?[^?]*\?=/', $header, $matches);
    foreach ($matches[0] as $word) {
        if (strlen($word) > 75) {
            $errors[] = sprintf('encoded-word of %d chars: %s', strlen($word), $word);
        }
    }

    return $errors;
}

// Sweep the cut point across the value: every truncation has to stay inside
// the limits, whichever character the chunk boundary lands on. The ellipsis
// is kept on every case so the value is always non-ASCII
$failed = 0;

foreach (array('quoted-printable', 'base64') as $encoding) {
    for ($n = 0; $n <= strlen($text); $n++) {
        $errors = check_limits('Subject', substr($text, 0, $n) . $ellipsis, $encoding);
        foreach ($errors as $error) {
            printf('FAIL [%s n=%d]: %s' . PHP_EOL, $encoding, $n, $error);
            $failed++;
        }
    }
}

printf('failures: %d' . PHP_EOL, $failed);

// Used to be cut two characters late, giving a 77-character encoded-word
echo Mail_mimePart::encodeHeaderValue($value, 'UTF-8', 'quoted-printable', 9) . PHP_EOL;
?>
--EXPECT--
failures: 0
=?UTF-8?Q?Lorem_ipsum_dolor_sit_amet=2C_consectetur_adipiscing_e?=
 =?UTF-8?Q?lit=2C_sed_do_eiusmod_tempor_incididunt_ut_labore_et_dolore_mag?=
 =?UTF-8?Q?na_aliqua=E2=80=A6?=
