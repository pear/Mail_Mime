--TEST--
Bug #21027  Calendar support along with attachments and html images
--SKIPIF--
--FILE--
<?php
require_once('Mail/mime.php');

$txtBody = 'Hi, this is Plain Text Body.';
$htmlBody = '<div>This is HTML body.</div>';
$icsText = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//icalcreator//NONSGML iCalcreator 2.22//
METHOD:REQUEST
BEGIN:VEVENT
UID:77@localhost
DTSTAMP:20160208T170811Z
ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=
 TRUE;CN=Jacob Alvarez:MAILTO:fake1@mailinator.com
CREATED:20160208T170810Z
DTSTART:20160215T180000Z
DTEND:20160215T190000Z
ORGANIZER;CN=-:MAILTO:fake2@mailinator.com
SEQUENCE:1
STATUS:CONFIRMED
SUMMARY:Prueba 69
TRANSP:OPAQUE
URL:http://localhost/event/77
END:VEVENT
END:VCALENDAR';

function printPartsStartAndEnd($body) {
    $matches  = [];
    preg_match_all('/--(=_[a-z0-9]+)--|Content-Type: ([^;\r\n]+)/', $body, $matches);
    $tab = "    ";
    foreach ($matches[0] as $match){
        if (strpos($match, '--') === false) {
            printf("%s%s\n", $tab, $match);
            if (stripos($match, "multipart")) {
                $tab .= "    ";
            }
        } else {
            $tab = substr($tab, 0, -4);
            printf("%sEnd part\n", $tab);
        }
    }
}

function printHeaderContentType($headers) {
    $headerContentType = [];
    preg_match('/([^;\r\n]+)/', $headers['Content-Type'], $headerContentType);
    printf("Content-Type: %s\n", $headerContentType[0]);
}

print "TEST: text\n";
$mime = new Mail_mime();
$mime->setTXTBody($txtBody);
$headers = $mime->headers();
$body = $mime->get();
printHeaderContentType($headers);
printPartsStartAndEnd($body);
print("\n");

print "TEST: html\n";
$mime = new Mail_mime();
$mime->setHTMLBody($htmlBody);
$headers = $mime->headers();
$body = $mime->get();
printHeaderContentType($headers);
printPartsStartAndEnd($body);
print("\n");

print "TEST: attachments\n";
$mime = new Mail_mime();
$mime->addAttachment($icsText, 'application/ics', 'invite.ics', false);
$headers = $mime->headers();
$body = $mime->get();
printHeaderContentType($headers);
printPartsStartAndEnd($body);
print("\n");

print "TEST: text + attachments\n";
$mime = new Mail_mime();
$mime->setTXTBody($txtBody);
$mime->addAttachment($icsText, 'application/ics', 'invite.ics', false);
$headers = $mime->headers();
$body = $mime->get();
printHeaderContentType($headers);
printPartsStartAndEnd($body);
print("\n");

print "TEST: html + attachments\n";
$mime = new Mail_mime();
$mime->setHTMLBody($htmlBody);
$mime->addAttachment($icsText, 'application/ics', 'invite.ics', false);
$headers = $mime->headers();
$body = $mime->get();
printHeaderContentType($headers);
printPartsStartAndEnd($body);
print("\n");

print "TEST: html + inline images\n";
$mime = new Mail_mime();
$mime->setHTMLBody($htmlBody);
$mime->addHTMLImage("aaaaaaaaaa", 'image/gif', 'image.gif', false, 'contentid');
$headers = $mime->headers();
$body = $mime->get();
printHeaderContentType($headers);
printPartsStartAndEnd($body);
print("\n");

print("TEST: txt, html and attachment\n");
$mime = new Mail_mime();
$mime->setTXTBody($txtBody);
$mime->setHTMLBody($htmlBody);
$mime->addAttachment("test", 'application/octet-stream', 'attachment', false);
$headers = $mime->headers();
$body = $mime->get();
printHeaderContentType($headers);
printPartsStartAndEnd($body);
print("\n");

print "TEST: calendar\n";
$mime = new Mail_mime();
$mime->setCalendarBody($icsText);
$headers = $mime->headers();
$body = $mime->get();
printHeaderContentType($headers);
printPartsStartAndEnd($body);
print("\n");

print "TEST: txt + calendar\n";
$mime->setTXTBody($txtBody);
$headers = $mime->headers();
$body = $mime->get();
printHeaderContentType($headers);
printPartsStartAndEnd($body);
print("\n");

print "TEST: txt, html, calendar\n";
$mime = new Mail_mime();
$mime->setTXTBody($txtBody);
$mime->setHTMLBody($htmlBody);
$mime->setCalendarBody($icsText);
$headers = $mime->headers();
$body = $mime->get();
printHeaderContentType($headers);
printPartsStartAndEnd($body);
print("\n");

print "TEST: txt, html + html images, and calendar\n";
$mime = new Mail_mime();
$mime->setTXTBody($txtBody);
$mime->setHTMLBody($htmlBody);
$mime->addHTMLImage('testimage', 'image/gif', "bus.gif", false);
$mime->setCalendarBody($icsText);
$headers = $mime->headers();
$body = $mime->get();
printHeaderContentType($headers);
printPartsStartAndEnd($body);
print("\n");

print("TEST: txt, html, calendar and attachment\n");
$mime = new Mail_mime();
$mime->setTXTBody($txtBody);
$mime->setHTMLBody($htmlBody);
$mime->setCalendarBody($icsText);
$mime->addAttachment("test", 'application/octet-stream', 'attachment', false);
$headers = $mime->headers();
$body = $mime->get();
printHeaderContentType($headers);
printPartsStartAndEnd($body);
print("\n");

print "TEST: txt, html + html images, calendar, and attachment\n";
$mime = new Mail_mime();
$mime->setTXTBody($txtBody);
$mime->setHTMLBody($htmlBody);
$mime->addHTMLImage('testimage', 'image/gif', "bus.gif", false);
$mime->setCalendarBody($icsText);
$mime->addAttachment($icsText, 'application/ics', 'invite.ics', false);
$headers = $mime->headers();
$body = $mime->get();
printHeaderContentType($headers);
printPartsStartAndEnd($body);
print("\n");
?>
--EXPECT--
TEST: text
Content-Type: text/plain

TEST: html
Content-Type: text/html

TEST: attachments
Content-Type: multipart/mixed
    Content-Type: application/ics
End part

TEST: text + attachments
Content-Type: multipart/mixed
    Content-Type: text/plain
    Content-Type: application/ics
End part

TEST: html + attachments
Content-Type: multipart/mixed
    Content-Type: text/html
    Content-Type: application/ics
End part

TEST: html + inline images
Content-Type: multipart/related
    Content-Type: text/html
    Content-Type: image/gif
End part

TEST: txt, html and attachment
Content-Type: multipart/mixed
    Content-Type: multipart/alternative
        Content-Type: text/plain
        Content-Type: text/html
    End part
    Content-Type: application/octet-stream
End part

TEST: calendar
Content-Type: text/calendar

TEST: txt + calendar
Content-Type: multipart/alternative
    Content-Type: text/plain
    Content-Type: text/calendar
End part

TEST: txt, html, calendar
Content-Type: multipart/alternative
    Content-Type: text/plain
    Content-Type: text/html
    Content-Type: text/calendar
End part

TEST: txt, html + html images, and calendar
Content-Type: multipart/alternative
    Content-Type: text/plain
    Content-Type: multipart/related
        Content-Type: text/html
        Content-Type: image/gif
    End part
    Content-Type: text/calendar
End part

TEST: txt, html, calendar and attachment
Content-Type: multipart/mixed
    Content-Type: multipart/alternative
        Content-Type: text/plain
        Content-Type: text/html
        Content-Type: text/calendar
    End part
    Content-Type: application/octet-stream
End part

TEST: txt, html + html images, calendar, and attachment
Content-Type: multipart/mixed
    Content-Type: multipart/alternative
        Content-Type: text/plain
        Content-Type: multipart/related
            Content-Type: text/html
            Content-Type: image/gif
        End part
        Content-Type: text/calendar
    End part
    Content-Type: application/ics
End part
