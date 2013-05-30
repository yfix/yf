<?php
/**
 * Very simple xmlrpc proxy server. Forwards all requests to sf.net server
 *
 * @version $Id: proxyserver.php,v 1.1 2006/02/09 12:28:23 ggiunta Exp $
 * @copyright 2006
 */

include('xmlrpc.inc');
include('xmlrpcs.inc');
include('proxyxmlrpcs.inc');

$server = new proxy_xmlrpc_server(new xmlrpc_client('http://phpxmlrpc.sourceforge.net/server.php'), false);
$server->setDebug(2);
$server->service();
?>