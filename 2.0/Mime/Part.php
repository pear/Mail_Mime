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

require_once('Mail/Mime/Header.php');

/**
* Constants
*/
define('MAIL_MIME_PART_7BIT', '7bit', true);
define('MAIL_MIME_PART_8BIT', '8bit', true);
define('MAIL_MIME_PART_BASE64', 'base64', true);
define('MAIL_MIME_PART_QPRINT', 'quoted-printable', true);

define('MAIL_MIME_PART_PLAIN', 'text/plain', true);
define('MAIL_MIME_PART_HTML', 'text/html', true);

/**
* 
*/
class Mail_Mime_Part
{
	/**
    * Any headers for this part
	* @var array
    */
	var $_headers;

	/**
    * The body of this part
	* @var string
    */
	var $_body;
	
	/**
    * Subparts to this part
	* @var array
    */
	var $_subParts;

	/**
    * The encoding to use
	* @var string
    */
	var $_encoding;

	/**
    * The encoded version of this mimepart
	* @var string
    */
	var $_encoded;

	/**
    * Constructor
	*
	* @access public
    */
	function Mail_Mime_Part()
	{
		$this->_headers  = array();
		$this->_body     = '';
		$this->_subParts = array();
		$this->_encoding = '7bit';
		$this->_encoded  = null;

        if (!defined('MAIL_MIME_PART_CRLF')) {
            define('MAIL_MIME_PART_CRLF', defined('MAIL_MIME_CRLF') ? MAIL_MIME_CRLF : "\r\n", true);
        }
	}
	
	/**
    * Adds a header to this mime part
	*
	* @param  object $name  The Mail_Mime_Header object
    */
	function addHeader($headerObj)
	{
		$this->_headers[$headerObj->getName()] = $headerObj;

		// Update encoding if necessary
		if (strcasecmp($headerObj->getName(), 'content-transfer-encoding') == 0) {
			$this->_encoding = $headerObj->getValue();
		}
	}
	
	/**
    * Removes a previously set header
	*
	* @param  string $name The header to unset
	* @access public
    */
	function removeHeader($name)
	{
		if (isset($this->_headers[$name])) {
			unset($this->_headers[$name]);
		}
	}
	
	/**
    * Sets the body for this part
	*
	* @param  string $body The body string
	* @access public
    */
	function setBody($body)
	{
		$this->_body = $body;
	}
	
    /**
    * &addSubPart()
    *
    * Adds a subpart to current mime part and returns
    * a reference to it
    *
    * @param  object $subPart The Mail_MimePart subpart to add
    * @return                 A reference to the part you just added. It is
    *                         crucial if using multipart/* in your subparts that
    *                         you use =& in your script when calling this function,
    *                         otherwise you will not be able to add further subparts.
    * @access public
    */
    function &addSubPart(&$subPart)
    {
        $this->_subParts[] = &$subPart;
        return $this->_subParts[count($this->_subParts) - 1];
    }

    /**
    * Encodes and returns the email. Also stores
    * it in the encoded member variable
    *
    * @return An associative array containing two elements,
    *         body and headers. The headers element is itself
    *         an indexed array.
    * @access public
    */
	function encode()
	{
        $encoded = &$this->_encoded;

        if (!empty($this->_subParts)) {
            srand((double)microtime()*1000000);
            $boundary = '=_' . md5(uniqid(rand()) . microtime());
			$this->_headers['Content-Type']->addParameter('boundary', $boundary);

            // Add body parts to $subparts
            for ($i = 0; $i < count($this->_subParts); $i++) {
                $headers = array();
                $tmp = $this->_subParts[$i]->encode();
                foreach ($tmp['headers'] as $value) {
                    $headers[] = $value->get();
                }
                $subparts[] = (!empty($headers) ? implode(MAIL_MIME_PART_CRLF, $headers) . MAIL_MIME_PART_CRLF : '') . MAIL_MIME_PART_CRLF . $tmp['body'];
            }

            $encoded['body'] = '--' . $boundary . MAIL_MIME_PART_CRLF .
                               implode('--' . $boundary . MAIL_MIME_PART_CRLF, $subparts) .
                               '--' . $boundary.'--' . MAIL_MIME_PART_CRLF;

        } else {
            $encoded['body'] = $this->_getEncodedData($this->_body, $this->_encoding) . MAIL_MIME_PART_CRLF;
        }

        // Add headers to $encoded
        $encoded['headers'] = $this->_headers;
		
		// Add indexed array of headers for convenience
		foreach ($encoded['headers'] as $value) {
			$encoded['headers_idx'][] = $value->get();
		}

        return $encoded;
	}

    /**
    * Returns encoded data based upon encoding passed to it
    *
    * @param  string $data     The data to encode.
    * @param  string $encoding The encoding type to use; 7bit, 8bit, base64,
    *                          or quoted-printable.
    * @access private
    */
    function _getEncodedData($data, $encoding)
    {
        switch ($encoding) {
            case 'quoted-printable':
				require_once('Mail/Mime/Qprint.php');
				return Mail_Mime_Qprint::encode($data);
                break;

            case 'base64':
				require_once('Mail/Mime/Base64.php');
				return Mail_Mime_Base64::encode($data);
                break;

            case '7bit':
            case '8bit':
            default:
                return $data;
        }
    }
} // End of Mail_MimePart
?>
