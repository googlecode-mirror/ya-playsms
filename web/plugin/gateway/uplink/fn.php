<?php
if (!defined("_SECURE_")) {

    die("Intruder: IP " . $_SERVER['REMOTE_ADDR']);
};

include "$apps_path[plug]/gateway/$gateway_module/config.php";

function gw_customcmd() {
    // nothing
}

// gw_send_sms 
// called by main sms sender
// return true for success delivery
// $mobile_sender	: sender's mobile number
// $sms_to		: destination sms number
// $sms_msg		: sms message tobe delivered
// $gp_code		: group phonebook code (optional)
// $uid			: sender's User ID
// $smslog_id		: sms ID
function gw_send_sms($mobile_sender, $sms_to, $sms_msg, $gp_code = "", $uid = "", $smslog_id = "", $flash = false) {
    // global $uplink_param;   // global all variables needed, eg: varibles from config.php
    // ...
    // ...
    // return true or false
    // return $ok;
    global $uplink_param;
    global $gateway_number;
    $ok = false;
    if ($gateway_number) {
        $sms_from = $gateway_number;
    } else {
        $sms_from = $mobile_sender;
    }
    $sms_type = 2; // text
    if ($flash) {
        $sms_type = 1; // flash
    }
    if ($sms_to && $sms_msg) {
        $query_string = "ws.php?u=" . $uplink_param[username] . "&p=" . $uplink_param[password] . "&ta=pv&to=" . urlencode($sms_to) . "&from=" . urlencode($sms_from) . "&type=$sms_type&msg=" . urlencode($sms_msg);
        $url = $uplink_param[master] . "/" . $query_string;
        $fd = @ implode('', file($url));
        if ($fd) {
            $response = split(" ", $fd);
            if ($response[0] == "OK") {
                $remote_slid = $response[1];
                if ($remote_slid) {
                    $db_query = "
                    			INSERT INTO playsms_gwmodUplink (up_local_slid,up_remote_slid,up_status)
                    			VALUES ('$smslog_id','$remote_slid','0')
                    		    ";
                    $up_id = @ dba_insert_id($db_query);
                    if ($up_id) {
                        $ok = true;
                    }
                }
            }
        }
    }

    if ($ok) {
        $p_status= DLR_SENT;
    } else {
        $p_status= DLR_FAILED;
    }
    setsmsdeliverystatus($smslog_id, $uid, $p_status);
    return $ok;
}

// gw_set_delivery_status
// called by daemon.php (periodic daemon) to set sms status
// no returns needed
// $p_datetime	: first sms delivery datetime
// $p_update	: last status update datetime
function gw_set_delivery_status($gp_code = "", $uid = "", $smslog_id = "", $p_datetime = "", $p_update = "") {
    // global $uplink_param;
    // p_status :
    // 0 = pending
    // 1 = delivered
    // 2 = failed
    // setsmsdeliverystatus($smslog_id,$uid,$p_status);
    global $uplink_param;
    $db_query = "SELECT * FROM playsms_gwmodUplink WHERE up_status='0' AND up_local_slid='$smslog_id'";
    $db_result = dba_query($db_query);
    while ($db_row = dba_fetch_array($db_result)) {
        $local_slid = $db_row[up_local_slid];
        $remote_slid = $db_row[up_remote_slid];
        $query_string = "ws.php?u=" . $uplink_param[username] . "&p=" . $uplink_param[password] . "&ta=ds&slid=" . $remote_slid;
        $url = $uplink_param[master] . "/" . $query_string;
        $response = @ implode('', file($url));
        switch ($response) {
            case "1" :
                setsmsdeliverystatus($local_slid, $uid, DLR_SENT);
                $db_query1 = "UPDATE playsms_gwmodUplink SET up_status='1' WHERE up_remote_slid='$remote_slid'";
                $db_result1 = dba_query($db_query1);
                break;
            case "2" :
            case "ERR 400" :
                setsmsdeliverystatus($local_slid, $uid, DLR_FAILED);
                $db_query1 = "UPDATE playsms_gwmodUplink SET up_status='2' WHERE up_remote_slid='$remote_slid'";
                $db_result1 = dba_query($db_query1);
                break;
        }
    }
}

// gw_set_incoming_action
// called by incoming sms processor
// no returns needed
function gw_set_incoming_action() {
    // global $uplink_param;
    // $sms_datetime	: incoming sms datetime
    // $target_code	: target code
    // $message		: incoming sms message
    // setsmsincomingaction($sms_datetime,$sms_sender,$target_code,$message)
    // you must retrieve all informations needed by setsmsincomingaction()
    // from incoming sms, have a look gnokii gateway module
    global $gnokii_param;
    $handle = @ opendir("$gnokii_param[path]/cache/smsd");
    while ($sms_in_file = @ readdir($handle)) {
        if (eregi("^ERR.in", $sms_in_file) && !eregi("^[.]", $sms_in_file)) {
            $fn = "$gnokii_param[path]/cache/smsd/$sms_in_file";
            $tobe_deleted = $fn;
            $lines = @ file($fn);
            $sms_datetime = trim($lines[0]);
            $sms_sender = trim($lines[1]);
            $message = "";
            for ($lc = 2; $lc < count($lines); $lc++) {
                $message .= trim($lines[$lc]);
            }

            if (setsmsincomingaction($sms_datetime, $sms_sender, $message)) {
                @ unlink($tobe_deleted);
            }
        }
    }
}

function gw_waitForStartup() {
}
?>