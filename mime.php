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
// | Authors: Tomas V.V.Cox <cox@idecnet.com>                             |
// |          Richard Heyes <richard.heyes@heyes-computing.net            |
// |                                                                      |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'PEAR.php';

/*
* Mime mail composer class. Can handle: text and html bodies, embedded html
* images and attachments.
* Documentation and example of this class is avaible here:
* http://vulcanonet.com/soft/mime/
*
* @notes This class is based on HTML Mime Mail class from
*   Richard Heyes <richard.heyes@heyes-computing.net> which was based also
*   in the mime_mail.class by Tobias Ratschiller <tobias@dnet.it> and
*   Sascha Schumann <sascha@schumann.cx>.
*
* @author Tomas V.V.Cox <cox@idecnet.com>
* @author Richard Heyes <richard.heyes@heyes-computing.net>
* @package Mail
* @access public
*/
class Mail_mime extends Mail
{
    /**
    * Contains the plain text part of the email
    * @var string
    */
    var $_txtbody;
    /**
    * Contains the html part of the email
    * @var string
    */
    var $_htmlbody;
    /**
    * contains the mime encoded text
    * @var string
    */
    var $_mime;
    /**
    * contains the multipart content
    * @var string
    */
    var $_multipart;
    /**
    * list of the attached images
    * @var array
    */
    var $_html_images = array();
    /**
    * list of the attachements
    * @var array
    */
    var $_parts = array();
    /**
    * Build parameters
    * @var array
    */
    var $_build_params = array();
    /**
    * Headers for the mail
    * @var array
    */
    var $_headers = array();


    /*
    * Constructor function
    *
    * @access public
    */
    function Mail_mime($crlf = "\r\n")
    {
        if (!defined('MAIL_MIME_CRLF')) {
            define('MAIL_MIME_CRLF', $crlf, true);
        }

        $this->_boundary = '=_' . md5(uniqid(time()));

        $this->_build_params = array(
                                     'text_encoding' => '7bit',
                                     'html_encoding' => 'quoted-printable',
                                     '7bit_wrap'     => 998,
                                     'charset'       => 'iso-8859-1'
                                    );
    }

    /*
    * Accessor function to set the body text. Body text is used if
    * it's not an html mail being sent or else is used to fill the
    * text/plain part that emails clients who don't support
    * html should show.
    *
    * @param string $data Either a string or the file name with the
    *        contents
    * @param bool $isfile If true the first param should be trated
    *        as a file name, else as a string (default)
    * @return mixed true on success or PEAR_Error object
    * @access public
    */
    function setTXTBody($data, $isfile = false)
    {
        if (!$isfile) {
            $this->_txtbody = $data;
        } else {
            $cont = $this->_file2str($data);
            if (PEAR::isError($cont)) {
                return $cont;
            }
            $this->_txtbody = $cont;
        }
        return true;
    }

    /*
    * Adds a html part to the mail
    *
    * @param string $data Either a string or the file name with the
    *        contents
    * @param bool $isfile If true the first param should be trated
    *        as a file name, else as a string (default)
    * @return mixed true on success or PEAR_Error object
    * @access public
    */
    function setHTMLBody($data, $isfile = false)
    {
        if (!$isfile) {
            $this->_htmlbody = $data;
        } else {
            $cont = $this->_file2str($data);
            if (PEAR::isError($cont)) {
                return $cont;
            }
            $this->_htmlbody = $cont;
        }

        return true;
    }

    /*
    * Builds html part of email.
    *
    * @param string $orig_boundary boundary of the beginning content
    *                               type header
    * @access private
    */
    function _buildHtml($orig_boundary)
    {
        if (!isset($this->_txtbody)) {
            $this->_txtbody = '';
        }

        $sec_boundary = '=_' . md5(uniqid(time()));
        $thr_boundary = '=_' . md5(uniqid(time()));

        if (count($this->_html_images) == 0) {
            $this->_multipart .=
                '--' . $orig_boundary . MAIL_MIME_CRLF .
                'Content-Type: multipart/alternative;' . MAIL_MIME_CRLF . chr(9) .
                'boundary="' . $sec_boundary.'"' . MAIL_MIME_CRLF . MAIL_MIME_CRLF .

                '--' . $sec_boundary . MAIL_MIME_CRLF .
                'Content-Type: text/plain; charset="' . $this->_build_params['charset'] . '"' . MAIL_MIME_CRLF .
                $this->_getEncodedData($this->_txtbody, $this->_build_params['text_encoding']) . MAIL_MIME_CRLF .

                '--'.$sec_boundary . MAIL_MIME_CRLF .
                'Content-Type: text/html; charset="' . $this->_build_params['charset'] . '"' . MAIL_MIME_CRLF .
                $this->_getEncodedData($this->_htmlbody, $this->_build_params['html_encoding']) . MAIL_MIME_CRLF .
                '--' . $sec_boundary . '--' . MAIL_MIME_CRLF . MAIL_MIME_CRLF;

        } else {

            // Replaces image names with content-id's.
            for ($i = 0; $i < count($this->_html_images); $i++) {
                $this->_htmlbody = str_replace($this->_html_images[$i]['name'],
                                              'cid:' . $this->_html_images[$i]['cid'],
                                              $this->_htmlbody);
            }
            $this->_multipart .=
                '--'.$orig_boundary . MAIL_MIME_CRLF .
                'Content-Type: multipart/related;' . MAIL_MIME_CRLF . chr(9) .
                'boundary="' . $sec_boundary . '"' . MAIL_MIME_CRLF . MAIL_MIME_CRLF .

                '--'.$sec_boundary . MAIL_MIME_CRLF .
                'Content-Type: multipart/alternative;' . MAIL_MIME_CRLF . chr(9) .
                'boundary="' . $thr_boundary . '"' . MAIL_MIME_CRLF . MAIL_MIME_CRLF .

                '--' . $thr_boundary . MAIL_MIME_CRLF .
                'Content-Type: text/plain; charset="' . $this->_build_params['charset'] . '"' . MAIL_MIME_CRLF .
                $this->_getEncodedData($this->_txtbody, $this->_build_params['text_encoding']) . MAIL_MIME_CRLF .

                '--'.$thr_boundary . MAIL_MIME_CRLF .
                'Content-Type: text/html; charset="' . $this->_build_params['charset'] . '"' . MAIL_MIME_CRLF .
                $this->_getEncodedData($this->_htmlbody, $this->_build_params['html_encoding']) . MAIL_MIME_CRLF .
                '--' . $thr_boundary . '--' . MAIL_MIME_CRLF;

            // Add the embedded images
            for ($i = 0; $i < count($this->_html_images); $i++) {
                $this->_multipart .= '--' . $sec_boundary . MAIL_MIME_CRLF;
                $this->_buildHtmlImage($this->_html_images[$i]);
            }

            $this->_multipart .= '--' . $sec_boundary . '--' . MAIL_MIME_CRLF;
        }
    }

    /*
    * Adds an image to the list of embedded images.
    *
    * @param string $file The image file name OR image data itself
    * @param string $c_type The content type
    * @param string $name The filename of the image. Only use if $file is the image data
    * @param bool $isfilename Whether $file is a filename or not. Defaults to true
    * @return mixed true on success or PEAR_Error object
    * @access public
    */
    function addHTMLImage($file, $c_type='application/octet-stream', $name = '', $isfilename = true)
    {
        $filedata = ($isfilename === true) ? $this->_file2str($file) : $file;
        $filename = ($isfilename === true) ? basename($file) : basename($name);
        if (PEAR::isError($filedata)) {
            return $filedata;
        }
        $this->_html_images[] = array(
                                      'body'   => $filedata,
                                      'name'   => $filename,
                                      'c_type' => $c_type,
                                      'cid'    => md5(uniqid(time()))
                                     );
        return true;
    }

    /*
    * Adds a file to the list of attachments.
    *
    * @param string $file The file name of the file to attach OR the file data itself
    * @param string $c_type The content type
    * @param string $name The filename of the attachment. Only use if $file is the file data
    * @param bool $isFilename Whether $file is a filename or not. Defaults to true
    * @return mixed true on success or PEAR_Error object
    * @access public
    */
    function addAttachment($file, $c_type='application/octet-stream', $name = '', $isfilename = true)
    {
        $filedata = ($isfilename === true) ? $this->_file2str($file) : $file;
        $filename = ($isfilename === true) ? basename($file) : basename($name);
        if (PEAR::isError($filedata)) {
            return $filedata;
        }

        $this->_parts[] = array(
                                'body'   => $filedata,
                                'name'   => $filename,
                                'c_type' => $c_type
                               );
        return true;
    }

    /*
    * Returns the contents of the given file name as string
    * @param string $file_name
    * @return string
    * @acces private
    */
    function & _file2str($file_name)
    {
        if (!is_readable($file_name)) {
            return $this->raiseError('File is not readable ' . $file_name);
        }
        if (!$fd = fopen($file_name, 'rb')) {
            return $this->raiseError('Could not open ' . $file_name);
        }
        $cont = fread($fd, filesize($file_name));
        fclose($fd);
        return $cont;
    }

    /*
    * Builds an embedded image part of an html mail.
    *
    * @param integer $i number of the image to build
    * @access private
    */
    function _buildHtmlImage($image)
    {
        $this->_multipart .= 'Content-Type: ' . $image['c_type'];

        $fname = basename($image['name']);
        $this->_multipart .= '; name="' . $fname . '"' . MAIL_MIME_CRLF;

        $this->_multipart .= 'Content-ID: <' . $image['cid'] . '>' . MAIL_MIME_CRLF;
        $this->_multipart .= $this->_getEncodedData($image['body'], 'base64') . MAIL_MIME_CRLF;
    }

    /*
    * Builds a single part of a multipart message.
    *
    * @param array &$part Array containing the part data
    * @return string containing the whole part
    * @access private
    */
    function & _buildPart(&$part)
    {
        $message_part = '';
        $message_part.= 'Content-Type: ' . $part['c_type'];
        if ($part['name'] != '') {
            $message_part .= '; name="' . $part['name'] . '"' . MAIL_MIME_CRLF;
        } else {
            $message_part .= MAIL_MIME_CRLF;
        }

        // Determine content encoding.
        if ($part['c_type'] == 'text/plain') {
            $message_part .= 'Content-Disposition: attachment; filename="' . $part['name'] . '"' . MAIL_MIME_CRLF;
            $message_part .= $this->_getEncodedData($part['body'], $this->_build_params['text_encoding']) . MAIL_MIME_CRLF;

        } elseif ($part['c_type'] == 'message/rfc822') {
            $message_part .= $this->_getEncodedData($part['body'], '7bit') . MAIL_MIME_CRLF;

        } else {
            $message_part .= 'Content-Disposition: attachment; filename="' . $part['name'] . '"' . MAIL_MIME_CRLF;
            $message_part .= $this->_getEncodedData($part['body'], 'base64') . MAIL_MIME_CRLF;
        }

        return $message_part;
    }

    /*
    * Encodes data quoted-printable style
    *
    * @param string Data to encode
    * @return string Encoded data
    * @access private
    */
    function _quotedPrintableEncode($input , $line_max = 76)
    {
        $lines  = preg_split("/(\r\n|\r|\n)/", $input);
        $eol    = MAIL_MIME_CRLF;
        $escape = '=';
        $output = '';

        while (list(, $line) = each($lines)) {

            $linlen  = strlen($line);
            $newline = '';

            for ($i = 0; $i < $linlen; $i++) {
                $char = substr($line, $i, 1);
                $dec  = ord($char);

                if (($dec == 32) AND ($i == ($linlen - 1))) {
                    $char = '=20';

                } elseif($dec == 9) {
                    // Do nothing if a tab.

                } elseif(($dec == 61) OR ($dec < 32 ) OR ($dec > 126)) {
                    $char = $escape . strtoupper(sprintf('%02s', dechex($dec)));
                }

                if ((strlen($newline) + strlen($char)) >= $line_max) {
                    $output  .= $newline . $escape . $eol;
                    $newline  = '';
                }
                $newline .= $char;
            }

            $output .= $newline . $eol;
        }

        return substr($output, 0, -1*strlen($eol)); // Don't want the final CRLF
    }

    /*
    * Returns data passed to it based on the given encoding type.
    *
    * @param string Data to be encoded
    * @param string Encoding type use, currently one of
    *               7bit, quoted-printable, base64
    * @return string Encoded data
    * @access private
    */
    function _getEncodedData($data, $encoding)
    {
        $return = '';

        switch($encoding){

            case '7bit':
                $return .= 'Content-Transfer-Encoding: 7bit' . MAIL_MIME_CRLF . MAIL_MIME_CRLF .
                           substr(chunk_split($data, $this->_build_params['7bit_wrap'], MAIL_MIME_CRLF), 0, -1 * strlen(MAIL_MIME_CRLF));
                break;

            case 'quoted-printable':
                $return .= 'Content-Transfer-Encoding: quoted-printable' . MAIL_MIME_CRLF . MAIL_MIME_CRLF .
                           $this->_quotedPrintableEncode($data);
                break;

            case 'base64':
                $return .= 'Content-Transfer-Encoding: base64' . MAIL_MIME_CRLF . MAIL_MIME_CRLF .
                           substr(chunk_split(base64_encode($data), 76, MAIL_MIME_CRLF), 0, -1 * strlen(MAIL_MIME_CRLF));
                           // The substr removes the last CRLF that chunk_split adds
                break;
        }

        return $return;
    }

    /*
    * Builds the multipart message from the list ($this->_parts) and
    * returns the mime content.
    *
    * @param  array  Build parameters that change the way the email
    *                is built. Should be associative. Can contain:
    *                text_encoding  -  What encoding to use for plain text
    *                                  Default is 7bit
    *                html_encoding  -  What encoding to use for html
    *                                  Default is quoted-printable
    *                7bit_wrap      -  Number of characters before text is
    *                                  wrapped in 7bit encoding
    *                                  Default is 998
    *                charset        -  The character set to use.
    *                                  Default is iso-8859-1
    * @return string The mime content
    * @access public
    */
    function & get($build_params = null)
    {
        if (isset($build_params)) {
            while (list($key, $value) = each($build_params)) {
                $this->_build_params[$key] = $value;
            }
        }

        $do_text  = isset($this->_txtbody)   ? true : false;
        $do_html  = isset($this->_htmlbody)  ? true : false;
        $do_parts = count($this->_parts) > 0 ? true : false;

        $boundary = $this->_boundary;

        // Need to make a multipart email?
        if ($do_html OR $do_parts) {
            $this->_multipart = 'This is a MIME encoded message.' . MAIL_MIME_CRLF . MAIL_MIME_CRLF;

            // For HTML bodies and HTML Images
            if ($do_html) {
                $this->_buildHtml($boundary);

            // For TXT bodies
            } elseif ($do_text) {
                $part = array(
                              'body'   => $this->_txtbody,
                              'name'   => '',
                              'c_type' => 'text/plain'
                             );

                $this->_multipart .= '--' . $boundary . MAIL_MIME_CRLF . $this->_buildPart($part);
            }

        // Plain text email
        } elseif ($do_text) {
            $this->_multipart = $this->_txtbody;
        }

        // Build any attachments
        if ($do_parts) {
            for ($i = 0; $i < count($this->_parts); $i++) {
                $this->_multipart .= '--' . $boundary . MAIL_MIME_CRLF . $this->_buildPart($this->_parts[$i]);
            }
        }

        $this->_mime = ($do_html OR $do_parts) ? $this->_multipart . '--' . $boundary . '--' . MAIL_MIME_CRLF : $this->_multipart;
        return $this->_mime;
    }

    /*
    * Returns an array with the headers needed to append to the email
    * (MIME-Version and Content-Type)
    *
    * @param  array Assoc array with any extra headers. Optional.
    * @return array Assoc array with the standard mime headers
    * @access public
    */
    function & headers($xtra_headers = null)
    {
        $do_text  = isset($this->_txtbody)   ? true : false;
        $do_html  = isset($this->_htmlbody)  ? true : false;
        $do_parts = count($this->_parts) > 0 ? true : false;

        // Add the mime headers
        if ($do_html OR $do_parts) {
            $this->_headers['MIME-Version'] = '1.0';
            $this->_headers['Content-Type'] = 'multipart/mixed;' . MAIL_MIME_CRLF . chr(9) .
                                                'boundary="' . $this->_boundary . '"';

        // Just set the content-type to text/plain
        } elseif ($do_text) {
            $this->_headers['Content-Type'] = 'text/plain';
        }

        if (isset($xtra_headers)) {
            $this->_headers = array_merge($this->_headers, $xtra_headers);
        }

        return $this->_headers;
    }
}
?>