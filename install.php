<?php
	session_start();
 	if(isset($_GET['step']) && $_GET['step'] == 3){	
		thank_you();	
		exit();
	}	
 	verify_old_installation(); 
	if(isset($_GET['step']) && $_GET['step'] == 2){		
		install();	
	}	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
	<head>
		<title>TrackThrough Installation</title>
		<?php
			$base_url = get_base_url();		
			$css_path = $base_url . '/resources/images/installer/style.css';
			$js_path = $base_url . '/resources/images/installer/install.js'; 
		?>	
		<link type="text/css" rel="stylesheet" href="<?php echo $css_path ?>" />
		<script type="text/javascript" src="<?php echo $js_path ?>"></script>		
	</head>	
	<body>		
<?php
	$version = get_tt_version();
	$page_title = 'Welcome to TrackThrough ' . $version . ' Installation';
	echo show_header($page_title);
	echo get_inputs();
	echo show_footer();
	
	function verify_old_installation() {
		$dir_fs_www_root = realpath(dirname(__FILE__));		
		$ini_file = $dir_fs_www_root .DIRECTORY_SEPARATOR. 'config.ini';	
		if(file_exists($ini_file)) {
			my_die("Application is already installed; please remove config.ini file and run this script to reinstall the application. ". mysql_error(), false);
		}		
	}
	
	function get_base_url() {
		$protocol = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ? 'https' : 'http';
		$base_url = $protocol.'://'.  $_SERVER['HTTP_HOST'];  
		$base_url .= str_replace("/".basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']); 		
		return $base_url;
	}
	
	function create_iv() {
		srand();
		$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND);
		return base64_encode($iv);
	}
	
	function encrypt($src, $key, $iv) {
		if ($src == '' || $key == '') {
			return '';
		}
		$my_key = $key . 'bispark.com';
		$my_key = substr($my_key, 0, 5);
		$my_key = md5($my_key);
		// Encryption Algorithm
		$cipher_alg = MCRYPT_RIJNDAEL_128;
		// Encrypt $string
		return base64_encode(mcrypt_encrypt($cipher_alg, $my_key, $src, MCRYPT_MODE_CBC, base64_decode($iv)));
		//return $src;
	}		
	
	function show_header($page_title){
		$base_url = get_base_url();
		$img_path = $base_url.'/resources/images/logo.png';
		echo '<div class="whole"><div class="main_body">' .
				'<div class="top_shade"></div>' .
				'<div class="header_block"><div class="header_logo"><img src="'.$img_path.'" width="27" height="27" class="logo" alt="TrackThrough Logo" /></div>' .
					'<div class="page_title"><i>' . $page_title . '</i></div><div style="clear:both;"></div></div>' .
					'<div class="separator"><hr /></div>' .
					'<div class="content">';					
	}
	
	function show_footer(){	
		echo '</div><div class="btm_shade"></div></div><div class="footer">					
					<a href="http://www.bispark.com/">bispark software (www.bispark.com)</a>.
			</div></div></body></html>';	
	}
	
	function get_inputs(){		
	   	verify_old_installation();	  
		$base_url = get_base_url();
		
		$dbname_value = get_parameter('dbname') ? get_parameter('dbname') : 'ttdb';
		if(get_parameter('db_check') == '1'){
			$db_check1_checked = "checked=checked";
		}
		else{
			$db_check2_checked = "checked=checked";
		}
		$dbserver_value = get_parameter('dbserver') ? get_parameter('dbserver') : 'localhost';
		$dbuser_value = get_parameter('dbuser') ? get_parameter('dbuser') : 'root';		
		$prefix_value = get_parameter('prefix') ? get_parameter('prefix') : '';
		$port_num_value = get_parameter('port_num') ? get_parameter('port_num') : '3306';
		$admin_username_value = get_parameter('admin_username') ? get_parameter('admin_username') : 'admin';		
		$admin_email_id_value = get_parameter('admin_email_id') ? get_parameter('admin_email_id') : '';
		
		$page = '
		<div class="content_desc">		
			This installation routine will correctly setup and configure TrackThrough to run on this server.
			Please follow the on-screen instructions that will help you configure and install. For installation help you can
			also refer our TrackThrough manual or check on our site <a href="http://www.bispark.com/trackthrough/installation-instructions">Click here</a>.
		</div>
		<form name="install" id="installForm" method="post" action="'.$base_url.'/install.php?step=2" onsubmit="return check_blank(this);">
			<div class="shaded_block">
				<div class="shaded_inner_block">
					<span>
						<b class="red_text">* Database name:</b> <br />
						<input type="text" id="dbname" name="dbname" value="'.$dbname_value.'" class="input_box" onmouseover=highlightField("dbname") onmouseout=normalField("dbname") />
					</span>
					<div class="field_desc">
						The name of the database Trackthrough data will be stored in.
					</div>						
				</div>
				<div style="clear:both;">&nbsp;</div>	
			</div>
			<div class="shaded_block">
				<div class="shaded_inner_block">
					<span>
						<b class="red_text">Already have a databse?</b>
						<div class="option_block">
							<input type="radio" name="db_check" id="db_check1" value="1" '.$db_check1_checked.' /> Yes &nbsp;
							<input type="radio" name="db_check" id="db_check2" value="2" '.$db_check2_checked.' /> No				
						</div>					
					</span>
					<div class="field_desc">
						If you have already created the database then select "Yes" otherwise select "No".
					</div>						
				</div>
				<div style="clear:both;">&nbsp;</div>	
			</div>
			<div class="shaded_block">
				<div class="shaded_inner_block">
					<span>
						<b class="red_text">* Database server:</b> <br />
						<input type="text" id="dbserver" name="dbserver" value="'.$dbserver_value.'" class="input_box" onmouseover=highlightField("dbserver") onmouseout=normalField("dbserver") />
					</span>
					<div class="field_desc">
						The address of the database server in the form of a hostname or IP address.
						If your database is located on a different server, change this.
					</div>						
				</div>
				<div style="clear:both;">&nbsp;</div>	
			</div>
			<div class="shaded_block">
				<div class="shaded_inner_block">
					<span>
						<b class="red_text">* Database username:</b> <br />
						<input type="text" id="dbuser" name="dbuser" value="'.$dbuser_value.'" class="input_box" onmouseover=highlightField("dbuser") onmouseout=normalField("dbuser") />
					</span>
					<div class="field_desc">
						The username used to connect to the database server. Default value is the name of the user that owns the server process.
					</div>						
				</div>
				<div style="clear:both;">&nbsp;</div>	
			</div>
			<div class="shaded_block">
				<div class="shaded_inner_block">
					<span>
						<b class="red_text">Database password:</b> <br />
						<input type="password" id="dbpassword" name="dbpassword" class="input_box" onmouseover=highlightField("dbpassword") onmouseout=normalField("dbpassword") />
					</span>
					<div class="field_desc">
						The password that is used together with the username to connect to the database server. Default value is an empty password.
					</div>					
				</div>
				<div style="clear:both;">&nbsp;</div>	
			</div>
			<div class="shaded_block">
				<div class="shaded_inner_block">
					<span>
						<b class="red_text">Table prefix:</b> <br />
						<input type="text" id="prefix" name="prefix" value="'.$prefix_value.'" class="input_box" onmouseover=highlightField("prefix") onmouseout=normalField("prefix") />
					</span>
					<div class="field_desc">
						If you are using only one database and want to append prefix to your database tables then you can fill this field such as trackthrough_. (optional)
					</div>					
				</div>
				<div style="clear:both;">&nbsp;</div>	
			</div>
			<div class="shaded_block">
				<div class="shaded_inner_block">
					<span>
						<b class="red_text">MySQL port number:</b> <br />
						<input type="text" id="port_num" name="port_num" value="'.$port_num_value.'" class="input_box" onmouseover=highlightField("port_num") onmouseout=normalField("port_num") />
					</span>
					<div class="field_desc">
						The MySQL server can include a port number. e.g.: "hostname:port" or a path to a local socket e.g.: ":/path/to/socket" for the localhost. If the PHP directive mysql.default_host is undefined (default)� then the default value is 3306. If your database server is listening to a non-standard port, change its number.
					</div>					
				</div>
				<div style="clear:both;">&nbsp;</div>	
			</div>
			<div class="shaded_block">
				<div class="shaded_inner_block">
					<span>
						<b class="red_text">* Admin username:</b> <br />
						<input type="text" id="admin_username" name="admin_username" value="'.$admin_username_value.'" maxlength="16" class="input_box" onmouseover=highlightField("admin_username") onmouseout=normalField("admin_username") />
					</span>
					<div class="field_desc">
						The username to login as application administrator. Once you login to the application you can change the username.' .
						' Admin username should have alpha-numeric characters only and it must be at least 3 characters in length, it can not exceed 16 characters. 
					</div>						
				</div>
				<div style="clear:both;">&nbsp;</div>	
			</div>
			<div class="shaded_block">
				<div class="shaded_inner_block">
					<span>
						<b class="red_text">* Admin password:</b> <br />
						<input type="password" id="admin_password" name="admin_password" class="input_box" onmouseover=highlightField("admin_password") onmouseout=normalField("admin_password") />
					</span>
					<div class="field_desc">
						The password that is used together with the admin username to login to the application. You can change the password once you login to the application.' .
						' Admin password field must be at least 5 characters in length.
					</div>						
				</div>
				<div style="clear:both;">&nbsp;</div>	
			</div>			
			<div class="shaded_block">
				<div class="shaded_inner_block">
					<span>
						<b class="red_text">* Confirm password:</b> <br />
						<input type="password" id="admin_confirm_password" name="admin_confirm_password" class="input_box" onmouseover=highlightField("admin_confirm_password") onmouseout=normalField("admin_confirm_password") />
					</span>
					<div class="field_desc">
						Please re-enter admin password.
					</div>						
				</div>
				<div style="clear:both;">&nbsp;</div>	
			</div>
			<div class="shaded_block">
				<div class="shaded_inner_block">
					<span>
						<b class="red_text">Admin email address:</b> <br />
						<input type="text" id="admin_email_id" name="admin_email_id" value="'.$admin_email_id_value.'" maxlength="100" class="input_box" onmouseover=highlightField("admin_email_id") onmouseout=normalField("admin_email_id") />
					</span>
					<div class="field_desc">
						Please make sure to double check your email address as the application uses this address to notify all TrackThrough events.
						You can change email address even after login to the application. The email address can be maximum 100 characters in length.  
					</div>						
				</div>
				<div style="clear:both;">&nbsp;</div>	
			</div>
			<div class="button_block" align="center">
				<input type="submit" class="button" value="Submit" /> &nbsp;
				<input class="button" type="reset" value="Reset" />				
			</div>			
		</form>';
		return $page;
	}
	
	function valid_email($address) {
		return (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address)) ? FALSE : TRUE;
	}
	
	function valid_name($subject) {
		return (!preg_match("/^([a-z0-9A-Z])+$/i", $subject)) ? FALSE : TRUE;
	} 
	
	function set_parameter($key, $value) {
		$_SESSION[$key] = $value;
	}
	function get_parameter($key) {
		if (empty ($_SESSION[$key])) {
			return false;
		}
		return $_SESSION[$key];
	}

	function install() {	
		if(isset($_POST["dbname"])){			
			set_parameter("dbname", $_POST["dbname"]);
		}
		if(isset($_POST["db_check"])){
			set_parameter("db_check", $_POST["db_check"]);		
		}	
		if(isset($_POST["dbserver"])){
			set_parameter("dbserver", $_POST["dbserver"]);
		}
		if(isset($_POST["dbuser"])){
			set_parameter("dbuser", $_POST["dbuser"]);
		}
		if(isset($_POST["prefix"])){
			set_parameter("prefix", $_POST["prefix"]);
		}
		if(isset($_POST["port_num"])){
			set_parameter("port_num", $_POST["port_num"]);
		}
		if(isset($_POST["admin_username"])){
			set_parameter("admin_username", $_POST["admin_username"]);
		}
		if(isset($_POST["admin_email_id"])){
			set_parameter("admin_email_id", $_POST["admin_email_id"]);
		}
		
		
		if(!isset($_POST["dbname"]) || $_POST["dbname"] == '') {
			my_die("Please enter Database name!");
		}
		if(!isset($_POST["dbserver"]) || $_POST["dbserver"] == '') {
			my_die("Please enter Database server!");
		}
		if(!isset($_POST["dbuser"]) || $_POST["dbuser"] == '') {
			my_die("Please enter Database username!");
		}	
		if(!isset($_POST["admin_username"]) || $_POST["admin_username"] == '') {
			my_die("Please enter Admin username!");
		}	
		if (!$_POST["admin_username"] == '' && !valid_name($_POST["admin_username"])) {
			my_die("Admin username should have alpha-numeric characters only!");
		}
		if (!$_POST["admin_username"] == '' && (strlen($_POST["admin_username"]) < 3)) {
			my_die("Admin username must be at least 3 characters in length!");
		}		
		if(!isset($_POST["admin_password"]) || $_POST["admin_password"] == '') {
			my_die("Please enter Admin password!");
		}
		if (!$_POST["admin_password"] == '' && (strlen($_POST["admin_password"]) < 5)) {
			my_die("Password must be at least 5 characters in length!");
		}
		if ($_POST["admin_password"] !== $_POST["admin_confirm_password"]) {
			my_die("Admin passwords do not match, please try again!");
		}	
		if (!$_POST["admin_email_id"] == '' && !valid_email($_POST["admin_email_id"])) {
			my_die("Admin email address not in correct format!");			
		}
		
		$db_server_name = $_POST["dbserver"];
		$dbuser = $_POST["dbuser"];
		$dbpassword = $_POST["dbpassword"];
		$dbname = $_POST["dbname"];
		$port_no = $_POST["port_num"] ? $_POST["port_num"] : 3306;
		$prefix = $_POST["prefix"];		
		$admin_username = $_POST["admin_username"];				
		$admin_password = $_POST["admin_password"];		
		$admin_email_id = $_POST["admin_email_id"];
		$create_db = $_POST["db_check"] == 2; //no, hence we need to create database
		
		$base_url = get_base_url();		
		$dsn = "mysql://$dbuser:$dbpassword@$db_server_name:$port_no/$dbname";		
		$create_db_errors = create_database($db_server_name, $port_no, $dbname, $dbuser, $dbpassword, $prefix, $create_db, $admin_username, $admin_password, $admin_email_id);	
	
		$dir_fs_www_root = realpath(dirname(__FILE__));		
		$ini_file_org = $dir_fs_www_root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'config.ini.original';
		if(!file_exists($ini_file_org)) {
			my_die("Missing config file in the distribution source. ". mysql_error());
		}			
		$tbl_prefix =  $prefix && ($prefix != '') ? "$prefix"."_" : '';
		$ini_cont = file_get_contents($ini_file_org);		
		$pattern =array('BASE_URL_VAL', 'DSN_VAL', 'PREFIX_VAL');
		$replacement=array($base_url, $dsn, $tbl_prefix);		
		$new_cont = str_replace($pattern, $replacement, $ini_cont);		
		//write
		$ini_file = $dir_fs_www_root .DIRECTORY_SEPARATOR. 'config.ini';
		$fp = fopen($ini_file, 'w');
		if(!$fp) {
		 	my_die("Can't create ini file. ". mysql_error());	
		}
		fputs($fp, $new_cont);
		fclose($fp);
		
		//creating config.ini file for mini-profile
		$mini_ini_file_org = $dir_fs_www_root.DIRECTORY_SEPARATOR.'mini'.DIRECTORY_SEPARATOR.'config.ini';
		if(!file_exists($mini_ini_file_org)) {
			my_die("Missing config file in the mini-profile distribution source. ". mysql_error());
		}	
		$mini_ini_cont = file_get_contents($mini_ini_file_org);
		$old_mini_base_url = explode("\n", $mini_ini_cont);
		$new_mini_base_url = 'base_url='.$base_url . '/mini';

		$mini_new_cont = str_replace($old_mini_base_url[3], $new_mini_base_url, $mini_ini_cont);		
		//write
		$mini_ini_file = $dir_fs_www_root.DIRECTORY_SEPARATOR.'mini'.DIRECTORY_SEPARATOR. 'config.ini';
		$mini_fp = fopen($mini_ini_file, 'w');
		if(!$mini_fp) {
		 	my_die("Can't create ini file in mini-profile source. ". mysql_error());	
		}
		fputs($mini_fp, $mini_new_cont);
		fclose($mini_fp);
		
		
		$mv = get_mysql_version($dbuser, $dbpassword);
		
		$base_url = get_base_url()."/install.php?step=3&mv=$mv";
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
     	header("Location: $base_url");				
		exit();	
	}	
	
	function get_mysql_version($dbuser, $dbpassword){
		$link = mysql_connect('localhost', $dbuser, $dbpassword);
		if (!$link) {
	    	die('Could not connect: ' . mysql_error());
		}
		return mysql_get_server_info();
	}
	
	function create_database($db_server_name, $port_no, $dbname, $dbuser, $dbpassword, $prefix, $create_db, $admin_username, $admin_password, $admin_email_id) {
		$connection = @mysql_connect("$db_server_name:$port_no","$dbuser","$dbpassword");
		if (!$connection) {		
			my_die("Could not connect to MySQL Server. ". mysql_error());
		}
		if($create_db){
			if (!(mysql_query(" CREATE DATABASE $dbname ", $connection))) {
				my_die("Could not create database. Database already exists. ". mysql_error());
			}
		}
		$db = mysql_select_db($dbname, $connection);		
		if(!$db){
			my_die("There is no database by name $dbname. ". mysql_error());
		}			
		$schema_sql = get_schema_sql($prefix, $admin_username, $admin_password, $admin_email_id);	
		
		$schema_content = preg_split('/;/', $schema_sql, -1, PREG_SPLIT_OFFSET_CAPTURE); 
		$errors = array();
		$cnt = 0;
    	foreach ($schema_content as $content) {
    		if($cnt < (count($schema_content)- 1)){ 
    		$cnt = $cnt + 1;
    		if (!mysql_query($content[0])) {
	 			my_die("There was an error while creating database schema; error message: ". mysql_error());
	 			$errors [] =  mysql_error();
	 		}
    		}
		}
		return $errors;			
	}
	
	function get_schema_sql($prefix, $admin_username, $admin_password, $admin_email_id){
		$dir_fs_www_root = realpath(dirname(__FILE__));		
		$sql_file_org = $dir_fs_www_root .DIRECTORY_SEPARATOR. 'scripts'.DIRECTORY_SEPARATOR.'ttdb.sql';
		
		if(!file_exists($sql_file_org)) {
			my_die("Missing sql file in the distribution source.". mysql_error());
		}
		$sql_cont = file_get_contents($sql_file_org);
		$pattern =array('`config`', '`message`', '`project`', '`task`', '`user`', '`user_permission`', '`message_board`', '`preference`', '`bookmark`', '`issue`', '`issue_task`', 'ADMIN_USERNAME', 'ADMIN_PASSWORD', 'ADMIN_IV', 'ADMIN_EMAIL_ID');
		
		$tbl_prefix = $prefix && ($prefix != '') ? "$prefix"."_" : '';
		
		$admin_iv = create_iv();
		$admin_enc_password = encrypt($admin_password, $admin_username, $admin_iv);
		$replacement = array('`'.$tbl_prefix.'config`', '`'.$tbl_prefix.'message`', '`'.$tbl_prefix.'project`', '`'.$tbl_prefix.'task`', '`'.$tbl_prefix.'user`','`'.$tbl_prefix.'user_permission`', '`'.$tbl_prefix.'message_board`', '`'.$tbl_prefix.'preference`', '`'.$tbl_prefix.'bookmark`','`'.$tbl_prefix.'issue`','`'.$tbl_prefix.'issue_task`', $admin_username, $admin_enc_password, $admin_iv, $admin_email_id);
		//echo $replacement[6]; exit();
		return str_replace($pattern, $replacement, $sql_cont);		
	}
	
	function my_die($message, $show_back = true) {	
		$base_url = get_base_url();
		$path = $base_url.'/install.php';
		
		$back_btn  = $show_back ? '<br/><span class="link">&lt;&lt; <a href="'.$path.'">Back</a></span><br/>' : '';		
		$css_path = $base_url . '/resources/images/installer/style.css'; 
?>		
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
 		<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
			<head>
				<title>Error Page</title>
				<link type="text/css" rel="stylesheet" href="<?php echo $css_path ?>" />
			</head>		
			<body>
<?php
		$page_title = 'Oops!.. You got an error.';
		echo show_header($page_title);
		die( '<div class="error_msg_box"><div class="shaded_inner_block">
				<div class="red_text"><b>Error:</b></div>
				<div>'.$message.'</div>
				'.$back_btn.'
				</div></div></div><div class="btm_shade"></div></div><div class="footer">					
					<a href="http://www.bispark.com/">bispark software (www.bispark.com)</a>.
			</div></div></body></html>');			
	}
	
	function thank_you(){
		if(isset($_GET["mv"]) && $_GET["mv"] != ''){
			$mv = $_GET["mv"];
		}
		$base_url = get_base_url();
		$tt_version = get_tt_version();
		$page_title = ' Congratulations! It is done.';
		$css_path = $base_url . '/resources/images/installer/style.css'; 
?>		
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
 		<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
			<head>
				<title>Thank You Page</title>
				<link type="text/css" rel="stylesheet" href="<?php echo $css_path ?>" />
			</head>		
			<body>		
<?php		
		if(@fopen("http://www.bispark.com", "r")){
   	   		$veryfy_path = 'http://www.bispark.com/trackthrough/verify-installation/';
   	   	}	
   	   	else{
   	   		$veryfy_path = $base_url;
   	   	}		
   	   	$pv = phpversion();	
		echo show_header($page_title);				
		echo '<div class="thank_you_block"><div class="shaded_inner_block">
				<div class="green_text">You have successfully installed TrackThrough version - '.$tt_version.'</div>				
				<div class="rounded_block"><div class="rounded_top_left"></div><div class="rounded_top_right"></div><div class="rounded_inner_block">' .
				'<div class="content_desc">Please share following installation details; we will keep this in mind while building future versions.</div>' .
				'<div><b class="red_text">PHP Version: </b> '.$pv.
				'</div><div><b class="red_text">MySQL Version: </b>' . $mv . 
				'</div><div><b class="red_text">TrackThrough Version: </b>'.$tt_version.
				'</div><div align="center">' .
					'<form method="post" action="'.$veryfy_path.'">
						<input type="submit" class="blue_button" value="Submit" />' .
						'<input type="hidden" name="pv" value="'.$pv.'" />' .
						'<input type="hidden" name="mv" value="'.$mv.'" />' .
						'<input type="hidden" name="tt_version" value="'.$tt_version.'" />' .
						'<input type="hidden" name="base_url" value="'.$base_url.'" />' .
					'</form>
				</div></div><div class="rounded_bottom_left"></div><div class="rounded_bottom_right"></div></div><div class="go_link"><a href="'.$base_url.'">Go to my TrackThrough</a></div></div><br/></div>';
		echo show_footer();		
	}
	
	function get_tt_version()  {
		$dir_fs_www_root = realpath(dirname(__FILE__));		
		$ini_file_org = $dir_fs_www_root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'config.ini.original';
		if(!file_exists($ini_file_org)) {
			my_die("Missing config file in the distribution source. ". mysql_error());
		}
		$start_pattern = "version =" ;
		$ini_cont = file_get_contents($ini_file_org);
		
		$chunk = trim(stristr ($ini_cont, $start_pattern));
		if($chunk == '') {
	  	 my_die("invalid config file. ");
		}
				
		return substr($chunk, strlen($start_pattern), 6);		
	}
?>		