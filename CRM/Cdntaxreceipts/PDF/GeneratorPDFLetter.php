<?php

class CRM_Cdntaxreceipts_PDF_GeneratorPDFLetter  extends CRM_Cdntaxreceipts_PDF_Generator {

  protected $_imageFilesPath;
  protected $_html;

  function __construct($msgTemplateId) {
    parent::__construct();
    // Get template and PDF format
  }

  function open() {}

  function addPage($pdf_variables) {}

  function closeAndSend($filename) {
    CRM_Utils_PDF_Utils::html2pdf($this->_html, $filename, TRUE, $this->_pdfFormat);
    CRM_Utils_System::civiExit(1);
  }

  function closeAndSave($filename) {
    CRM_Utils_PDF_Utils::html2pdf($this->_html, $filename, TRUE, $this->_pdfFormat);
  }

  function getDefaultLeftMargin() { return 0;}

  function getDefaultTopMargin() { return 0;}
}
