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
array('Cc', ' <adresse@adresse.de>, "KuÅ›miderski Jan Krzysztof Janusz DÅ‚uga nazwa" <cris@domain.com>'),
array('From', '"adresse@adresse.de" <addresse@adresse>'),
array('From', 'adresse@adresse.de <addresse@adresse>'),
array('From', '"German Umlauts öäü" <adresse@adresse.de>'),
array('Subject', 'German Umlauts öäü <adresse@adresse.de>'),
array('Subject', 'Short ASCII subject'),
array('Subject', 'Long ASCII subject - multiline space separated words - too long for one line'),
array('Subject', 'Short Unicode Å¼ subject'),
array('Subject', 'Long Unicode subject - zaÅ¼Ã³Å‚Ä‡ gÄ™Å›lÄ… jaÅºÅ„ - too long for one line'),
array('References', '<hglvja$jg7$1@nemesis.news.neostrada.pl>  <4b2e87ac$1@news.home.net.pl> <hgm5b1$3a7$1@atlantis.news.neostrada.pl>'),
array('To', '"Frank Do" <adresse@adresse.de>,, "James Clark" <james@domain.com>'),
array('To', '"Frank \\" \\\\Do" <adresse@adresse.de>'),
array('To', 'Frank " \\Do <adresse@adresse.de>'),
array('Subject', "A REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY /REALLY/ LONG test"),
array('Subject', "TEST Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir!!!?"),
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
[11] Subject: =?UTF-8?Q?German_Umlauts_=F6=E4=FC_=3Cadresse=40adresse=2Ede=3E?=
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
[18] To: "Frank \" \\Do" <adresse@adresse.de>
[18] To: "Frank \" \\Do" <adresse@adresse.de>
[19] To: "Frank \" \\Do" <adresse@adresse.de>
[19] To: "Frank \" \\Do" <adresse@adresse.de>
[20] Subject: A REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY
 REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY
 REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY
 REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY /REALLY/ LONG test
[20] Subject: A REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY
 REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY
 REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY
 REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY /REALLY/ LONG test
[21] Subject: =?UTF-8?B?VEVTVCBT/HBlciBncvZzZSB0b2xsZSBncvzfZSB2b24gbWlyIFP8?=
 =?UTF-8?B?cGVyIGdy9nNlIHRvbGxlIGdy/N9lIHZvbiBtaXIgU/xwZXIgZ3L2c2UgdG9s?=
 =?UTF-8?B?bGUgZ3L832Ugdm9uIG1pciBT/HBlciBncvZzZSB0b2xsZSBncvzfZSB2b24g?=
 =?UTF-8?B?bWlyIFP8cGVyIGdy9nNlIHRvbGxlIGdy/N9lIHZvbiBtaXIgU/xwZXIgZ3L2?=
 =?UTF-8?B?c2UgdG9sbGUgZ3L832Ugdm9uIG1pciBT/HBlciBncvZzZSB0b2xsZSBncvzf?=
 =?UTF-8?B?ZSB2b24gbWlyIFP8cGVyIGdy9nNlIHRvbGxlIGdy/N9lIHZvbiBtaXIgU/xw?=
 =?UTF-8?B?ZXIgZ3L2c2UgdG9sbGUgZ3L832Ugdm9uIG1pciEhIT8=?=
[21] Subject: =?UTF-8?Q?TEST_S=FCper_gr=F6se_tolle_gr=FC=DFe_von_mir_S=FCper_?=
 =?UTF-8?Q?gr=F6se_tolle_gr=FC=DFe_von_mir_S=FCper_gr=F6se_tolle_gr=FC?=
 =?UTF-8?Q?=DFe_von_mir_S=FCper_gr=F6se_tolle_gr=FC=DFe_von_mir_S=FCper_?=
 =?UTF-8?Q?gr=F6se_tolle_gr=FC=DFe_von_mir_S=FCper_gr=F6se_tolle_gr=FC?=
 =?UTF-8?Q?=DFe_von_mir_S=FCper_gr=F6se_tolle_gr=FC=DFe_von_mir_S=FCper_?=
 =?UTF-8?Q?gr=F6se_tolle_gr=FC=DFe_von_mir_S=FCper_gr=F6se_tolle_gr=FC?=
 =?UTF-8?Q?=DFe_von_mir!!!=3F?=
