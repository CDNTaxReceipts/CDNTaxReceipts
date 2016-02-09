<?php

require_once 'CRM/Core/Page.php';

class CRM_Cdntaxreceipts_Page_OpenEvent extends CRM_Core_Page {

  function run() {
    
    $tracking_id = CRM_Utils_Array::value('r', $_GET);
    if (!$tracking_id) {
      echo "Missing input parameters\n";
      exit();
    }

    cdntaxreceipts_process_open($tracking_id);

    global $civicrm_root;
    $filename = $civicrm_root . "/i/tracker.gif";

    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Content-Description: File Transfer');
    header('Content-type: image/gif');
    header('Content-Length: ' . filesize($filename));
    header('Content-Disposition: inline; filename=tracker.gif');
    readfile($filename);

    CRM_Utils_System::civiExit();
    
  }

}
