--TEST--
Bug GH-32  RFC 2047 param folding must not loop forever when a value cannot be folded
--SKIPIF--
--FILE--
<?php
include "Mail/mime.php";

// An over-long charset name once made buildRFC2047Param() loop until memory
// was exhausted; the charset is now capped so encoded-words stay within 75 chars.
$charset = str_repeat('X', 70);
$filename = "\xC3\xA9\xC3\xA9\xC3\xA9\xC3\xA9\xC3\xA9.pdf"; // "ééééé.pdf"

foreach (array('quoted-printable', 'base64') as $enc) {
    $mime = new Mail_mime("\r\n");
    $mime->setTXTBody('hello');
    $mime->addAttachment(
        'data', 'application/pdf', $filename, false, 'base64', 'attachment',
        $charset, '', '', $enc, $enc, '', $charset
    );

    // Must return instead of hanging / exhausting memory.
    $msg = $mime->get();

    echo "--- {$enc} ---\n";
    var_dump(is_string($msg));
    echo strpos($msg, 'name="=?') !== false ? "name encoded\n" : "name NOT encoded\n";

    // RFC 2047: no encoded-word may exceed 75 characters.
    preg_match_all('/=\?[^?]+\?[QB]\?[^?]*\?=/', $msg, $words);
    $tooLong = array();
    foreach ($words[0] as $w) {
        if (strlen($w) > 75) {
            $tooLong[] = strlen($w);
        }
    }
    echo empty($tooLong)
        ? "all encoded-words <= 75\n"
        : "over-length encoded-words: " . implode(',', $tooLong) . "\n";
}
--EXPECT--
--- quoted-printable ---
bool(true)
name encoded
all encoded-words <= 75
--- base64 ---
bool(true)
name encoded
all encoded-words <= 75
