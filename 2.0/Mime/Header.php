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
class Mail_Mime_Header
{
	/*
	* Name of the header (eg. From)
	*/
	var $_name;
	
	/*
	* Value of the header (eg. foo@example.com)
	*/
	var $_value;
	
	/*
	* Any parameters of the header eg. name="blaat"
	* (Associative array)
	*/
	var $_parameters;

	/**
    * Constructor
	*
	* @access public
    */
	function Mail_Mime_Header($name = null, $value = null, $parameters = null)
	{
		$this->_name       = '';
		$this->_value      = '';
		$this->_parameters = array();
		
		// Set name if given
		if (!is_null($name)) {
			$this->_name = $name;
		}

		// Set value if given
		if (!is_null($value)) {
			$this->_value = $value;
		}

		// Set params if given
		if (!is_null($parameters)) {
			$this->_parameters = $parameters;
		}
	}
	
	/*
	* Sets the name of the header. Returns the
	* old name.
	* 
	* @param  string $name The name of the header
	* @return string       The old name
	*/
	function setName($name)
	{
		$oldName = $this->_name;
		$this->_name = $name;
		
		return $oldName;
	}
	
	/*
	* Returns the current name of the header
	* 
	* @return string Name of the header
	*/
	function getName()
	{
		return $this->_name;
	}
	
	/*
	* Sets the value of the header. Returns the
	* old value.
	* 
	* @param  string $value The value of the header
	* @return string        The old value
	*/
	function setValue($value)
	{
		$oldValue = $this->_value;
		$this->_value = $value;

		return $oldValue;
	}
	
	/*
	* Returns the current value of the header
	* 
	* @return string Value of the header
	*/
	function getValue()
	{
		return $this->_value;
	}
	
	/*
	* Adds a parameter to the header, given a name
	* and value for it. Will replace any existing
	* parameter with the same name.
	* 
	* @param  string $name  Name of the parameter
	* @param  string $value Value of the parameter
	*/
	function addParameter($name, $value)
	{
		$this->_parameters[$name] = $value;
	}
	
	/*
	* Removes a parameter if it exists
	* 
	* @param  string $name Name of parameter to remove
	* @return string       Value of parameter if it 
	*                      existed, false otherwise.
	*/
	function removeParameter($name)
	{
		if (isset($this->_parameters[$name])) {
			$value = $this->_parameters[$name];
			unset($this->_parameters[$name]);
		} else {
			$value = false;
		}
		
		return $value;
	}
	
	/*
	* Returns a specific parameter value if
	* it exists, false otherwise.
	* 
	* @param  string $name Name of parameter
	* @return string       Value of parameter
	*/
	function getParameter($name)
	{
		return isset($this->_parameters[$name]) ? $this->_parameters[$name] : false;
	}
	
	
	/*
	* Returns true/false as to whether a parameter
	* is set in this header.
	* 
	* @param  string $name Name of parameter to check
	* @return bool         True/false
	*/
	function parameterExists($name)
	{
		return isset($this->_parameters[$name]);
	}
	
	/*
	* Returns the header as a text string as you'd
	* see in an email/message
	* 
	* @return string The header, or false if an error occurs
	*/
	function get()
	{
		if (empty($this->_name)) {
			return false;
		}

		// Handle parameters
		if (!empty($this->_parameters)) {
			foreach ($this->_parameters as $name => $value) {
				$parameters[] = sprintf('%s="%s"', $name, $value);
			}
			$parameters = '; ' . implode(";\r\n\t", $parameters);

		} else {
			$parameters = '';
		}

		return sprintf('%s: %s%s', $this->_name, $this->encode($this->_value), $this->encode($parameters));
	}
	
	/*
	* Parses given text and returns a header
	* object. This method should be called
	* statically.
	* 
	* @param  string $input The header (Eg: "Content-Type: foo/bar")
	* @return object       A header object
	*/
	function &parse($input)
	{
		$return = &Mail_Mime_Header::parseMultiple($input);

        return is_object($return[0]) ? $return[0] : new Mail_Mime_Header();
	}
	
	/*
	* Simlar to &parse(), except this can
	* handle multiple headers in the text. This
	* means however that the return value is an
	* array of header objects, even if there is
	* only one header.
	* 
	* @param  string $input The text to parse
	* @return array         Array of header objects
	*/
	function &parseMultiple($input)
	{
        if (!empty($input)) {
            // Unfold the input
            $input   = preg_replace("/\r?\n/", "\r\n", $input);
            $input   = preg_replace("/\r\n(\t| )+/", ' ', $input);
            $headers = explode("\r\n", trim($input));

            foreach ($headers as $value) {
                $hdr_name = substr($value, 0, $pos = strpos($value, ':'));
                $hdr_value = substr($value, $pos+1);

                if($hdr_value[0] == ' ') {
                    $hdr_value = substr($hdr_value, 1);
				}
				
				$hdr_value = Mail_Mime_Header::_parseValue(Mail_Mime_Header::decode($hdr_value));

				$headerObj = &new Mail_Mime_Header($hdr_name, $hdr_value['value'], !empty($hdr_value['parameters']) ? $hdr_value['parameters'] : null); // Ref operator is necessary
                $return[] = &$headerObj;
            }

        } else {
            $return = array();
        }

		return $return;
	}

    /*
    * Encodes a header as per RFC2047
    *
    * @param  optional string $input If given the method will encode
	*                                this data (as if it were the header
	*                                value). If not given it must be called
	*                                as an objects method (not statically)
	*                                and it will encode the current objects
	*                                header value.
    * @return string                 Encoded data or false on errors
    */
    function encode($input = null, $charset = 'ISO-8859-1')
    {
		if (is_null($input) AND ( !isset($this) OR get_class($this) != 'mail_mime_header') ) {
			return false;
		} elseif (is_null($input)) {
			$input = $this->_value;
			$inObject = true;
		}

		// Actual encoding
        preg_match_all('/(\w*[\x80-\xFF]+\w*)/', $input, $matches);
        foreach ($matches[1] as $value) {
            $replacement = preg_replace('/([\x80-\xFF])/e', '"=" . strtoupper(dechex(ord("\1")))', $value);
            $input = str_replace($value, '=?' . $charset . '?Q?' . $replacement . '?=', $input);
        }
		
		// Return or set in object
		if (!empty($inObject)) {
			$this->_value = $input;
		}

        return $input;
    }
	
	/*
	* Decodes headers that have been encoded as per
	* RFC2047. Can be called statically.
	* 
	* @param  string $input The text to decode
	* @return string        Decoded text
	*/
	function decode($input)
    {
        // Remove white space between encoded-words
        $input = preg_replace('/(=\?[^?]+\?(q|b)\?[^?]*\?=)(\s)+=\?/i', '\1=?', $input);

        // For each encoded-word...
        while (preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)/i', $input, $matches)) {

            $encoded  = $matches[1];
            $charset  = $matches[2];
            $encoding = $matches[3];
            $text     = $matches[4];

            switch (strtolower($encoding)) {
                case 'b':
                    $text = base64_decode($text);
                    break;

                case 'q':
                    $text = str_replace('_', ' ', $text);
                    preg_match_all('/=([a-f0-9]{2})/i', $text, $matches);
                    foreach($matches[1] as $value)
                        $text = str_replace('='.$value, chr(hexdec($value)), $text);
                    break;
            }

            $input = str_replace($encoded, $text, $input);
        }

        return $input;
    }

	/*
	* Used by parseMultiple to parse the value of the given
	* header(s) to extract the value and any following parameters
	* 
	* @param  string $input The input to parse
	* @return array         Associative array of "value" and
	*                       "parameters".
	*/
	function _parseValue($input)
    {

        if (($pos = strpos($input, ';')) !== false) {

            $return['value'] = trim(substr($input, 0, $pos));
            $input = trim(substr($input, $pos+1));

            if (strlen($input) > 0) {

                // This splits on a semi-colon, if there's no preceeding backslash
                // Can't handle if it's in double quotes however.
                $parameters = preg_split('/\s*(?<!\\\\);\s*/i', $input);

                for ($i = 0; $i < count($parameters); $i++) {
                    $param_name  = substr($parameters[$i], 0, $pos = strpos($parameters[$i], '='));
                    $param_value = substr($parameters[$i], $pos + 1);
                    if ($param_value[0] == '"') {
                        $param_value = substr($param_value, 1, -1);
                    }
                    $return['parameters'][$param_name] = $param_value;
                    $return['parameters'][strtolower($param_name)] = $param_value;
                }
            }
        } else {
            $return['value'] = trim($input);
        }

        return $return;
    }
}
	
