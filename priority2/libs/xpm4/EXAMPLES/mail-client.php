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
   - set 'text/plain' and 'text/html' version of message
   - send mail directly to client (without MTA support)
   - add attachment
   - embed image into HTML
   - print result
*/

// manage errors
error_reporting(E_ALL); // php errors
define('DISPLAY_XPM4_ERRORS', true); // display XPM4 errors

// path to 'MAIL.php' file from XPM4 package
require_once '../MAIL.php';

// get ID value (random) for the embed image
$id = MIME::unique();

// initialize MAIL class
$m = new MAIL;
// set from address and name
$m->From('me@myaddress.net', 'My Name');
// add to address and name
$m->AddTo('client@destination.net', 'Client Name');
// set subject
$m->Subject('Hello World!');
// set text/plain version of message
$m->Text('Text version of message.');
// set text/html version of message
$m->Html('<b>HTML</b> version of <u>message</u>.<br><i>Powered by</i> <img src="cid:'.$id.'">');
// add attachment ('text/plain' file)
$m->Attach('source file', 'text/plain');
$f = 'xpertmailer.gif';
// add inline attachment '$f' file with ID '$id'
$m->Attach(file_get_contents($f), FUNC::mime_type($f), null, null, null, 'inline', $id);

// send mail
echo $m->Send('client') ? 'Mail sent !' : 'Error !';

// optional for debugging ----------------
echo '<br /><pre>';
// print History
print_r($m->History);
// calculate time
list($tm1, $ar1) = each($m->History[0]);
list($tm2, $ar2) = each($m->History[count($m->History)-1]);
echo 'The process took: '.(floatval($tm2)-floatval($tm1)).' seconds.</pre>';

?>