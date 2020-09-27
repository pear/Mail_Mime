--TEST--
qp comprehensive test
--SKIPIF--
--FILE--
<?php
error_reporting(E_ALL); // ignore E_STRICT

include("Mail/mimePart.php");

/**
 * Convenience function to make qp encoded output easier to verify
 *
 * @param string $text Input text to be encoded and printed
 * @param int $begin Start character to visibly print from
 * @param int $end Stop character to visibly print to
 * @param bool $special_chars Convert character such as linebreaks
 *     etc. to visible replacements.
 * @param int $break Line length before soft break
 *
 */
function debug_print($text, $begin=False, $end=False, $special_chars=True, $break=76) {
    $begin = $begin ? $begin : strlen($text);
    $end = $end ? $end : strlen($text);

    for ($i=$begin; $i <= $end; $i++) {
        $input = substr($text, 0, $i);
        $output = Mail_mimePart::quotedPrintableEncode($input, $break);

        if ($special_chars) {
            $input_vis = str_replace("\t", '\t', str_replace("\n", '\n', str_replace("\r", '\r', $input)));
        } else {
            $input_vis = $input;
        }
        printf("input: %02d: %s\n", strlen($input), $input_vis);

        $lines = explode("\r\n", $output);
        for($j=0; $j < count($lines); $j++) {
            $line = $lines[$j];
            if ($j + 1 < count($lines) && $special_chars) {
                $line_vis = str_replace("\t", '\t', $line).'\r\n';
            } else {
                $line_vis = $line;
            }
            printf("output:%02d: %s\n", strlen($line), $line_vis);
        }
        print("---\n");
    }
}

// Test linebreaks on regular long lines
$text = '12345678901234567890123456789012345678901234567890123456789012345678901234567890';
debug_print($text, 74);

// Test linebreaks on long line with dot at end.
$text = '123456789.12';
debug_print($text, 10, False, False, 10);

$text = "\tHere's\t\na tab.\n";
debug_print($text, False, False, True, 8);

--EXPECT--
input: 74: 12345678901234567890123456789012345678901234567890123456789012345678901234
output:74: 12345678901234567890123456789012345678901234567890123456789012345678901234
---
input: 75: 123456789012345678901234567890123456789012345678901234567890123456789012345
output:75: 123456789012345678901234567890123456789012345678901234567890123456789012345
---
input: 76: 1234567890123456789012345678901234567890123456789012345678901234567890123456
output:76: 1234567890123456789012345678901234567890123456789012345678901234567890123456
---
input: 77: 12345678901234567890123456789012345678901234567890123456789012345678901234567
output:76: 123456789012345678901234567890123456789012345678901234567890123456789012345=\r\n
output:02: 67
---
input: 78: 123456789012345678901234567890123456789012345678901234567890123456789012345678
output:76: 123456789012345678901234567890123456789012345678901234567890123456789012345=\r\n
output:03: 678
---
input: 79: 1234567890123456789012345678901234567890123456789012345678901234567890123456789
output:76: 123456789012345678901234567890123456789012345678901234567890123456789012345=\r\n
output:04: 6789
---
input: 80: 12345678901234567890123456789012345678901234567890123456789012345678901234567890
output:76: 123456789012345678901234567890123456789012345678901234567890123456789012345=\r\n
output:05: 67890
---
input: 10: 123456789.
output:10: 123456789.
---
input: 11: 123456789.1
output:10: 123456789=
output:04: =2E1
---
input: 12: 123456789.12
output:10: 123456789=
output:05: =2E12
---
input: 16: \tHere's\t\na tab.\n
output:08: \tHere's=\r\n
output:03: =09\r\n
output:06: a tab.\r\n
output:00: 
---
