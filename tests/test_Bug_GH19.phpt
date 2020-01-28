--TEST--
Bug GH-19  Test boundary value with different headers()/get() call order
--SKIPIF--
--FILE--
<?php
include "Mail/mime.php";

// Test get() before headers()

$mime = new Mail_mime("\r\n");
$mime->setHTMLBody('html');
$mime->setTXTBody('text');

$body = $mime->get();
$hdrs = $mime->headers(array(
        'From'    => 'test@domain.tld',
        'Subject' => 'Subject',
        'To'      => 'to@domain.tld'
));

preg_match('/boundary="([^"]+)/', $hdrs['Content-Type'], $matches);
$boundary = $matches[1];

echo substr_count($body, "--$boundary") . "\n";

// Test headers() before get()

$mime = new Mail_mime("\r\n");
$mime->setHTMLBody('html');
$mime->setTXTBody('text');

$hdrs = $mime->headers(array(
        'From'    => 'test@domain.tld',
        'Subject' => 'Subject',
        'To'      => 'to@domain.tld'
));
$body = $mime->get();

preg_match('/boundary="([^"]+)/', $hdrs['Content-Type'], $matches);
$boundary = $matches[1];

echo substr_count($body, "--$boundary");
--EXPECT--
3
3
