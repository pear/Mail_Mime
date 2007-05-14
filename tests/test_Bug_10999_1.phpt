--TEST--
Bug #10999   Bad Content-ID(cid) format
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // ignore E_STRICT
$from='user@from.example.com';

require_once('Mail.php');
require_once('Mail/mime.php');

$mime=new Mail_mime();

$body='<img src="test.gif"/>';

$mime->setHTMLBody($body);
$mime->setFrom($from);
$mime->addHTMLImage('','image/gif', 'test.gif', false);
$msg=$mime->get();

$header = preg_match('|Content-ID: <[0-9a-fA-F]+@from.example.com>|', $msg);
if (!$header){
    print("FAIL:\n");
    print($msg);
}else{
    print("OK");
}
--EXPECT--
OK
