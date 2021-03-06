<?php
include "configure.php";

# override the default database options
#
$dbinfos= array ('/etc/playsms/dbinfo.php', './dbinfo.php');
foreach ($dbinfos as $dbinfo) {
	if (file_exists($dbinfo)) {
		include $dbinfo;

        $db_param[type] = $dbtype;
        $db_param[user] = $dbuser;
        $db_param[pass] = $dbpass;
        $db_param[name] = $dbname;

		break;
	}
}

// configure DB_DataObject
$dboptions = &PEAR::getStaticProperty('DB_DataObject','options');
$dataobjname="DataObjects";
$dboptions = array(
    'database'         => "mysql://$db_param[user]:$db_param[pass]@localhost/$db_param[name]",
    'schema_location'  => "$apps_path[base]/$dataobjname",
    'class_location'   => "$apps_path[base]/$dataobjname",
    'require_prefix'   => "{$dataobjname}/",
    'class_prefix'     => "{$dataobjname}_",
);

// --------------------------------------------------------------------------------
if (!$DAEMON_PROCESS) {
	if (trim($SERVER_PROTOCOL) == "HTTP/1.1") {
		header("Cache-Control: no-cache, must-revalidate");
	} else {
		header("Pragma: no-cache");
	}
	ob_start();
}
// --------------------------------------------------------------------------------

// set global variable
$date_format = "Y-m-d";
$time_format = "G:i:s";
$datetime_format = $date_format . " " . $time_format;
$date_now = date($date_format, time());
$time_now = date($time_format, time());
$datetime_now = date($datetime_format, time());
$reserved_codes = array (
	PV,
	BC
); //,"GET","PUT","INFO","SAVE","DEL","LIST","RETR","POP3","SMTP","BROWSE","NEW","SET","POLL","VOTE","REGISTER","REG","DO","USE","EXECUTE","EXEC","RUN","ACK");
sort($reserved_codes);
$nd = "<font color=red>(*)</font>";

// sms constants
//
$SMS_SINGLE_MAXCHARS = 160;
$SMS_SINGLE_MULTIPART_MAXCHARS = ($SMS_SINGLE_MAXCHARS -7);
$SMS_MULTIPART_MAX = 3;
$SMS_MAXCHARS = ($SMS_SINGLE_MULTIPART_MAXCHARS * $SMS_MULTIPART_MAX);

// dlr constants
define(DLR_PENDING  , 0);
define(DLR_SENT     , 1);
define(DLR_FAILED   , 2);
define(DLR_DELIVERED, 3);

// time to pause between resend tries
define(RESEND_SLEEP, 90);

// max number of attempts to attempt sending an sms
define(SEND_TRY_MAX, 4);

// very important, do not try to remove it or change it
define("_SECURE_", "1");

// connect to database
include_once "$apps_path[libs]/dba.php";
$dba_object = dba_connect($db_param[user], $db_param[pass], $db_param[name], $db_param[host], $db_param[port]);
if (DB :: isError($dba_object)) {
	echo "Cannot connect to database '$db_param[name]' using user '$db_param[user]'. <br/>\n";
	echo "$dba_object->getMessage() <br/>\n";
	echo "<br/>\n";
	echo "Something may have gone wrong during setup-- please reconfigure the package or use your admin mysql account to create user '$db_param[user]' with password '$db_param[pass]; <br/>\n";

	die;
}

// get main config
$db_query = "SELECT * FROM playsms_tblConfig_main";
$db_result = dba_query($db_query);
if ($db_row = dba_fetch_array($db_result)) {
	$web_title = $db_row[cfg_web_title];
	$web_url = $db_row[cfg_web_url];
	$email_service = $db_row[cfg_email_service];
	$email_footer = $db_row[cfg_email_footer];
	$gateway_module = $db_row[cfg_gateway_module];
	$gateway_number = $db_row[cfg_gateway_number];
	$system_from = $db_row[cfg_system_from];
}

// protect from SQL injection when magic_quotes_gpc sets to "Off"
// by rolling our own magic quotes

function pl_addslashes($data) {
	global $db_param;
	
	if (is_array($data)) {
		foreach ($data as &$datum) {
		    $datum= pl_addslashes($datum);
		}
	} else {
		if ($db_param[type] == "mssql") {
			$data = str_replace("'", "''", $data);
		} else {
			$data = addslashes($data);
		}
	}
	return $data;
}
if (!get_magic_quotes_gpc()) {
	foreach ($_GET as $key => $val) {
		$_GET[$key] = pl_addslashes($_GET[$key]);
	}
	foreach ($_POST as $key => $val) {
		$_POST[$key] = pl_addslashes($_POST[$key]);
	}
	foreach ($_COOKIE as $key => $val) {
		$_COOKIE[$key] = pl_addslashes($_COOKIE[$key]);
	}
	foreach ($_SERVER as $key => $val) {
		$_SERVER[$key] = pl_addslashes($_SERVER[$key]);
	}
}
?>
