--TEST--
Bug GH-41 Test body_file param does not trigger a warning (PHP 8.6 deprecation of is_a())
--INI--
error_reporting=E_ALL
--FILE--
<?php
include("Mail/mimePart.php");

file_put_contents(__DIR__ . '/file1.tmp', 'test');

$part = new Mail_mimePart('', ['body_file' => __DIR__ . '/file1.tmp']);

$encoded = $part->encode();
echo $encoded['body'];
?>
--CLEAN--
<?php
    @unlink(__DIR__ . '/file1.tmp');
?>
--EXPECT--
test
