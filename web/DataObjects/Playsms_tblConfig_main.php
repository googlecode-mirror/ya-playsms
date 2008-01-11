<?php
/**
 * Table Definition for playsms_tblConfig_main
 */
require_once 'DB/DataObject.php';

class DataObjects_Playsms_tblConfig_main extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'playsms_tblConfig_main';          // table name
    public $cfg_web_title;                   // string(250)  
    public $cfg_email_service;               // string(250)  
    public $cfg_email_footer;                // string(250)  
    public $cfg_gateway_module;              // string(20)  
    public $cfg_gateway_number;              // string(100)  
    public $cfg_system_from;                 // string(100)  

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('DataObjects_Playsms_tblConfig_main',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
