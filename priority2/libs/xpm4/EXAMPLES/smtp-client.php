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
   - send mail directly to client (without MTA support)
   - connect to internet using IP '127.0.0.1'
   - set hostname 'localdomain.net' for EHLO/HELO SMTP dialog
   - set return-path in 'MAIL FROM' SMTP dialog
*/

// manage errors
error_reporting(E_ALL); // php errors
define('DISPLAY_XPM4_ERRORS', true); // display XPM4 errors

// path to 'SMTP.php' file from XPM4 package
require_once '../SMTP.php';

$f = 'me@mydomain.net'; // from mail address
$t = 'client@destination.net'; // to mail address
$p = 'my@address.net'; // return-path

// standard mail message RFC2822
$m = 'From: '.$f."\r\n".
	 'To: '.$t."\r\n".
	 'Subject: test'."\r\n".
	 'Content-Type: text/plain'."\r\n\r\n".
	 'Text message.';

// get client hostname
$h = explode('@', $t);

// optional, connect to the internet using IP '127.0.0.1'
$r = stream_context_create(array('socket' => array('bindto' => '127.0.0.1:0')));

// connect to SMTP server (direct) from MX hosts list to port '25' and timeout '10' secounds
// optional, set hostname 'localdomain.net' for EHLO/HELO SMTP dialog
$c = SMTP::mxconnect($h[1], 25, 10, 'localdomain.net', $r) or die(print_r($_RESULT));

// send mail and set return-path '$p' in 'MAIL FROM' SMTP dialog
$s = SMTP::send($c, array($t), $m, $p);

// print result
if ($s) echo 'Sent !';
else print_r($_RESULT);

// disconnect
SMTP::disconnect($c);

?>