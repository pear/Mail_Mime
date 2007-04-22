--TEST--
Bug #8223   Incorrectly encoded quoted-printable headers
     Bug #10793  Long headers don't get wrapped since fix for Bug #10298
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // ignore E_STRICT
include("Mail/mime.php");

$encoder = new Mail_mime();
$encoder->_build_params['ignore-iconv'] = true;
$input['a'] = "A short test";
$input['b'] = "A REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY REALLY /REALLY/ LONG test";
$input['c'] = "TEST Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir Süper gröse tolle grüße von mir!!!?";

$encoded = $encoder->_encodeHeaders($input, array('head_encoding' => 'quoted-printable'));
foreach ($encoded as $line){
    if (strstr($line, '=?')){
        $lines = explode("\n", $line);
        foreach ($lines as $aLine){
            $aLine = trim($aLine);
            if (strlen($aLine) > 75){
                print("Line too long (" . strlen($aLine) . "):\n");
                print($aLine);
                print("\n");
            }else{
#                print("Line OK (" . strlen($aLine) . "):\n");
#                print($aLine);
#                print("\n");
            }
        }
    }
}
print("OK");
--EXPECT--
OK
