--TEST--
Bug GH-26  Backward slash is getting added in headers
--SKIPIF--
--FILE--
<?php

require_once('Mail/mime.php');

$mail_mime = new Mail_mime("\n");

$from = '"George B@@Z"<george@cort.org.au>';
$to = <<<EOT
"austin test"<austinn@cort.org>,<reno@cort.org>,t@mail.com
EOT;
$subject = "Test mime";
$mailbody = "hello world";

$mail_mime->setTxtBody($mailbody);
$mail_mime->setHTMLBody($mailbody);
$mail_mime->setSubject($subject);
$mail_mime->setFrom($from);

$body = $mail_mime->get();

$extra_headers = array();
$extra_headers["To"] = $to;

$arr_hdrs = $mail_mime->headers($extra_headers);

echo $arr_hdrs['From'] . "\n" . $arr_hdrs['To'];

--EXPECT--
"George B@@Z" <george@cort.org.au>
"austin test" <austinn@cort.org>, <reno@cort.org>, t@mail.com