<?php
error_reporting(E_ALL);
 
include "../mimeDecode.php";


$mime_obj = new Mail_mimeDecode("From -  Tue Nov 28 23:42:23 2006
Someheader: somejunk data

body
");

print_r($mime_obj->decode(array(
 
    'include_bodies' => true,
    'decode_bodies'   => true,
    'decode_headers'  => true,
)));


