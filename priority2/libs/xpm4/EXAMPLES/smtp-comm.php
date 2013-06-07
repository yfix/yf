<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *																						 *
 *  XPertMailer is a PHP Mail Class that can send and read messages in MIME format.		*
 *  This file is part of the XPertMailer package (http://xpertmailer.sourceforge.net/)	 *
 *  Copyright (C) 2007 Tanase Laurentiu Iulian											 *
 *																						 *
 *  This library is free software; you can redistribute it and/or modify it under the	  *
 *  terms of the GNU Lesser General Public License as published by the Free Software	   *
 *  Foundation; either version 2.1 of the License, or (at your option) any later version.  *
 *																						 *
 *  This library is distributed in the hope that it will be useful, but WITHOUT ANY		*
 *  WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A		*
 *  PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.		*
 *																						 *
 *  You should have received a copy of the GNU Lesser General Public License along with	*
 *  this library; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, *
 *  Fifth Floor, Boston, MA 02110-1301, USA												*
 *																						 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/* Purpose:
   - connect to MTA server (Gmail) via SSL (TLS encryption)
   - send mail relay using step-by-step SMTP commands
*/

// manage errors
error_reporting(E_ALL); // php errors
define('DISPLAY_XPM4_ERRORS', true); // display XPM4 errors

// path to 'SMTP.php' file from XPM4 package
require_once '../SMTP.php';

$f = 'username@gmail.com'; // from (Gmail mail address)
$t = 'client@destination.net'; // to mail address
$p = 'password'; // Gmail password

// standard mail message RFC2822
$m = 'From: '.$f."\r\n".
	 'To: '.$t."\r\n".
	 'Subject: test'."\r\n".
	 'Content-Type: text/plain'."\r\n\r\n".
	 'Text message.';

// connect to 'smtp.gmail.com' via SSL (TLS encryption) using port '465' and timeout '10' secounds
// make sure you have OpenSSL module (extension) enable on your php configuration
$c = fsockopen('tls://smtp.gmail.com', 465, $errno, $errstr, 10) or die($errstr);
// expect response code '220'
if (!SMTP::recv($c, 220)) die(print_r($_RESULT));
// EHLO/HELO
if (!SMTP::ehlo($c, 'localhost')) SMTP::helo($c, 'localhost') or die(print_r($_RESULT));
// AUTH LOGIN/PLAIN
if (!SMTP::auth($c, $f, $p, 'login')) SMTP::auth($c, $f, $p, 'plain') or die(print_r($_RESULT));
// MAIL FROM
SMTP::from($c, $f) or die(print_r($_RESULT));
// RCPT TO
SMTP::to($c, $t) or die(print_r($_RESULT));
// DATA
SMTP::data($c, $m) or die(print_r($_RESULT));
// RSET, optional if you need to send another mail using this connection '$c'
// SMTP::rset($c) or die(print_r($_RESULT));
// QUIT
SMTP::quit($c);
// close connection
@fclose($c);

echo 'Sent !';

?>