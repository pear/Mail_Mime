--TEST--
Multi-test for headers encoding using base64 and quoted-printable
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // ignore E_STRICT
include("Mail/mime.php");
$mime = new Mail_mime();

$headers = array(
array('From', '<adresse@adresse.de>'),
array('From', 'adresse@adresse.de'),
array('From', 'Frank Do <adresse@adresse.de>'),
array('To', 'Frank Do <adresse@adresse.de>, James Clark <james@domain.com>'),
array('From', '"Frank Do" <adresse@adresse.de>'),
array('Cc', '"Frank Do" <adresse@adresse.de>, "James Clark" <james@domain.com>'),
array('Cc', ' <adresse@adresse.de>, "Kuśmiderski Jan Krzysztof Janusz Długa nazwa" <cris@domain.com>'),
array('From', '"adresse@adresse.de" <addresse@adresse>'),
array('From', 'adresse@adresse.de <addresse@adresse>'),
array('From', '"German Umlauts ���" <adresse@adresse.de>'),
array('Subject', 'German Umlauts ��� <adresse@adresse.de>'),
array('Subject', 'Short ASCII subject'),
array('Subject', 'Long ASCII subject - multiline space separated words - too long for one line'),
array('Subject', 'Short Unicode ż subject'),
array('Subject', 'Long Unicode subject - zażółć gęślą jaźń - too long for one line'),
array('References', '<hglvja$jg7$1@nemesis.news.neostrada.pl>  <4b2e87ac$1@news.home.net.pl> <hgm5b1$3a7$1@atlantis.news.neostrada.pl>'),
array('To', '"Frank Do" <adresse@adresse.de>,, "James Clark" <james@domain.com>'),
);

$i = 1;
foreach ($headers as $header) {
    $hdr = $mime->encodeHeader($header[0], $header[1], 'UTF-8', 'base64');
    printf("[%02d] %s: %s\n", $i, $header[0], $hdr);
    $hdr = $mime->encodeHeader($header[0], $header[1], 'UTF-8', 'quoted-printable');
    printf("[%02d] %s: %s\n", $i, $header[0], $hdr);
    $i++;
}
?>
--EXPECT--
[01] From: <adresse@adresse.de>
[01] From: <adresse@adresse.de>
[02] From: adresse@adresse.de
[02] From: adresse@adresse.de
[03] From: Frank Do <adresse@adresse.de>
[03] From: Frank Do <adresse@adresse.de>
[04] To: Frank Do <adresse@adresse.de>, James Clark <james@domain.com>
[04] To: Frank Do <adresse@adresse.de>, James Clark <james@domain.com>
[05] From: "Frank Do" <adresse@adresse.de>
[05] From: "Frank Do" <adresse@adresse.de>
[06] Cc: "Frank Do" <adresse@adresse.de>, "James Clark" <james@domain.com>
[06] Cc: "Frank Do" <adresse@adresse.de>, "James Clark" <james@domain.com>
[07] Cc: <adresse@adresse.de>, =?UTF-8?B?S3XFm21pZGVyc2tpIEphbiBLcnp5c3p0?=
 =?UTF-8?B?b2YgSmFudXN6IETFgnVnYSBuYXp3YQ==?= <cris@domain.com>
[07] Cc: <adresse@adresse.de>, =?UTF-8?Q?Ku=C5=9Bmiderski_Jan_Krzysztof_Janus?=
 =?UTF-8?Q?z_D=C5=82uga_nazwa?= <cris@domain.com>
[08] From: "adresse@adresse.de" <addresse@adresse>
[08] From: "adresse@adresse.de" <addresse@adresse>
[09] From: "adresse@adresse.de" <addresse@adresse>
[09] From: "adresse@adresse.de" <addresse@adresse>
[10] From: =?UTF-8?B?R2VybWFuIFVtbGF1dHMg9uT8?= <adresse@adresse.de>
[10] From: =?UTF-8?Q?German_Umlauts_=F6=E4=FC?= <adresse@adresse.de>
[11] Subject: =?UTF-8?B?R2VybWFuIFVtbGF1dHMg9uT8IDxhZHJlc3NlQGFkcmVzc2UuZGU+?=
[11] Subject: =?UTF-8?Q?German_Umlauts_=F6=E4=FC_<adresse@adresse.de>?=
[12] Subject: Short ASCII subject
[12] Subject: Short ASCII subject
[13] Subject: Long ASCII subject - multiline space separated words - too long for
 one line
[13] Subject: Long ASCII subject - multiline space separated words - too long for
 one line
[14] Subject: =?UTF-8?B?U2hvcnQgVW5pY29kZSDFvCBzdWJqZWN0?=
[14] Subject: =?UTF-8?Q?Short_Unicode_=C5=BC_subject?=
[15] Subject: =?UTF-8?B?TG9uZyBVbmljb2RlIHN1YmplY3QgLSB6YcW8w7PFgsSHIGfEmcWb?=
 =?UTF-8?B?bMSFIGphxbrFhCAtIHRvbyBsb25nIGZvciBvbmUgbGluZQ==?=
[15] Subject: =?UTF-8?Q?Long_Unicode_subject_-_za=C5=BC=C3=B3=C5=82=C4=87_g?=
 =?UTF-8?Q?=C4=99=C5=9Bl=C4=85_ja=C5=BA=C5=84_-_too_long_for_one_line?=
[16] References: <hglvja$jg7$1@nemesis.news.neostrada.pl>
 <4b2e87ac$1@news.home.net.pl> <hgm5b1$3a7$1@atlantis.news.neostrada.pl>
[16] References: <hglvja$jg7$1@nemesis.news.neostrada.pl>
 <4b2e87ac$1@news.home.net.pl> <hgm5b1$3a7$1@atlantis.news.neostrada.pl>
[17] To: "Frank Do" <adresse@adresse.de>, "James Clark" <james@domain.com>
[17] To: "Frank Do" <adresse@adresse.de>, "James Clark" <james@domain.com>
