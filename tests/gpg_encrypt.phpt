--TEST--
Request #20938: Encrypting mail with GPG
--SKIPIF--
<?php
include 'Crypt/GPG.php';
if (!class_exists('Crypt_GPG')) {
   echo "skip Crypt_GPG not available\n";
}
?>
--FILE--
<?php
require_once 'Mail/mime.php';
require_once 'Crypt/GPG.php';

$mime = new Mail_mime(
    array(
        'eol' => "\n",
        'boundary_gpg' => '=_unittest-gpg',
        'boundary' => '=_unittest-main'
    )
);
$hdrs = array(
    'From'    => 'foo@example.org',
    'Subject' => 'PGP Test'
);
$mime->setTXTBody("txtbody");
$mime->setHTMLBody('<h1>foo</h1>');

$gpg = new Crypt_GPG(
     array('homedir' => __DIR__ . '/gpg-keychain')
);
$gpg->addEncryptKey('first-keypair@example.com');
$mime->setGPG($gpg);

$message = $mime->getMessage();

echo "E-mail:\n----\n";
echo $message . "\n";

echo "----\nDecrypted e-mail content:\n";

preg_match(
    '#-----BEGIN PGP MESSAGE-----.*-----END PGP MESSAGE-----#s',
    $message,
    $matches
);
$encryptedData = $matches[0];

$gpg->addDecryptKey('first-keypair@example.com', 'test1');
echo str_replace("\r\n", "\n", $gpg->decrypt($encryptedData)) . "\n";
echo "done\n";
?>
--EXPECTF--
E-mail:
----
MIME-Version: 1.0
Content-Type: multipart/encrypted; protocol="application/pgp-encrypted";
 boundary="=_unittest-gpg"

--=_unittest-gpg
Content-Type: application/pgp-encrypted

Version: 1

--=_unittest-gpg
Content-Type: application/octet-stream

-----BEGIN PGP MESSAGE-----
%s
-----END PGP MESSAGE-----

--=_unittest-gpg--

----
Decrypted e-mail content:
Content-Type: multipart/alternative;
 boundary="=_unittest-main"

--=_unittest-main
Content-Transfer-Encoding: quoted-printable
Content-Type: text/plain; charset=ISO-8859-1

txtbody
--=_unittest-main
Content-Transfer-Encoding: quoted-printable
Content-Type: text/html; charset=ISO-8859-1

<h1>foo</h1>
--=_unittest-main--

done