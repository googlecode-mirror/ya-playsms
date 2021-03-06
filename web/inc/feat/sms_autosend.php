<?php
$op = $_GET[op];
$selfurl = $_SERVER['PHP_SELF'] . "?inc=sms_autosend";

// security check
//
if (!defined("_SECURE_")) {
	die("Intruder: IP " . $_SERVER['REMOTE_ADDR']);
};

// print any errors from a 
// previous load of this page
//
if ($err) {
	echo "<p><font color=red>$err</font><p>\n";
}

require_once 'DB/DataObject.php';
require_once 'DB/DataObject/FormBuilder.php';

error_log(print_r($_GET, true));
error_log(print_r($_POST, true));

switch ($op) {
	case "list" :
		echo makeList($selfurl);
		break;

	case "add" :
		echo makeEditForm($selfurl);
		break;

	case "edit" :
		echo makeEditForm($selfurl, $_GET[id]);
		break;

	case "del" :
		doDelete($selfurl, $_POST[id]);
		break;

}

function makeList($selfurl) {
	$html = "
			<h2>List/Manage/Delete SMS autosend</h2>
			<p/>
			<a href=\"$selfurl&op=add\">[ Add ]</a>
			<p/>";

	// create hidden form with the 
	// id to delete, this way it will
	// get POSTed
	//
	$formName = "delForm";
	$html .= "
			<form name=\"$formName\" method=\"post\" action=\"$selfurl&op=del\">
				<input type=\"hidden\" name=\"id\" value=\"\"/>
				<script language=\"JavaScript\"><!--
   					function del(id, msg) {
						if (confirm(msg)) {
							document.forms.$formName.id.value=id;
							document.forms.$formName.submit();
						}
				   }
				--></script>
			 </form>";

	// iterate through each item in the db
	// and create a line for it
	$item = DB_DataObject::factory(playsms_featAutoSend);
	$item->find();
	while ($item->fetch()) {
		$html .= "
			<a href=\"$selfurl&op=edit&id=$item->id\">[e]</a>

			<a href=\"javascript: 
					del($item->id, 'Are you sure you want to delete this autosend?');
					\">[x]</a>
 
			$item->frequency $item->number \"$item->msg\"
			<br/>
			";
	}
	return $html;
}

function makeEditForm($selfurl, $id= null) {
    // nice names for each field
    $formNames = array (
        'frequency' => 'Frequency:',
        'number' => 'To:<br/><font size="1">(number or group code, <br/>e.g., "admin")</font>',
        'msg' => 'Message:');

	$do = DB_DataObject::factory(playsms_featAutoSend);
	if ($id) $do->get($id);
	$fb = DB_DataObject_FormBuilder::create($do, 
                array("fieldLabels" => $formNames)
          );
	
	$fb->enumFields= array('frequency');
	$form = $fb->getForm("{$selfurl}&op=edit");

	setupSmsCounting($form, 'msg', '__submit__');

	if ($form->validate()) {
    	$form->process(array(&$fb,'processForm'), false);    	
	}
	$form->display();

    ?>    
    <a href="<?php echo $selfurl; ?>&op=list"><br><br>[back]</a>
    <?php	
}

function doDelete($selfurl, $id) {
	$do = DB_DataObject::factory(playsms_featAutoSend);
	$do->get($id);	
	$ok= $do->delete();

	if ($ok) {
		$error_string = "SMS autosend has been deleted!";
	} else {
		$error_string = "Failed to delete SMS autosend";
	}

	header("Location: $selfurl&op=list&err=" . urlencode($error_string));
}


?>
