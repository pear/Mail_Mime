--TEST--
Bug #21027  Calendar support along with attachments and html images
--SKIPIF--
--FILE--
<?php
require_once('Mail/mime.php');

$txtBody = 'Hi,

I would like to invite you to: My Event.

It has been scheduled for Mon Feb 15, 2016 at 06:00 PM (GMT) and it will la=
st about 60 minutes.

Please confirm your attendance.';

$htmlBody = '<div>
<div>Hi,</div>
<div><br></div>
<div>I would like to invite you to: My event.</div>
<div><br></div>
<div>It has been scheduled for Mon Feb 15, 2016 at 06:00 PM (GMT) and it will last about 60 minutes.</div>
<div><br></div>
<div>Please confirm your attendance.</div>';

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

print "TEST: calendar\n";
$mime = new Mail_mime();
$mime->setCalendarBody($icsText);
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
$mime->addAttachment("test", 'application/octec-stream', 'attachment', false);
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
TEST: calendar
Content-Type: text/calendar

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
    Content-Type: application/octec-stream
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
