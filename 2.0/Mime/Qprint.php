<?php
// +-----------------------------------------------------------------------+
// | Copyright (c) 2002  Richard Heyes                                     |
// | All rights reserved.                                                  |
// |                                                                       |
// | Redistribution and use in source and binary forms, with or without    |
// | modification, are permitted provided that the following conditions    |
// | are met:                                                              |
// |                                                                       |
// | o Redistributions of source code must retain the above copyright      |
// |   notice, this list of conditions and the following disclaimer.       |
// | o Redistributions in binary form must reproduce the above copyright   |
// |   notice, this list of conditions and the following disclaimer in the |
// |   documentation and/or other materials provided with the distribution.|
// | o The names of the authors may not be used to endorse or promote      |
// |   products derived from this software without specific prior written  |
// |   permission.                                                         |
// |                                                                       |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
// | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
// | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
// | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
// | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
// | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
// |                                                                       |
// +-----------------------------------------------------------------------+
// | Author: Richard Heyes <richard@phpguru.org>                           |
// +-----------------------------------------------------------------------+

/**
* @author  Richard Heyes <richard@phpguru.org>
* @package Mail_Mime
* @access  public
*/
class Mail_Mime_Qprint
{
	/**
    * Constructor
    */
	function Mail_Mime_Qprint()
	{
	}
	
	/*
	* Function to encode data using
	* quoted printable encoding.
	* Can be called statically, eg
	* Mail_Mime_Qprint::encode()
    *
    * @param  string  $input    The data to encode
    * @param  integer $line_max Optional max line length. Should
    *                           not be more than 76 chars
	* @return string            The encoded data
    */
	function encode($input , $lineMax = 76)
	{
		// Replace non printables
		$input    = preg_replace('/([^\x20\x21-\x3C\x3E-\x7E\x0A\x0D])/e', 'sprintf("=%02X", ord("\1"))', $input);
		$inputLen = strlen($input);
		$outLines = array();
		$output   = '';

		$lines = preg_split('/\r?\n/', $input);
		
		// Walk through each line
		for ($i=0; $i<count($lines); $i++) {
			// Is line too long ?
			if (strlen($lines[$i]) > $lineMax) {
				$outLines[] = substr($lines[$i], 0, $lineMax - 1) . "="; // \r\n Gets added when lines are imploded
				$lines[$i] = substr($lines[$i], $lineMax - 1);
				$i--; // Ensure this line gets redone as we just changed it
			} else {
				$outLines[] = $lines[$i];
			}
		}
		
		// Convert trailing whitespace		
		$output = preg_replace('/(\x20+)$/me', 'str_replace(" ", "=20", "\1")', $outLines);

		return implode("\r\n", $output);
	}
	
	/*
	* Function to decode quoted
	* printable encoded data.
	* Can be called statically, eg
	* Mail_Mime_Qprint::decode()
	* 
    * @param  string Input body to decode
    * @return string Decoded body
	*/
	function decode($input)
	{
        // Remove soft line breaks
        $input = preg_replace("/=\r?\n/", '', $input);

        // Replace encoded characters
		$input = preg_replace('/=([a-f0-9]{2})/ie', "chr(hexdec('\\1'))", $input);

        return $input;
	}
}
	
