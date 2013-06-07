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
   - connect to POP3 server (Gmail) via SSL (SSL encryption)
   - print the source of last mail message
*/

// manage errors
error_reporting(E_ALL); // php errors
define('DISPLAY_XPM4_ERRORS', true); // display XPM4 errors

// path to 'POP3.php' file from XPM4 package
require_once '../POP3.php';

// connect to POP3 server via SSL (SSL encryption) with authentication on port '995' and timeout '10' secounds
// make sure you have OpenSSL module (extension) enable on your php configuration
$c = POP3::connect('pop.gmail.com', 'username@gmail.com', 'password', 995, 'ssl', 10) or die(print_r($_RESULT));
// STAT
$s = POP3::pStat($c) or die(print_r($_RESULT));
// $i - total number of messages, $b - total bytes
list($i, $b) = each($s);
if ($i > 0) { // if we have messages
	// RETR
	$r = POP3::pRetr($c, $i) or die(print_r($_RESULT)); // <- get the last mail (newest)
	// or pRetr($c, 1) <- get the old mail
	// print the source of message
	echo $r;
	// optional, you can delete this message from server
	POP3::pDele($c, $i) or die(print_r($_RESULT));
} else echo 'MailBox is empty !';
// disconnect
POP3::disconnect($c);

?>