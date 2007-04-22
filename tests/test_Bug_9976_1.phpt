--TEST--
Bug #9976   Subject encoded twice
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // ignore E_STRICT
include("Mail/mime.php");

$mime = new Mail_mime();
$mime->_build_params['ignore-iconv'] = true;
$body = $mime->get(array('text_charset'=>'UTF-8',
                         'text_encoding'=>'8bit',
                         'head_charset'=>'UTF-8'
                        )
                  ); 
$hdrs = $mime->headers(array('Subject'=>'Nový Nový!')); 

print($hdrs['Subject']);
--EXPECT--
=?UTF-8?Q?Nov=C3=BD_Nov=C3=BD!?=
