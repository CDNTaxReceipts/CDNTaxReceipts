<?php

/**
 * @file PdfFpdi.php - define subclass of fpdi
 */

require_once 'tcpdf/tcpdf.php';
require_once('FPDI/fpdi.php');

class CRM_Cdntaxreceipts_PdfFpdi extends FPDI {
  /**
   * "Remembers" the template id of the imported page
   */
  var $_tplIdx;

  /**
   * include a background template for every page
   */
  function Header() {
    $pdf_template_file = CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'receipt_pdftemplate');
    if (!empty($pdf_template_file)) {

      if (is_null($this->_tplIdx)) {
        $pdf_template_file = CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'receipt_pdftemplate');
        $this->setSourceFile($pdf_template_file);
        $this->_tplIdx = $this->importPage(1);
      }
      $this->useTemplate($this->_tplIdx);
    }
    else {
      $this->parsers = array();
    }
  }

  function Footer() {}
}

