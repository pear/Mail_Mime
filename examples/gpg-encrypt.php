<?php
/**
 * Encrypt an email with PGP/gnupg
 */
require_once 'Mail.php';
require_once 'Mail/mime.php' ;
require_once 'Crypt/GPG.php';

$mime = new Mail_mime(array('eol' => "\n"));
$hdrs = array(
    'From' => 'foo@example.org',
    'Subject' => 'An encrypted mail example'
);
$mime->setTXTBody('This text will be only readable by bar@example.org');

$gpg = new Crypt_GPG();
$gpg->addEncryptKey('bar@example.org');
$mime->setGPG($gpg);

$body = $mime->get();
$hdrs = $mime->headers($hdrs);

$mail = Mail::factory('mail');
//$mail->send('bar@example.org', $hdrs, $body);
?>
