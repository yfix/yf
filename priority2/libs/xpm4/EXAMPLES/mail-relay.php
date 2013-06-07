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
   - set HTML message
   - send mail relay using Gmail MTA via SSL (TLS encryption) and authentication
   - print result
*/

// manage errors
error_reporting(E_ALL); // php errors
define('DISPLAY_XPM4_ERRORS', true); // display XPM4 errors

// path to 'MAIL.php' file from XPM4 package
require_once '../MAIL.php';

// initialize MAIL class
$m = new MAIL;
// set from address
$m->From('username@gmail.com');
// add to address
$m->AddTo('client@destination.net');
// set subject
$m->Subject('Hello World!');
// set HTML message
$m->Html('<b>HTML</b> <u>message</u>.');

// connect to MTA server 'smtp.gmail.com' port '465' via SSL ('tls' encryption) with authentication: 'username@gmail.com'/'password'
// make sure you have OpenSSL module (extension) enable on your php configuration
$c = $m->Connect('smtp.gmail.com', 465, 'username@gmail.com', 'password', 'tls') or die(print_r($m->Result));

// send mail relay using the '$c' resource connection
echo $m->Send($c) ? 'Mail sent !' : 'Error !';

// disconnect from server
$m->Disconnect();

// optional for debugging ----------------
echo '<br /><pre>';
// print History
print_r($m->History);
// calculate time
list($tm1, $ar1) = each($m->History[0]);
list($tm2, $ar2) = each($m->History[count($m->History)-1]);
echo 'The process took: '.(floatval($tm2)-floatval($tm1)).' seconds.</pre>';

?>