--TEST--
Test class filename (bug #24)
--SKIPIF--
--FILE--
<?php
@include('Mail/Mime.php');
echo class_exists('Mail_Mime') ? 'Include OK' : 'Include failed';
?>
--EXPECT--
Include OK