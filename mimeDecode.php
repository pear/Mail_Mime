<?php
//
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2001 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Richard Heyes <richard.heyes@heyes-computing.net>           |
// +----------------------------------------------------------------------+

	require_once('PEAR.php');

/**
* Mime Decoding class
*
* This class will parse a raw mime email and return
* the structure. Returned structure is similar to 
* that returned by imap_fetchstructure().
*
* USAGE: (assume $input is your raw email)
*
* $decode = new Mail_mimeDecode($input, "\r\n");
* $structure = $decode->decode();
* print_r($structure);
*
* Or statically:
*
* $params['input'] = $input;
* $structure = Mail_mimeDecode::decode($params);
* print_r($structure);
*
* TODO:
*  - Implement full usage of pear error handling
*  - Implement further content types, eg. message/rfc822
*  - Implement decoding of bodies
*  - Implement decoding of headers
*  - Improve robustness
*  - Fix the inevitable bugs :)
*
* @author  Richard Heyes <richard.heyes@heyes-computing.net>
* @version $Revision$
* @package Mail
*/

class Mail_mimeDecode extends PEAR{

    /**
     * The raw email to decode
     * @var	string
     */
	var $_input;

    /**
     * The header part of the input
     * @var	string
     */
	var $_header;

    /**
     * The body part of the input
     * @var	string
     */
	var $_body;

    /**
     * If an error occurs, this is used to store the message
     * @var	string
     */
	var $_error;

    /**
     * Flag to determine whether to decode bodies
     * @var	boolean
     */
	var $_decode_bodies;

    /**
     * Constructor.
     * 
     * Sets up the object, initialise the variables, and splits and
     * stores the header and body of the input.
     *
     * @param string The input to decode
     * @param string CRLF type to use (CRLF/LF/CR)
     * @access public
     */
	function Mail_mimeDecode($input, $crlf = "\r\n")
	{

		if (!defined('MAIL_MIMEDECODE_CRLF'))
			define('MAIL_MIMEDECODE_CRLF', $crlf, TRUE);

		list($header, $body) = $this->splitBodyHeader($input);

		$this->_input         = $input;
		$this->_header        = $header;
		$this->_body          = $body;
		$this->_decode_bodies = FALSE;
	}

    /**
     * Begins the decoding process. If called statically
	 * it will create an object and call the decode() method
	 * of it.
     * 
     * @param array An array of various parameters that determine
	 *              various things:
	 *              decode_bodies - Whether to decode the bodies
	 *                              of the parts. (Transfer encoding)
     *
	 *              input - If called statically, this will be treated
	 *                      as the input
     * @return object Decoded results
     * @access public
     */
	function decode($params = NULL)
	{

		// Have we been called statically? If so, create an object and pass details to that.
		if (!isset($this) AND isset($params['input'])) {

			if (isset($params['crlf']))
				$obj = new Mail_mimeDecode($params['input'], $params['crlf']);
			else
				$obj = new Mail_mimeDecode($params['input']);
			$structure = $obj->decode($params);

		// Called statically but no input
		} elseif (!isset($this)) {
			return new PEAR_Error('Called statically and no input given');

		// Called via an object
		} else {
			$this->_decode_bodies = isset($params['decode_bodies']) ? $params['decode_bodies'] : FALSE;
			$structure = $this->_decode($this->_header, $this->_body);
		}

		return $structure;
	}

    /**
     * Performs the decoding. Decodes the body string passed to it
	 * If it finds certain content-types it will call itself in a
	 * recursive fashion
     * 
     * @param string Header section
	 * @param string Body section
	 * @return object Results of decoding process
     * @access private
     */
	function _decode($headers, $body)
	{

		$return = new stdClass;

		$headers = $this->parseHeaders($headers);

		foreach ($headers as $value)
			$return->headers[] = $value['name'].': '.$value['value'];

		reset($headers);
		while (list($key, $value) = each($headers)) {
			$headers[$key]['name'] = strtolower($headers[$key]['name']);
			switch ($headers[$key]['name']) {
			case 'content-type':
				$content_type = $this->parseHeaderValue($headers[$key]['value']);
				break;

			case 'content-disposition';
				$content_disposition = $this->parseHeaderValue($headers[$key]['value']);
				break;

			case 'content-transfer-encoding':
				$content_transfer_encoding = $this->parseHeaderValue($headers[$key]['value']);
				break;
			}
		}

		if (isset($content_type)) {

			switch ($content_type['value']) {
			case 'text/plain':
				$return->body = $body;
				break;

			case 'text/html':
				$return->body = $body;
				break;

			case 'multipart/alternative':
			case 'multipart/related':
			case 'multipart/mixed':
				if(!isset($content_type['other']['boundary'])){
					$this->_error = 'No boundary found for multipart/* part';
					return FALSE;
				}

				$parts = $this->boundarySplit($body, $content_type['other']['boundary']);
				for($i=0; $i<count($parts); $i++){
					list($part_body, $part_header) = $this->splitBodyHeader($parts[$i]);
					$return->parts[] = $this->_decode($part_body, $part_header);
				}
				break;

			default:
				$return->body = $body;
				break;
			}
		}
		
		return $return;
	}

    /**
     * Given a string containing a header and body
	 * section, this function will split them (at the first
	 * blank line) and return them.
     * 
	 * @param string Input to split apart
	 * @return array Contains header and body section
     * @access private
     */
	function splitBodyHeader($input)
	{

		$pos	= strpos($input, MAIL_MIMEDECODE_CRLF.MAIL_MIMEDECODE_CRLF);
		if ($pos === FALSE) {
			$this->_error = 'Could not split header and body';
			return FALSE;
		}

		$header	= substr($input, 0, $pos+strlen(MAIL_MIMEDECODE_CRLF));
		$body	= substr($input, $pos+(2*strlen(MAIL_MIMEDECODE_CRLF)));

		return array($header, $body);
	}

    /**
     * Parse headers given in $input and return
	 * as assoc array.
     * 
	 * @param string Headers to parse
	 * @return array Contains parsed headers
     * @access private
     */
	function parseHeaders($input)
	{

		// Unfold the input
		$input		= preg_replace('/'.MAIL_MIMEDECODE_CRLF.'(	| )/', ' ', $input);
		$headers	= explode(MAIL_MIMEDECODE_CRLF, trim($input));
	
		foreach ($headers as $value) {

			list($hdr_name, $hdr_value) = explode(': ', $value);
			$return[] = array('name' => $hdr_name, 'value' => $hdr_value);
		}

		return $return;
	}

    /**
     * Function to parse a header value,
	 * extract first part, and any secondary
	 * parts (after ;) This function is not as
	 * robust as it could be. Eg. header comments
	 * in the wrong place will probably break it.
     * 
	 * @param string Header value to parse
	 * @return array Contains parsed result
     * @access private
     */
	function parseHeaderValue($input)
	{

		if (($pos = strpos($input, ';')) !== FALSE) {

			$return['value'] = trim(substr($input, 0, $pos));
			$input = trim(substr($input, $pos+1));

			if (strlen($input) > 0) {
				preg_match_all('/(([[:alnum:]]+)="?([^"]*)"?\s?;?)+/i', $input, $matches);

				for($i=0; $i<count($matches[2]); $i++){
					$return['other'][$matches[2][$i]] = $matches[3][$i];
				}
			}
		} else
			$return['value'] = trim($input);
		
		return $return;
	}

    /**
     * This function splits the input based
	 * on the given boundary
     * 
	 * @param string Input to parse
	 * @return array Contains array of resulting mime parts
     * @access private
     */
	function boundarySplit($input, $boundary)
	{

		$tmp = explode('--'.$boundary, $input);

		for ($i=1; $i<count($tmp)-1; $i++)
			$parts[] = $tmp[$i];

		return $parts;
	}

} // End of class
?>