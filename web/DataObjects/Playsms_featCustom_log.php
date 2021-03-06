<?php
/**
 * Table Definition for playsms_featCustom_log
 */
require_once 'DB/DataObject.php';

class DataObjects_Playsms_featCustom_log extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'playsms_featCustom_log';          // table name
    public $custom_log_id;                   // int(11)  not_null primary_key auto_increment
    public $sms_sender;                      // string(20)  not_null
    public $custom_log_datetime;             // string(20)  not_null
    public $custom_log_code;                 // string(10)  not_null
    public $custom_log_url;                  // blob(65535)  not_null blob

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('DataObjects_Playsms_featCustom_log',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
