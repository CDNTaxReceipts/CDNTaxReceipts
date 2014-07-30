<?php

abstract class CRM_Cdntaxreceipts_PDF_Generator {

  protected $_imageFilesPath;
  protected $_imageFilesURL;

  function __construct() {
    $resources = CRM_Core_Resources::singleton();
    $path = $resources->getPath('org.civicrm.cdntaxreceipts', 'img/preview_mode.png');
    $this->_imageFilesPath = dirname($path) . '/';
    $url  = $resources->getUrl('org.civicrm.cdntaxreceipts' , 'img/');
    $this->_imageFilesURL = $url;
  }

  abstract function open();

  abstract function addPage($pdf_variables);

  abstract function closeAndSend($filename);

  abstract function closeAndSave($filename);

  abstract function getDefaultLeftMargin();

  abstract function getDefaultTopMargin();
}
