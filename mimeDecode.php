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
*  +----------------------------- IMPORTANT ------------------------------+
*  | Usage of this class compared to native php extensions such as        |
*  | mailparse or imap, is slow and may be feature deficient. If available|
*  | you are STRONGLY recommended to use the php extensions.              |
*  +----------------------------------------------------------------------+
*
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
*  - Implement further content types, eg. multipart/parallel,
*    perhaps even message/partial.
*  - Implement decoding of bodies (Just need the _decodeBodies method writing)
*
* @author  Richard Heyes <richard.heyes@heyes-computing.net>
* @version $Revision$
* @package Mail
*/

class Mail_mimeDecode extends PEAR{

    /**
     * The raw email to decode
     * @var    string
     */
    var $_input;

    /**
     * The header part of the input
     * @var    string
     */
    var $_header;

    /**
     * The body part of the input
     * @var    string
     */
    var $_body;

    /**
     * If an error occurs, this is used to store the message
     * @var    string
     */
    var $_error;

    /**
     * Flag to determine whether to include bodies in the
     * returned object.
     * @var    boolean
     */
    var $_include_bodies;

    /**
     * Flag to determine whether to decode bodies
     * @var    boolean
     */
    var $_decode_bodies;

    /**
     * Flag to determine whether to decode headers
     * @var    boolean
     */
    var $_decode_headers;

    /**
     * Variable to hold the line end type.
     * @var    string
     */
    var $_crlf;

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

        $this->_crlf = $crlf;
        list($header, $body) = $this->_splitBodyHeader($input);

        $this->_input          = $input;
        $this->_header         = $header;
        $this->_body           = $body;
        $this->_decode_bodies  = false;
        $this->_include_bodies = true;
    }

    /**
     * Begins the decoding process. If called statically
     * it will create an object and call the decode() method
     * of it.
     * 
     * @param array An array of various parameters that determine
     *              various things:
     *              include_bodies - Whether to include the body in the returned
     *                               object.
     *              decode_bodies  - Whether to decode the bodies
     *                               of the parts. (Transfer encoding)
     *              decode_headers - Whether to decode headers
     *              input          - If called statically, this will be treated
     *                               as the input
     *              crlf           - If called statically, this will be used as
     *                               the crlf value.
     * @return object Decoded results
     * @access public
     */
    function decode($params = null)
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
            return $this->raiseError('Called statically and no input given');

        // Called via an object
        } else {
            $this->_include_bodies = isset($params['include_bodies'])  ? $params['include_bodies']  : false;
            $this->_decode_bodies  = isset($params['decode_bodies'])   ? $params['decode_bodies']   : false;
            $this->_decode_headers = isset($params['decode_headers'])  ? $params['decode_headers']  : false;
            
            $structure = $this->_decode($this->_header, $this->_body);
            if($structure === false)
                $structure = $this->raiseError($this->_error);
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
    function _decode($headers, $body, $default_ctype = 'text/plain')
    {
        $return = new stdClass;
        $headers = $this->_parseHeaders($headers);

        foreach ($headers as $value) {
            if (isset($return->headers[strtolower($value['name'])]) AND !is_array($return->headers[strtolower($value['name'])])) {
                $return->headers[strtolower($value['name'])]   = array($return->headers[strtolower($value['name'])]);
                $return->headers[strtolower($value['name'])][] = $value['value'];

            } elseif (isset($return->headers[strtolower($value['name'])])) {
                $return->headers[strtolower($value['name'])][] = $value['value'];

            } else {
                $return->headers[strtolower($value['name'])] = $value['value'];
            }
        }

        reset($headers);
        while (list($key, $value) = each($headers)) {
            $headers[$key]['name'] = strtolower($headers[$key]['name']);
            switch ($headers[$key]['name']) {

                case 'content-type':
                    $content_type = $this->_parseHeaderValue($headers[$key]['value']);
    
                    if (preg_match('/([0-9a-z+.-]+)\/([0-9a-z+.-]+)/i', $content_type['value'], $regs)) {
                        $return->ctype_primary   = $regs[1];
                        $return->ctype_secondary = $regs[2];
                    }
    
                    if (isset($content_type['other'])) {
                        while (list($p_name, $p_value) = each($content_type['other'])) {
                            $return->ctype_parameters[$p_name] = $p_value;
                        }
                    }
                    break;

                case 'content-disposition';
                    $content_disposition = $this->_parseHeaderValue($headers[$key]['value']);
                    $return->disposition   = $content_disposition['value'];
                    if (isset($content_disposition['other'])) {
                        while (list($p_name, $p_value) = each($content_disposition['other'])) {
                            $return->d_parameters[$p_name] = $p_value;
                        }
                    }
                    break;

                case 'content-transfer-encoding':
                    $content_transfer_encoding = $this->_parseHeaderValue($headers[$key]['value']);
                    break;
            }
        }

        if (isset($content_type)) {

            switch (strtolower($content_type['value'])) {
                case 'text/plain':
                    $encoding = isset($content_transfer_encoding) ? $content_transfer_encoding['value'] : '7bit';
                    $this->_include_bodies ? $return->body = ($this->_decode_bodies ? $this->_decodeBody($body, $encoding) : $body) : null;
                    break;
    
                case 'text/html':
                    $encoding = isset($content_transfer_encoding) ? $content_transfer_encoding['value'] : '7bit';
                    $this->_include_bodies ? $return->body = ($this->_decode_bodies ? $this->_decodeBody($body, $encoding) : $body) : null;
                    break;

                case 'multipart/digest':
                case 'multipart/alternative':
                case 'multipart/related':
                case 'multipart/mixed':
                    if(!isset($content_type['other']['boundary'])){
                        $this->_error = 'No boundary found for ' . $content_type['value'] . ' part';
                        return false;
                    }

                    $default_ctype = (strtolower($content_type['value']) === 'multipart/digest') ? 'message/rfc822' : 'text/plain';

                    $parts = $this->_boundarySplit($body, $content_type['other']['boundary']);
                    for ($i = 0; $i < count($parts); $i++) {
                        list($part_header, $part_body) = $this->_splitBodyHeader($parts[$i]);
                        $part = $this->_decode($part_header, $part_body, $default_ctype);
                        if($part === false)
                            $part = $this->raiseError($this->_error);
                        $return->parts[] = $part;
                    }
                    break;

                case 'message/rfc822':
                    $obj = new Mail_mimeDecode($body, $this->_crlf);
                    $return->parts[] = $obj->decode(array('include_bodies' => $this->_include_bodies));
                    unset($obj);
                    break;

                default:
                    $this->_include_bodies ? $return->body = ($this->_decode_bodies ? $this->_decodeBody($body, $content_transfer_encoding['value']) : $body) : null;
                    break;
            }

        } else {
            $ctype = explode('/', $default_ctype);
            $return->ctype_primary   = $ctype[0];
            $return->ctype_secondary = $ctype[1];
            $this->_include_bodies ? $return->body = ($this->_decode_bodies ? $this->_decodeBody($body) : $body) : null;
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
    function _splitBodyHeader($input)
    {

        $pos = strpos($input, $this->_crlf . $this->_crlf);
        if ($pos === false) {
            $this->_error = 'Could not split header and body';
            return false;
        }

        $header = substr($input, 0, $pos);
        $body   = substr($input, $pos+(2*strlen($this->_crlf)));

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
    function _parseHeaders($input)
    {

        if ($input !== '') {
            // Unfold the input
            $input   = preg_replace('/' . $this->_crlf . "(\t| )/", ' ', $input);
            $headers = explode($this->_crlf, trim($input));
    
            foreach ($headers as $value) {
                $hdr_name = substr($value, 0, $pos = strpos($value, ':'));
                $hdr_value = substr($value, $pos+1);
                $return[] = array(
                                  'name'  => $hdr_name,
                                  'value' => $this->_decode_headers ? $this->_decodeHeader($hdr_value) : $hdr_value
                                 );
            }
        } else {
            $return = array();
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
    function _parseHeaderValue($input)
    {

        if (($pos = strpos($input, ';')) !== false) {

            $return['value'] = trim(substr($input, 0, $pos));
            $input = trim(substr($input, $pos+1));

            if (strlen($input) > 0) {
                preg_match_all('/(([[:alnum:]]+)="?([^"]*)"?\s?;?)+/i', $input, $matches);

                for ($i = 0; $i < count($matches[2]); $i++) {
                    $return['other'][strtolower($matches[2][$i])] = $matches[3][$i];
                }
            }
        } else {
            $return['value'] = trim($input);
        }
        
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
    function _boundarySplit($input, $boundary)
    {
        $tmp = explode('--'.$boundary, $input);

        for ($i=1; $i<count($tmp)-1; $i++) {
            $parts[] = $tmp[$i];
        }

        return $parts;
    }

    /**
     * Given a header, this function will decode it
     * according to RFC2047. Probably not *exactly*
     * conformant, but it does pass all the given
     * examples (in RFC2047).
     *
     * @param string Input header value to decode
     * @return string Decoded header value
     * @access private
     */
    function _decodeHeader($input)
    {
        // Remove white space between encoded-words
        $input = preg_replace('/(=\?[^?]+\?(Q|B)\?[^?]*\?=)( |' . "\t|" . $this->_crlf . ')+=\?/', '\1=?', $input);

        // For each encoded-word...
        while (preg_match('/(=\?([^?]+)\?(Q|B)\?([^?]*)\?=)/', $input, $matches)) {

            $encoded  = $matches[1];
            $charset  = $matches[2];
            $encoding = $matches[3];
            $text     = $matches[4];

            switch ($encoding) {
                case 'B':
                    $text = base64_decode($text);
                    break;

                case 'Q':
                    $text = str_replace('_', ' ', $text);
                    preg_match_all('/=([A-F0-9]{2})/', $text, $matches);
                    foreach($matches[1] as $value)
                        $text = str_replace('='.$value, chr(hexdec($value)), $text);
                    break;
            }

            $input = str_replace($encoded, $text, $input);
        }
        
        return $input;
    }

    /**
     * Given a body string and an encoding type, 
     * this function will decode and return it.
     *
     * @param  string Input body to decode
     * @param  string Encoding type to use.
     * @return string Decoded body
     * @access private
     */
    function _decodeBody($input, $encoding = '7bit')
    {
        switch ($encoding) {
            case '7bit':
                return $input;
                break;

            case 'quoted-printable':
                return $this->_quotedPrintableDecode($input);
                break;

            case 'base64':
                return base64_decode($input);
                break;

            default:
                return $input;
        }
    }

    /**
     * Given a quoted-printable string, this
     * function will decode and return it.
     *
     * @param  string Input body to decode
     * @return string Decoded body
     * @access private
     */
    function _quotedPrintableDecode($input)
    {
        // Remove soft line breaks
        $input = preg_replace("/=\r?\n/", '', $input);

        // Replace encoded characters
        if (preg_match_all('/=[A-Z0-9]{2}/', $input, $matches)) {
            $matches = array_unique($matches[0]);
            foreach ($matches as $value) {
                $input = str_replace($value, chr(hexdec(substr($value,1))), $input);
            }
        }

        return $input;
    }

} // End of class
?>