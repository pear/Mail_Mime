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
* @package Mail
* @access public
*/
class Mail_mime extends Mail
{
    /**
    * contains the mime encoded text
    * @var string
    */
    var $mime;
    /**
    * contains the multipart content
    * @var string
    */
    var $multipart;
    /**
    * list of the attached images
    * @var array
    */
    var $html_images = array();
    /**
    * list of the attachements
    * @var array
    */
    var $parts       = array();

    /*
    * Constructor function
    *
    * @access public
    */
    function Mail_mime()
    {
        $this->boundary = '=_' . md5(uniqid(time()));
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
            $this->txtbody = $data;
        } else {
            $cont = $this->_file2str($data);
            if (PEAR::isError($cont)) {
                return $cont;
            }
            $this->txtbody = $cont;
        }
        return TRUE;
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
            $this->htmlbody = $data;
        } else {
            $cont = $this->_file2str($data);
            if (PEAR::isError($cont)) {
                return $cont;
            }
            $this->htmlbody = $cont;
        }
        return TRUE;
    }

    /*
    * Builds html part of email.
    *
    * @param string $orig_boundary boundary of the beginning content
    *                               type header
    * @access private
    */
    function build_html($orig_boundary)
    {
        $sec_boundary = '=_' . md5(uniqid(time()));
        $thr_boundary = '=_' . md5(uniqid(time()));

        if (count($this->html_images) == 0) {
            $this->multipart .=
                '--'.$orig_boundary."\r\n".
                'Content-Type: multipart/alternative;'.chr(13).chr(10).chr(9).
                'boundary="'.$sec_boundary."\"\r\n\r\n\r\n".

                '--'.$sec_boundary."\r\n".
                'Content-Type: text/plain'."\r\n".
                'Content-Transfer-Encoding: base64'."\r\n\r\n".
                chunk_split(base64_encode($this->txtbody))."\r\n\r\n".

                '--'.$sec_boundary."\r\n".
                'Content-Type: text/html'."\r\n".
                'Content-Transfer-Encoding: base64'."\r\n\r\n".
                chunk_split(base64_encode($this->htmlbody))."\r\n\r\n".
                '--'.$sec_boundary."--\r\n\r\n";
        } else {
            //replaces image names with content-id's.
            for ($i=0; $i<count($this->html_images); $i++) {
                $this->htmlbody = ereg_replace($this->html_images[$i]['name'],
                                   'cid:'.$this->html_images[$i]['cid'],
                                   $this->htmlbody);
            }
            $this->multipart .=
                '--'.$orig_boundary."\r\n".
                'Content-Type: multipart/related;'.chr(13).chr(10).chr(9).
                'boundary="'.$sec_boundary."\"\r\n\r\n\r\n".

                '--'.$sec_boundary."\r\n".
                'Content-Type: multipart/alternative;'.chr(13).chr(10).chr(9).
                'boundary="'.$thr_boundary."\"\r\n\r\n\r\n".

                '--'.$thr_boundary."\r\n".
                'Content-Type: text/plain'."\r\n".
                'Content-Transfer-Encoding: base64'."\r\n\r\n".
                chunk_split(base64_encode($this->txtbody))."\r\n\r\n".

                '--'.$thr_boundary."\r\n".
                'Content-Type: text/html'."\r\n".
                'Content-Transfer-Encoding: base64'."\r\n\r\n".
                chunk_split(base64_encode($this->htmlbody))."\r\n\r\n".
                '--'.$thr_boundary."--\r\n\r\n";

            for ($i=0; $i<count($this->html_images); $i++) {
                $this->multipart .= '--'.$sec_boundary."\r\n";
                $this->build_html_image($i);
            }

            $this->multipart .= "--".$sec_boundary."--\r\n\r\n";
        }
    }

    /*
    * Adds an image to the list of embedded images.
    *
    * @param string $file_name The image file name
    * @param string $c_type The content type
    * @return mixed true on success or PEAR_Error object
    * @access public
    */
    function addHTMLImage ($file_name, $c_type='application/octet-stream')
    {
        $file = $this->_file2str($file_name);
        if (PEAR::isError($file)) {
            return $file;
        }
        $this->html_images[] = array( 'body'   => $file,
                                      'name'   => basename($file_name),
                                      'c_type' => $c_type,
                                      'cid'    => md5(uniqid(time()))
                                    );
        return TRUE;
    }

    /*
    * Adds a file to the list of attachments.
    *
    * @param string $file_name The file name of the file to attach
    * @param string $c_type The content type
    * @return mixed true on success or PEAR_Error object
    * @access public
    */
    function addAttachment ($file_name, $c_type='application/octet-stream')
    {
        $file = $this->_file2str($file_name);
        if (PEAR::isError($file)) {
            return $file;
        }
        $this->parts[] = array( 'body'   => $file,
                                'name'   => basename($file_name),
                                'c_type' => $c_type );
        return TRUE;
    }

    /*
    * Returns the contents of the given file name as string
    * @param string $file_name
    * @return string
    * @acces private
    */
    function & _file2str ($file_name)
    {
        if (!is_readable($file_name)) {
            return $this->raiseError("File is not readable $file_name");
        }
        if (!$fd = fopen($file_name, 'r')) {
            return $this->raiseError("Could not open $file_name");
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
    function build_html_image ($i)
    {
        $this->multipart .= 'Content-Type: '.$this->html_images[$i]['c_type'];

        $fname = basename($this->html_images[$i]['name']);
        $this->multipart .= '; name="' . $fname . "\"\r\n";

        $this->multipart .= 'Content-Transfer-Encoding: base64'."\r\n";
        $this->multipart .= 'Content-ID: <' . $this->html_images[$i]['cid'] . ">\r\n\r\n";
        $this->multipart .= chunk_split(base64_encode($this->html_images[$i]['body'])) . "\r\n";
    }

    /*
    * Builds a single part of a multipart message.
    *
    * @param array &$part Array containing the part data
    * @return string containing the whole part
    * @access private
    */
    function & build_part(&$part)
    {
        $message_part = '';
        $message_part.= 'Content-Type: '.$part['c_type'];
        if ($part['name'] != '') {
            $message_part .= '; name="'.$part['name']."\"\r\n";
        } else {
            $message_part .= "\r\n";
        }

        // Determine content encoding.
        if ($part['c_type'] == 'text/plain') {
            $message_part .= 'Content-Transfer-Encoding: base64'."\r\n\r\n";
            $message_part .= chunk_split(base64_encode($part['body']))."\r\n";
        } elseif ($part['c_type'] == 'message/rfc822') {
            $message_part .= 'Content-Transfer-Encoding: 7bit'."\r\n\r\n";
            $message_part .= $part['body']."\r\n";
        } else {
            $message_part .= 'Content-Transfer-Encoding: base64'."\r\n";
            $message_part .= 'Content-Disposition: attachment; filename="'.$part['name']."\"\r\n\r\n";
            $message_part .= chunk_split(base64_encode($part['body']))."\r\n";
        }

        return $message_part;
    }

    /*
    * Builds the multipart message from the list ($this->_parts) and
    * returns the mime content.
    *
    * @return string The mime content
    * @access public
    */
    function & get()
    {
        $boundary = $this->boundary;
        $this->multipart = "This is a MIME encoded message.\r\n\r\n";
        // For HTML bodies and HTML Images
        if (isset($this->htmlbody)) {
            $this->build_html($boundary);
        // For TXT bodies
        } elseif (isset($this->txtbody)) {
            $part = array('body' => $this->txtbody,
                          'name' => '',
                          'c_type' => 'text/plain');
            $this->multipart .= '--'.$boundary."\r\n".$this->build_part($part);
        }
        // For attachments
        for ($i=(count($this->parts)-1); $i>=0; $i--) {
            $this->multipart .= '--'.$boundary."\r\n".
                                $this->build_part($this->parts[$i]);
        }

        $this->mime = $this->multipart."--".$boundary."--\r\n";
        return $this->mime;
    }

    /*
    * Returns an array with the headers needed to append to the email
    * (MIME-Version and Content-Type)
    *
    * @return array Assoc array with the standar mime headers
    * @access public
    */
    function & headers()
    {
        $headers = array();
        $headers['MIME-Version'] = '1.0';
        $headers['Content-Type'] = 'multipart/mixed;'.chr(13).chr(10).chr(9).
                                    'boundary="'.$this->boundary.'"';
        return $headers;
    }
}
?>
