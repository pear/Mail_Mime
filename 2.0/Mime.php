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
// |         Tomas V.V.Cox <cox@idecnet.com> (initial port to PEAR)        |
// +-----------------------------------------------------------------------+
//
// $Id$

require_once('Mail/Mime/Part.php');

/**
* @author Richard Heyes <richard@phpguru.org>
* @author Tomas V.V.Cox <cox@idecnet.com>
* @package Mail_Mime
* @access public
*
* Method API
* ¯¯¯¯¯¯¯¯¯¯
* file2str          Loads a file from disk and returns it as a string
* getMimeBody       Returns the mime body content after the mail has been built
* getHeaders        Returns the headers (header name as array key, content as value)
* getTxtHeaders     Returns plain text/flat version of headers
* setText           Sets the text content
* setHTML           Sets the HTML content
* setTextEncoding   Sets the text encoding
* setHTMLEncoding   Sets the HTML encoding
* setTextCharset    Sets the text character set
* setHTMLCharset    Sets the HTML character set
* setHeadCharset    Sets the character set used for header encoding
* setTextWrap       Sets the max line length for text part
* setHeader         Sets a header, encoding it if necessary
* setSubject        Sets the subject of the mail
* setFrom           Sets the From header of the mail
* setReturnPath     Sets the return path of the mail
* setCc             Sets the Cc header of the mail (parsed for recipients before mail is sent)
* setBcc            Sets the Bcc header of the mail (parsed & removed before mail is sent)
* addHTMLImage      Adds an embedded image to the mail
* addAttachment     Adds an attachment to the mail
* build             Builds the mail
* getRFC822         Returns the mail as an RFC822 email. Used for attaching mails to other mails
* send              Sends the mail using the PHP mail() function
* sendSMTP          Sends the mail using SMTP. Relies on the PEAR Mail package for this functionality
*/
class Mail_Mime
{
    /**
    * Contains the plain text part of the email
    * @var string
    */
    var $_text;

    /**
    * Contains the html part of the email
    * @var string
    */
    var $_html;

    /**
    * List of the attached images
    * @var array
    */
    var $_embeddedImages;

    /**
    * List of the attachements
    * @var array
    */
    var $_attachments;

    /**
    * Build parameters
    * @var array
    */
    var $_buildParams;

    /**
    * Headers for the mail
    * @var array
    */
    var $_headers;

    /**
    * Headers for the mail (Lowercased names, internal use only)
    * @var array
    */
    var $_headers_lc;
    
    /**
    * Holds the encoded mime body
    * @var string
    */
    var $_mimeBody;
    
    /**
    * Whether the build() method has been called
    * @var boolean
    */
    var $_isBuilt;

    /**
    * Constructor function
    *
    * @access public
    */
    function Mail_Mime($crlf = "\r\n")
    {
        if (!defined('MAIL_MIME_CRLF')) {
            define('MAIL_MIME_CRLF', $crlf, true);
        }

        $this->_buildParams = array('text_encoding' => MAIL_MIME_PART_7BIT,
                                    'html_encoding' => MAIL_MIME_PART_QPRINT,
                                    'text_wrap'     => 998,
                                    'html_charset'  => 'ISO-8859-1',
                                    'text_charset'  => 'ISO-8859-1',
                                    'head_charset'  => 'ISO-8859-1'
                                   );

        $this->_text           = null;
        $this->_html           = null;
        $this->_headers        = array();
        $this->_headers_lc     = array();
        $this->_attachments    = array();
        $this->_embeddedImages = array();
        $this->_mimeBody       = null;
        $this->_isBuilt        = false;
    }

    /**
    * Loads a file given its filename. For use with
    * set text/html/attachments/images etc.
    *
    * @param string $filename The file to load
    */
    function file2str($filename)
    {
        if (file_exists($filename) AND is_readable($filename)) {
            if (function_exists('file_get_contents')) {
                return file_get_contents($filename);
            } else {
                $return = '';
                $fp = fopen($filename, 'rb');
                while (!feof($fp)) {
                    $return .= fread($fp, 1024);
                }
                fclose($fp);

                return $return;
            }
        } else {
            return false;
        }
    }

    /**
    * Returns the encoded mime content. If build() has not
    * been called, it will be called implicitly.
    *
    * @return string The mimebody string
    * @access public
    */
    function getMimeBody()
    {
        if (!$this->_isBuilt) {
            $this->build();
        }

        return $this->_mimeBody;
    }
    
    /**
    * Returns the headers. Meant for use after build() has
    * been called to retrieve the headers, but this is not
    * enforced.
    *
    * @return array  The headers (associative)
    * @access public
    */
    function getHeaders()
    {
        return $this->_headers;
    }

    /**
    * Get the text version of the headers
    * (usefull if you want to use the PHP mail() function)
    *
    * @return string Plain text headers
    * @access public
    */
    function getTxtHeaders()
    {
        foreach ($this->_headers as $key => $val) {
            $ret[] = $key . ': ' . $val;
        }

        return implode(MAIL_MIME_CRLF, $ret);
    }

    /**
    * Accessor function to set the body text. Body text is used if
    * it's not an HTML mail being sent or else is used to fill the
    * text/plain part that emails clients who don't support
    * HTML should show.
    *
    * @param  string $data   The text string
    * @param  bool   $append If true the text or file is appended to the
    *                        existing body, else the old body is overwritten
    * @access public
    */
    function setText($text, $append = false)
    {
        $append ? $this->_text .= $text : $this->_text = $text;
    }

    /**
    * Sets the HTML for the email. Turns the mail into a multipart/alternative
    * unless there are attachments/embedded images.
    *
    * @param  string $data The HTML to set
    * @access public
    */
    function setHTML($html)
    {
        $this->_html = $html;
    }

    /**
    * Accessor function to set the text encoding
    * 
    * @param  string $encoding Sets the encoding for the text part
    * @access public
    */
    function setTextEncoding($encoding = '7bit')
    {
        $this->_buildParams['text_encoding'] = $encoding;
    }

    /**
    * Accessor function to set the HTML encoding
    * 
    * @param  string $encoding Sets the encoding for the HTML part
    * @access public
    */
    function setHTMLEncoding($encoding = 'quoted-printable')
    {
        $this->_buildParams['html_encoding'] = $encoding;
    }

    /**
    * Accessor function to set the text charset
    * 
    * @param  string $charset Sets the character set for the text part
    * @access public
    */
    function setTextCharset($charset = 'ISO-8859-1')
    {
        $this->_buildParams['text_charset'] = $charset;
    }

    /**
    * Accessor function to set the HTML charset
    * 
    * @param  string $charset Sets the character set for the HTML part
    * @access public
    */
    function setHTMLCharset($charset = 'ISO-8859-1')
    {
        $this->_buildParams['html_charset'] = $charset;
    }

    /**
    * Accessor function to set the header encoding charset
    * 
    * @param  string $charset Sets the character set for headers
    * @access public
    */
    function setHeadCharset($charset = 'ISO-8859-1')
    {
        $this->_buildParams['head_charset'] = $charset;
    }

    /**
    * Accessor function to set the text wrap count
    * 
    * @param  string $count The maximum line length to use, defaults to 998
    * @access public
    */
    function setTextWrap($count = 998)
    {
        $this->_buildParams['text_wrap'] = $count;
    }

    /**
    * Accessor to set a header
    * 
    * @param  string $name  The name of the header
    * @param  string $value The value/contents of the header
    * @access public
    */
    function setHeader($name, $value)
    {
        $this->_headers[$name] = $this->_headers_lc[strtolower($name)] = $this->_encodeHeader($value, $this->_buildParams['head_charset']);
    }

    /**
    * Accessor to add a Subject: header
    * 
    * @param  string $subject The subject to set
    * @access public
    */
    function setSubject($subject)
    {
        $this->setHeader('Subject', $subject);
    }

    /**
    * Accessor to add a From: header
    * 
    * @param  string $from The From address(es) to use
    * @access public
    */
    function setFrom($from)
    {
        $this->setHeader('From', $from);
    }

    /**
    * Accessor to set the return path
    * 
    * @param  string $returnPath The return path address to use
    * @access public
    */
    function setReturnPath($returnPath)
    {
        $this->setHeader('Return-Path', $returnPath);
    }

    /**
    * Accessor to add a Cc: header
    * 
    * @param  string $cc The Cc header address(es)
    * @access public
    */
    function setCc($cc)
    {
        $this->setHeader('Cc', $cc);
    }

    /**
    * Accessor to add a Bcc: header
    * 
    * @param  string $bcc The Bcc header address(es)
    * @access public
    */
    function setBcc($bcc)
    {
        $this->setHeader('Bcc', $bcc);
    }

    /**
    * Adds an image to the list of embedded images.
    *
    * @param  string $data   The image data
    * @param  string $name   The filename of the image. Only use if $file is the image data
    * @param  string $c_type The content type
    * @access public
    */
    function addHTMLImage($data, $name = '', $c_type = 'application/octet-stream')
    {
        $this->_embeddedImages[] = array('body'   => $data,
                                         'name'   => $name,
                                         'c_type' => $c_type,
                                         'cid'    => md5(uniqid(time()))
                                         );
    }

    /**
    * Adds a file to the list of attachments.
    *
    * @param  string $file     The attachment data
    * @param  string $name     The filename of the attachment
    * @param  string $c_type   The content type to use
    * @param  string $encoding The content-transfer-encoding to use
    * @access public
    */
    function addAttachment($data, $name = '', $c_type = 'application/octet-stream', $encoding = 'base64')
    {
        $this->_attachments[] = array('body'     => $data,
                                      'name'     => $name,
                                      'c_type'   => $c_type,
                                      'encoding' => $encoding
                                     );
    }

    /**
    * Adds a text subpart to the mimePart object and
    * returns it during the build process.
    *
    * @param mixed    The object to add the part to, or
    *                 null if a new object is to be created.
    * @return object  The text mimePart object
    * @access private
    */
    function &_addTextPart(&$obj){

        $textPart = new Mail_Mime_Part();

        $ctype    = 'text/plain' . (!empty($this->_buildParams['text_charset']) ? '; charset="' . $this->_buildParams['text_charset'] . '"' : '');
        $textPart->addHeader('Content-Type', $ctype);
        $textPart->setBody($this->_text);

        if (!empty($this->_buildParams['text_encoding'])) {
            $textPart->addHeader('Content-Transfer-Encoding', $this->_buildParams['text_encoding']);
        }

        if (is_object($obj)) {
            return $obj->addSubpart($textPart);
        } else {
            return $textPart;
        }
    }

    /**
    * Adds a html subpart to the mimePart object and
    * returns it during the build process.
    *
    * @param mixed    The object to add the part to, or
    *                 null if a new object is to be created.
    * @return object  The html mimePart object
    * @access private
    */
    function &_addHtmlPart(&$obj)
    {
        $htmlPart = new Mail_Mime_Part();

        $ctype    = 'text/html' . (!empty($this->_buildParams['html_charset']) ? '; charset="' . $this->_buildParams['html_charset'] . '"' : '');
        $htmlPart->addHeader('Content-Type', $ctype);
        $htmlPart->setBody($this->_html);

        if (!empty($this->_buildParams['html_encoding'])) {
            $htmlPart->addHeader('Content-Transfer-Encoding', $this->_buildParams['html_encoding']);
        }

        if (is_object($obj)) {
            return $obj->addSubpart($htmlPart);
        } else {
            return $htmlPart;
        }
    }

    /**
    * Creates a new mimePart object, using multipart/mixed as
    * the initial content-type and returns it during the
    * build process.
    *
    * @return object  The multipart/mixed mimePart object
    * @access private
    */
    function &_addMixedPart()
    {
        $obj = new Mail_Mime_Part();
        $obj->addHeader('Content-Type', 'multipart/mixed');

        return $obj;
    }

    /**
    * Adds a multipart/alternative part to a mimePart
    * object, (or creates one), and returns it  during
    * the build process.
    *
    * @param mixed    The object to add the part to, or
    *                 null if a new object is to be created.
    * @return object  The multipart/mixed mimePart object
    * @access private
    */
    function &_addAlternativePart(&$obj)
    {
        $altPart = new Mail_Mime_Part();
        $altPart->addHeader('Content-Type', 'multipart/alternative');

        if (is_object($obj)) {
            return $obj->addSubpart($altPart);
        } else {
            return $altPart;
        }
    }

    /**
    * Adds a multipart/related part to a mimePart
    * object, (or creates one), and returns it  during
    * the build process.
    *
    * @param mixed    The object to add the part to, or
    *                 null if a new object is to be created.
    * @return object  The multipart/mixed mimePart object
    * @access private
    */
    function &_addRelatedPart(&$obj)
    {
        $relPart = new Mail_Mime_Part();
        $relPart->addHeader('Content-Type', 'multipart/related');

        if (is_object($obj)) {
            return $obj->addSubpart($relPart);
        } else {
            return $altPart;
        }
    }

    /**
    * Adds an html image subpart to a mimePart object
    * during the build process.
    *
    * @param  object  The mimePart to add the image to
    * @param  array   The image information
    * @access private
    */
    function _addHtmlImagePart(&$obj, $value)
    {
        $imagePart = new Mail_Mime_Part();
        $imagePart->addHeader('Content-Type', $value['c_type']);
        $imagePart->addHeader('Content-Transfer-Encoding', MAIL_MIME_PART_BASE64);
        $imagePart->addHeader('Content-Disposition', 'inline; filename="' . $value['name'] . '"');
        $imagePart->addHeader('Content-ID', '<' . $value['cid'] . '>');
        $imagePart->setBody($value['body']);

        $obj->addSubpart($imagePart);
    }

    /**
    * Adds an attachment subpart to a mimePart object
    * during the build process.
    *
    * @param  object  The mimePart to add the image to
    * @param  array   The attachment information
    * @access private
    */
    function &_addAttachmentPart(&$obj, $value)
    {
        $attachPart = new Mail_Mime_Part();
        $attachPart->addHeader('Content-Type', $value['c_type']);
        $attachPart->addHeader('Content-Transfer-Encoding', $value['encoding']);
        $attachPart->addHeader('Content-Disposition', 'attachment; filename="' . $value['name'] . '"');
        $attachPart->setBody($value['body']);

        $obj->addSubpart($attachPart);
    }

    /**
    * Builds the multipart message from the list ($this->_parts) and
    * returns the mime content.
    *
    * @return string The mime content
    * @access public
    */
    function build()
    {
        $this->_isBuilt = true;

        // Replace image names/paths with content IDs
        if (!empty($this->_embeddedImages) AND isset($this->_html)) {
            foreach ($this->_embeddedImages as $value) {
                $this->_html = str_replace($value['name'], 'cid:'.$value['cid'], $this->_html);
            }
        }

        $null        = null;
        $attachments = !empty($this->_attachments);
        $html_images = !empty($this->_embeddedImages);
        $html        = !is_null($this->_html);
        $text        = !is_null($this->_text);

        switch (true) {
            case $text AND !$html AND !$attachments:
                $message = &$this->_addTextPart($null);
                break;

            case !$text AND !$html AND $attachments:
                $message = &$this->_addMixedPart();

                for ($i = 0; $i < count($this->_attachments); $i++) {
                    $this->_addAttachmentPart($message, $this->_attachments[$i]);
                }
                break;

            case $text AND !$html AND $attachments:
                $message = &$this->_addMixedPart();
                $this->_addTextPart($message);

                for ($i = 0; $i < count($this->_attachments); $i++) {
                    $this->_addAttachmentPart($message, $this->_attachments[$i]);
                }
                break;

            case $html AND !$attachments AND !$html_images:
                if ($text) {
                    $message = &$this->_addAlternativePart($null);
                    $this->_addTextPart($message);
                    $this->_addHtmlPart($message);

                } else {
                    $message = &$this->_addHtmlPart($null);
                }
                break;

            case $html AND !$attachments AND $html_images:
                if ($text) {
                    $message = &$this->_addAlternativePart($null);
                    $this->_addTextPart($message);
                    $related = &$this->_addRelatedPart($message);
                } else {
                    $message = &$this->_addRelatedPart($null);
                    $related = &$message;
                }
                $this->_addHtmlPart($related);
                for ($i = 0; $i < count($this->_embeddedImages); $i++) {
                    $this->_addHtmlImagePart($related, $this->_embeddedImages[$i]);
                }
                break;

            case $html AND $attachments AND !$html_images:
                $message = &$this->_addMixedPart();
                if ($text) {
                    $alt = &$this->_addAlternativePart($message);
                    $this->_addTextPart($alt);
                    $this->_addHtmlPart($alt);
                } else {
                    $this->_addHtmlPart($message);
                }
                for ($i = 0; $i < count($this->_attachments); $i++) {
                    $this->_addAttachmentPart($message, $this->_attachments[$i]);
                }
                break;

            case $html AND $attachments AND $html_images:
                $message = &$this->_addMixedPart();
                if ($text) {
                    $alt = &$this->_addAlternativePart($message);
                    $this->_addTextPart($alt);
                    $rel = &$this->_addRelatedPart($alt);
                } else {
                    $rel = &$this->_addRelatedPart($message);
                }
                $this->_addHtmlPart($rel);
                for ($i = 0; $i < count($this->_embeddedImages); $i++) {
                    $this->_addHtmlImagePart($rel, $this->_embeddedImages[$i]);
                }
                for ($i = 0; $i < count($this->_attachments); $i++) {
                    $this->_addAttachmentPart($message, $this->_attachments[$i]);
                }
                break;

        }

        if (isset($message)) {
            $output = $message->encode();
            $this->setHeader('MIME-Version', '1.0');
            $this->_headers = array_merge($this->_headers, $output['headers']);
            $this->_mimeBody = $output['body'];
            return true;
        }
        
        return false;
    }

    /**
    * Encodes a header as per RFC2047
    *
    * @param  string  $input   The header data to encode
    * @param  string  $charset The charset to use
    * @return string           Encoded data
    * @access private
    */
    function _encodeHeader($input, $charset = 'ISO-8859-1')
    {
        preg_match_all('/(\w*[\x80-\xFF]+\w*)/', $input, $matches);
        foreach ($matches[1] as $value) {
            $replacement = preg_replace('/([\x80-\xFF])/e', '"=" . strtoupper(dechex(ord("\1")))', $value);
            $input = str_replace($value, '=?' . $charset . '?Q?' . $replacement . '?=', $input);
        }
        
        return $input;
    }
    
    /**
    * Returns the message as an RFC822 message. Can
    * be used to attach messages to messages.
    * 
    * @return string The RFC822 message
    */
    function getRFC822()
    {
        // Make up the date header as according to RFC822
        if (empty($this->_headers['Date'])) {
            $this->setHeader('Date', date('D, j M Y H:i:s O'));
        }

        // Message built?
        if (!$this->_isBuilt) {
            $this->build();
        }

        return $this->getTxtHeaders() . MAIL_MIME_CRLF . MAIL_MIME_CRLF . $this->_mimeBody;
    }

    /**
    * Function which sends the mail
    *
    * @param  string  $method     Method to use - mail() or SMTP
    * @param  array   $recipients The recipient list
    * @access private
    */
    function _send($method, $recipients, $smtpParams = null)
    {
        // Message built?
        if (!$this->_isBuilt) {
            $this->build();
        }

        switch ($method) {
            case 'mail':
                // Handle subject
                $subject = '';
                if (!empty($this->_headers_lc['subject'])) {
                    $subject = $this->_headers_lc['subject'];
                    unset($this->_headers['Subject']);
                }

                // Get flat representation of headers
                foreach ($this->_headers as $name => $value) {
                    $headers[] = $name . ': ' . $value;
                }

                // Handle recipients
                $to = $this->_encodeHeader(implode(', ', $recipients), $this->_buildParams['head_charset']);

                if (!empty($this->_headers_lc['return-path'])) {
                    $result = mail($to, $subject, $this->_mimeBody, implode(MAIL_MIME_CRLF, $headers), '-f' . $this->_headers_lc['return-path']);
                } else {
                    $result = mail($to, $subject, $this->_mimeBody, implode(MAIL_MIME_CRLF, $headers));
                }
                
                // Reset the subject in case mail is resent
                if ($subject !== '') {
                    $this->_headers['Subject'] = $subject;
                }
                
                // Return
                return $result;
                break;

            case 'smtp':
                require_once('Mail.php');
                require_once('Mail/RFC822.php');
                
                $smtpObj = &Mail::factory('smtp', $smtpParams);

                // Send it
                return $smtpObj->send($recipients, $this->_headers, $this->_mimeBody);
                break;
        }
    }
    
    /**
    * Sends using the mail() function
    *
    * @param  array  $recipients The recipient list
    * @access public
    */
    function send($recipients)
    {
        $this->_send('mail', $recipients);
    }

    /**
    * Sends via SMTP
    *
    * @param  array  $recipients The recipient list
    * @param  array  $smtpParams Parameters to pass to the SMTP object
    *                            Can include:
    *                             o host
    *                             o port
    *                             o auth
    *                             o username
    *                             o password
    * @access public
    */
    function sendSMTP($recipients, $smtpParams = array())
    {
        $this->_send('smtp', $recipients, $smtpParams);
    }

} // End of class
?>
