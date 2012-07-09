--TEST--
Bug #19497  Attachment filenames with a slash character
--SKIPIF--
--FILE--
<?php
include "Mail/mime.php";
$m = new Mail_mime();

$filename = "test/file.txt";
$m->addAttachment('testfile', "text/plain", $filename, FALSE,
    'base64', 'attachment', 'ISO-8859-1', '', '', 'quoted-printable', 'base64');

$root = $m->_addMixedPart();
$enc = $m->_addAttachmentPart($root, $m->_parts[0]);

echo $enc->_headers['Content-Type'];
echo "\n";
echo $enc->_headers['Content-Disposition'];
?>
--EXPECT--
text/plain; charset=ISO-8859-1;
 name="test/file.txt"
attachment;
 filename="test/file.txt";
 size=8
