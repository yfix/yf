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
   - send mail using POP before SMTP (Mail Proxy) method
*/

// manage errors
error_reporting(E_ALL); // php errors
define('DISPLAY_XPM4_ERRORS', true); // display XPM4 errors

// path to 'POP3.php' and 'SMTP.php' files from XPM4 package
require_once '../POP3.php';
require_once '../SMTP.php';

$f = 'username@hostname.net'; // from mail address / account username
$t = 'client@destination.net'; // to mail address
$p = 'password'; // account password

// standard mail message RFC2822
$m = 'From: '.$f."\r\n".
     'To: '.$t."\r\n".
     'Subject: test'."\r\n".
     'Content-Type: text/plain'."\r\n\r\n".
     'Text message.';

// connect to 'pop3.hostname.net' POP3 server address with authentication username '$f' and password '$p'
$p = POP3::Connect('pop3.hostname.net', $f, $p) or die(print_r($_RESULT));
// connect to 'smtp.hostname.net' SMTP server address
$c = SMTP::Connect('smtp.hostname.net') or die(print_r($_RESULT));

// send mail
$s = SMTP::Send($c, array($t), $m, $f);

// print result
if ($s) echo 'Sent !';
else print_r($_RESULT);

// disconnect from SMTP server
SMTP::Disconnect($c);
// disconnect from POP3 server
POP3::Disconnect($p);

?>