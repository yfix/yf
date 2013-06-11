<!DOCTYPE html>
<html>
<head>
	<title>YF Installation</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="//netdna.bootstrapcdn.com/bootswatch/2.3.2/slate/bootstrap.min.css" rel="stylesheet">
</head>
<body>
	<div class="navbar">
		<div class="navbar-inner">
			<a class="brand" href="https://github.com/yfix/yf">YF Framework</a>
			<ul class="nav">
				<li class=""><a href="https://github.com/yfix/yf">Home</a></li>
				<li class=""><a href="./customize.html">Customize</a></li>
			</ul>
		</div>
	</div>
	<header>
		<div class="container">
			<p class="lead">Welcome to YF Framework installation process. Submit form below to finish.</p>
		</div>
	</header>
	<div class="container">
		<form class="form-horizontal">
			<div class="control-group">
				<label class="control-label" for="input_yf_path">Filesystem path to YF</label>
				<div class="controls"><input type="text" id="input_yf_path" placeholder="Filesystem path to YF" value="{INSTALL_YF_PATH}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="input_db_host">Database Host</label>
				<div class="controls"><input type="text" id="input_db_host" placeholder="Database Host" value="{INSTALL_DB_HOST}"></div>
			</div>
			<div class="control-group">
				<label class="checkbox"><input type="checkbox" name="input_db_create">Create Database</label>
				<label class="control-label" for="input_db_name">Database Name</label>
				<div class="controls"><input type="text" id="input_db_name" placeholder="Database Name" value="{INSTALL_DB_NAME}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="input_db_user">Database Username</label>
				<div class="controls"><input type="text" id="input_db_user" placeholder="Database Username" value="{INSTALL_DB_USER}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="input_db_pswd">Database Password</label>
				<div class="controls"><input type="text" id="input_db_pswd" placeholder="Database Password" value="{INSTALL_DB_PSWD}"></div>
			</div>
			<div class="control-group">
				<label class="checkbox"><input type="checkbox" name="input_db_drop_existing">Drop Existing Tables</label>
				<label class="control-label" for="input_db_prefix">Database Prefix</label>
				<div class="controls"><input type="text" id="input_db_prefix" placeholder="Database Prefix" value="{INSTALL_DB_PREFIX}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="input_web_path">Web Path</label>
				<div class="controls"><input type="text" id="input_web_path" placeholder="Web Path" value="{INSTALL_WEB_PATH}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="input_admin_login">Administrator Login</label>
				<div class="controls"><input type="text" id="input_admin_login" placeholder="Administrator Login" value="{INSTALL_ADMIN_LOGIN}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="input_admin_pswd">Administrator Password</label>
				<div class="controls"><input type="text" id="input_admin_pswd" placeholder="Administrator Password" value="{INSTALL_ADMIN_PSWD}"></div>
			</div>

			<div class="control-group">
				<label class="checkbox"><input type="checkbox" name="input_rw_enabled">Enable URL Rewrites</label>
				<label class="control-label" for="input_rw_base">URL Rewrites Base</label>
				<div class="controls"><input type="text" id="input_rw_base" placeholder="URL Rewrites Base" value="{INSTALL_RW_BASE}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="input_web_name">Website Name</label>
				<div class="controls"><input type="text" id="input_web_name" placeholder="Website Name" value="{INSTALL_WEB_NAME}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="input_db_name">Database Name</label>
				<div class="controls"><input type="text" id="input_db_name" placeholder="Database Name" value="{INSTALL_DB_NAME}"></div>
			</div>

			<div class="control-group">
				<div class="controls">
					<button type="submit" class="btn">Launch!</button>
				</div>
			</div>
		</form>
	</div>
	<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>
</body>
</html>

<!--
<form action="./install.php?step=main_settings" name="install" method="post">
<tr>
<th colspan="2" class="ms">Database Configuration</th>
</tr>
<tr valign="top">
<td width="50%"><b class="required">*</b>Database Server Hostname:</td>
<td><input class="text" type="text" name="dbhost" value="localhost" /></td>
</tr>
<tr valign="top">
<td><b class="required">*</b>Your Database Name:</td>
<td>
<input class="text" type="text" name="dbname" value="" /><br />
<label><input name="create_database" type="checkbox" checked="true"  />Create if not exists</label>
</td>
</tr>
<tr valign="top">
<td><b class="required">*</b>Database Username:</td>
<td><input class="text" type="text" name="dbuser" value="root" /></td>
</tr>
<tr valign="top">
<td>Database Password:</td>
<td><input class="text" type="password" name="dbpasswd" value="" /></td>
</tr>
<tr valign="top">
<td>Prefix for tables in database:</td>
<td>
<input class="text" type="text" name="prefix" value="test_" /><br />
<label><input name="delete_table" type="checkbox" checked="true" />Delete table if exists</label>
</td>
</tr>
<tr>
<th colspan="2" class="ms">Admin Configuration</th>
</tr>
<tr valign="top">
<td><b class="required">*</b>Web Path:</td>
<td><input class="text" type="text" name="web_path" value="http://192.168.1.5/test2/" /></td>
</tr>
<tr valign="top">
<td><b class="required">*</b>YF Framework Path:</td>
<td><input class="text" type="text" name="framework_path" value="../yf/" /></td>
</tr>
<tr valign="top">
<td><b class="required">*</b>Administrator Username:</td>
<td><input class="text" type="text" name="admin_name" value="admin" /></td>
</tr>
<tr valign="top">
<td><b class="required">*</b>Administrator Password:</td>
			<td><input class="text" type="password" name="admin_pass1" value="" /></td>
		</tr>
		<tr valign="top">
			<td><b class="required">*</b>Administrator Password [Confirm]:</td>
			<td><input class="text" type="password" name="admin_pass2" value="" /></td>
		</tr>					
		<tr valign="top">
			<td><b class="required">*</b> - required fields</td>
		</tr>
-->