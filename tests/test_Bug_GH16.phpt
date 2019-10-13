--TEST--
Bug GH-16  Test methods that write to a file
--SKIPIF--
--FILE--
<?php
include "Mail/mime.php";

$mime = new Mail_mime("\r\n");
$mime->setHTMLBody('html');
$mime->setTXTBody('text');
$mime->setContentType('multipart/alternative', array('boundary' => 'something'));

$temp_filename = __DIR__ . "/output1.tmp";
touch($temp_filename);
$msg = $mime->saveMessageBody($temp_filename);
echo file_get_contents($temp_filename);

$temp_filename = __DIR__ . "/output2.tmp";
touch($temp_filename);
$msg = $mime->saveMessage($temp_filename);
echo file_get_contents($temp_filename);

$temp_filename = __DIR__ . "/output3.tmp";
$mimePart = new Mail_mimePart('abc', array(
        'content_type' => 'text/plain',
        'encoding'     => 'quoted-printable',
));
$mimePart->encodeToFile($temp_filename);
echo file_get_contents($temp_filename);

?>
--CLEAN--
<?php
    for ($i = 1; $i < 4; $i++) {
        @unlink(__DIR__ . "/output{$i}.tmp");
    }
?>
--EXPECT--
--something
Content-Transfer-Encoding: quoted-printable
Content-Type: text/plain; charset=ISO-8859-1

text
--something
Content-Transfer-Encoding: quoted-printable
Content-Type: text/html; charset=ISO-8859-1

html
--something--
MIME-Version: 1.0
Content-Type: multipart/alternative;
 boundary="something"

--something
Content-Transfer-Encoding: quoted-printable
Content-Type: text/plain; charset=ISO-8859-1

text
--something
Content-Transfer-Encoding: quoted-printable
Content-Type: text/html; charset=ISO-8859-1

html
--something--
Content-Transfer-Encoding: quoted-printable
Content-Type: text/plain

abc
