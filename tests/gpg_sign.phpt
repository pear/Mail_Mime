--TEST--
Request #20938: Signing mail with GPG
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
$gpg->addSignKey('no-passphrase@example.com');
$mime->setGPG($gpg);

$message = $mime->getMessage();

echo "E-mail:\n----\n" . $message . "\n----\n";

preg_match(
    '#--=_unittest-gpg(.*)--=_unittest-gpg#s',
    $message,
    $matches
);
$parts = explode("--=_unittest-gpg\n", $matches[0]);

//cut one newline at the end that does not belong to the data but the boundary
$data = substr($parts[1], 0, -1);
echo "----\nSigned data:\n" . $data . "\n----\n";

$signature = trim(
    str_replace('Content-Type: application/pgp-signature', '', trim($parts[2]))
);
echo "----\nSignature:\n" . $signature . "\n----\n";

list($gpgSignature) = $gpg->verify($data, $signature);
echo 'Signature is valid: ' . var_export($gpgSignature->isValid(), true) . "\n";
?>
--EXPECTF--
E-mail:
----
MIME-Version: 1.0
Content-Type: multipart/signed;protocol="application/pgp-signature";micalg=pgp-sha1;
 boundary="=_unittest-gpg"

--=_unittest-gpg
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

--=_unittest-gpg
Content-Type: application/pgp-signature

-----BEGIN PGP SIGNATURE-----
%s
-----END PGP SIGNATURE-----

--=_unittest-gpg--

----
----
Signed data:
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

----
----
Signature:
-----BEGIN PGP SIGNATURE-----
%s
-----END PGP SIGNATURE-----

--=_unittest-gpg
----
Signature is valid: true
