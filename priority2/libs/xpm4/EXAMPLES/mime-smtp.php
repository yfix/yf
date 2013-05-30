<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *                                                                                         *
 *  XPertMailer is a PHP Mail Class that can send and read messages in MIME format.        *
 *  This file is part of the XPertMailer package (http://xpertmailer.sourceforge.net/)     *
 *  Copyright (C) 2007 Tanase Laurentiu Iulian                                             *
 *                                                                                         *
 *  This library is free software; you can redistribute it and/or modify it under the      *
 *  terms of the GNU Lesser General Public License as published by the Free Software       *
 *  Foundation; either version 2.1 of the License, or (at your option) any later version.  *
 *                                                                                         *
 *  This library is distributed in the hope that it will be useful, but WITHOUT ANY        *
 *  WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A        *
 *  PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.        *
 *                                                                                         *
 *  You should have received a copy of the GNU Lesser General Public License along with    *
 *  this library; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, *
 *  Fifth Floor, Boston, MA 02110-1301, USA                                                *
 *                                                                                         *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/* Purpose:
   - set 'text/plain' and 'text/html' version of message
   - send mail directly to client (without MTA support)
   - print result
*/

// manage errors
error_reporting(E_ALL); // php errors
define('DISPLAY_XPM4_ERRORS', true); // display XPM4 errors

// path to 'SMTP.php' file from XPM4 package
require_once '../SMTP.php';

// CONFIGURATION ------------------
$from = 'me@mydomain.net'; // from mail address
$to   = 'client@destination.net'; // to mail address
$subj = 'Hello World!'; // mail subject
$text = 'Text version of message.'; // text/plain version of message
$html = '<b>HTML</b> version of <u>message</u>.'; // text/html version of message
// CONFIGURATION ------------------

// set text/plain version of message
$msg1 = MIME::message($text, 'text/plain');
// set text/html version of message
$msg2 = MIME::message($html, 'text/html');
// compose message in MIME format
$mess = MIME::compose($msg1, $msg2);
// standard mail message RFC2822
$body = 'From: '.$from."\r\n".
		'To: '.$to."\r\n".
		'Subject: '.$subj."\r\n".
		$mess['header']."\r\n\r\n".
		$mess['content'];

// get client hostname
$expl = explode('@', $to);

// connect to SMTP server (direct) from MX hosts list
$conn = SMTP::mxconnect($expl[1]) or die(print_r($_RESULT));

// send mail
$sent = SMTP::send($conn, array($to), $body, $from);

// print result
if ($sent) echo 'Sent !';
else print_r($_RESULT);

// disconnect from SMTP server
SMTP::disconnect($conn);

?>