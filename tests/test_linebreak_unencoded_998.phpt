--TEST--
Test that 7bit/8bit (unencoded) bodies are wrapped at 998 chars per RFC 5322
--INI--
error_reporting=E_ALL
--FILE--
<?php

include("Mail/mimePart.php");

// A single logical line well over 998 chars, with spaces so wordwrap() has
// somewhere to break it.
$word = str_repeat('a', 50);
$text = implode(' ', array_fill(0, 25, $word));

$params = array(
    'content_type' => 'text/plain',
    'encoding'     => '7bit',
);
$mimePart = new Mail_mimePart($text, $params);
$encoded  = $mimePart->encode();
$lines    = explode("\r\n", $encoded['body']);

$linesOk = true;
foreach ($lines as $line) {
    if (strlen($line) > 998) {
        $linesOk = false;
    }
}

// wordwrap() only replaces the odd space with the eol sequence at each break
// point, so collapsing eols back to single spaces should reproduce the
// original text (modulo whitespace run-length, which the regexp handles).
$rejoined   = str_replace("\r\n", ' ', $encoded['body']);
$contentOk  = (preg_replace('/ +/', ' ', $rejoined) === $text);

echo $linesOk  ? "All lines <= 998 chars: PASS\n" : "All lines <= 998 chars: FAIL\n";
echo $contentOk ? "Content preserved: PASS\n" : "Content preserved: FAIL\n";

// A short 7bit body should pass through completely unchanged.
$short     = "hello world";
$mimePart2 = new Mail_mimePart($short, $params);
$encoded2  = $mimePart2->encode();
echo ($encoded2['body'] === $short) ? "Short body unchanged: PASS\n" : "Short body unchanged: FAIL\n";

// quoted-printable already wraps at 76 chars during encoding; make sure
// that's still true (i.e. this fix didn't touch that path).
$qpParams = array(
    'content_type' => 'text/plain',
    'encoding'     => 'quoted-printable',
);
$mimePart3 = new Mail_mimePart($text, $qpParams);
$encoded3  = $mimePart3->encode();
$qpLinesOk = true;
foreach (explode("\r\n", $encoded3['body']) as $line) {
    if (strlen($line) > 76) {
        $qpLinesOk = false;
    }
}
echo $qpLinesOk ? "quoted-printable still wraps at 76 chars: PASS\n" : "quoted-printable still wraps at 76 chars: FAIL\n";

// Multi-line LF-delimited 7bit data > 998 bytes total whose individual lines
// are <= 998 chars (e.g. GnuPG ASCII-armored output on Unix when eol is CRLF)
// must not be wrapped at spaces inside headers/footers.
$pgp = "-----BEGIN PGP MESSAGE-----\n\n"
    . chunk_split(str_repeat('A', 1200), 64, "\n")
    . "=abcd\n-----END PGP MESSAGE-----\n";
$mimePart4 = new Mail_mimePart($pgp, $params);
$encoded4  = $mimePart4->encode();
echo ($encoded4['body'] === $pgp) ? "LF-delimited PGP armor unchanged: PASS\n" : "LF-delimited PGP armor unchanged: FAIL\n";

// Multi-line LF-delimited text where one line exceeds 998 chars: only the
// over-long line is wrapped while surrounding LF lines stay untouched.
$mixed     = "header line\n" . $text . "\nfooter line\n";
$mimePart5 = new Mail_mimePart($mixed, $params);
$encoded5  = $mimePart5->encode();
$expected5 = "header line\n" . $encoded['body'] . "\nfooter line\n";
echo ($encoded5['body'] === $expected5) ? "Mixed LF short and long lines: PASS\n" : "Mixed LF short and long lines: FAIL\n";
?>
--EXPECT--
All lines <= 998 chars: PASS
Content preserved: PASS
Short body unchanged: PASS
quoted-printable still wraps at 76 chars: PASS
LF-delimited PGP armor unchanged: PASS
Mixed LF short and long lines: PASS
