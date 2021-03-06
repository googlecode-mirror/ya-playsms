<?php
if (!defined("_SECURE_")) {

    die("Intruder: IP " . $_SERVER['REMOTE_ADDR']);
};

include "$apps_path[plug]/gateway/$gateway_module/config.php";

function gw_customcmd() {
    // nothing
}

function gw_send_sms($mobile_sender, $sms_to, $sms_msg, $gp_code = "", $uid = "", $smslog_id = "", $flash = false) {
    global $gnokii_param;
    global $gateway_number;
    $sms_id = "$gp_code.$uid.$smslog_id";
    if (empty ($sms_id)) {
        $sms_id = mktime();
    }
    $the_msg = "$sms_to\n$sms_msg";
    $fn = "$gnokii_param[path]/cache/smsd/out.$sms_id";
    umask(0);
    $fd = @ fopen($fn, "w+");
    @ fputs($fd, $the_msg);
    @ fclose($fd);
    $ok = false;
    if (file_exists($fn)) {
        $ok = true;
    }
    return $ok;
}

function gw_set_delivery_status($gp_code = "", $uid = "", $smslog_id = "", $p_datetime = "", $p_update = "") {
    global $gnokii_param;
    if ($gp_code) {
        $fn = "$gnokii_param[path]/cache/smsd/out.$gp_code.$uid.$smslog_id";
        $efn = "$gnokii_param[path]/cache/smsd/ERR.out.$gp_code.$uid.$smslog_id";
    } else {
        $fn = "$gnokii_param[path]/cache/smsd/out.PV.$uid.$smslog_id";
        $efn = "$gnokii_param[path]/cache/smsd/ERR.out.PV.$uid.$smslog_id";
    }

    // set delivered first
    setsmsdeliverystatus($smslog_id, $uid, DLR_SENT);

    // and then check if its not delivered
    if (file_exists($fn)) {
        $p_datetime_stamp = strtotime($p_datetime);
        $p_update_stamp = strtotime($p_update);
        $p_delay = floor(($p_update_stamp - $p_datetime_stamp) / 86400);

        // set pending if its under 2 days
        if ($p_delay <= 2) {
            setsmsdeliverystatus($smslog_id, $uid, DLR_PENDING);
        } else {
            setsmsdeliverystatus($smslog_id, $uid, DLR_FAILED);
            @ unlink($fn);
            @ unlink($efn);
        }
        return;
    }

    // set if its failed
    if (file_exists($efn)) {
        setsmsdeliverystatus($smslog_id, $uid, DLR_FAILED);
        @ unlink($fn);
        @ unlink($efn);
        return;
    }
    return;
}

function gw_set_incoming_action() {
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

            // collected:
            // $sms_datetime, $sms_sender, $target_code, $message
            if (setsmsincomingaction($sms_datetime, $sms_sender, $message)) {
                @ unlink($tobe_deleted);
            }
        }
    }
}

function gw_waitForStartup() {
}

?>