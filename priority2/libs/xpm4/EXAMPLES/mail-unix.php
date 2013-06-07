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
   - send a mail message using 'SendMail' Unix program 
*/

// manage errors
error_reporting(E_ALL); // php errors
define('DISPLAY_XPM4_ERRORS', true); // display XPM4 errors

// path to 'MAIL.php' file from XPM4 package
require_once '../MAIL.php';

// initialize MAIL class
$m = new MAIL;
// set from address
$m->From('me@myaddress.net');
// optional, set return-path
// $m->Path('my@address.net');
// add to address
$m->AddTo('client@destination.net');
// set subject
$m->Subject('Hello World!');
// set text message
$m->Text('Text message.');

// optional, you can change the execution program for 'sendmail', by default is '/usr/sbin/sendmail'
// $m->SendMail = '/path-to/your-mail-program';
// send mail using 'SendMail' Unix program, or type 'qmail' to select 'QMail' program
echo $m->Send('sendmail') ? 'Mail sent !' : 'Error !';

// optional, print History for debugging
print_r($m->History);

?>