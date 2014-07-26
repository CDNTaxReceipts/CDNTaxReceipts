<?php

abstract class CRM_Cdntaxreceipts_PDF_Generator {

  protected $_imageFilesPath;

  function __construct() {
    $resources = CRM_Core_Resources::singleton();
    $path = $resources->getPath('org.civicrm.cdntaxreceipts', 'img/preview_mode.png');
    $this->_imageFilesPath = dirname($path) . '/';
  }

  abstract function open();

  abstract function addPage($pdf_variables);

  abstract function closeAndSend($filename);

  abstract function closeAndSave($filename);

  abstract function getDefaultLeftMargin();

  abstract function getDefaultTopMargin();
}
