<?php

class CRM_Cdntaxreceipts_PDF_Factory {

  public static function getPDFLib() {
    $pdf_template_id = CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'receipt_pdflettertemplate', NULL, 0);
    if (empty($pdf_template_id)) {
      return new CRM_Cdntaxreceipts_PDF_GeneratorOriginal();
    }
    else {
      return new CRM_Cdntaxreceipts_PDF_GeneratorPDFLetter($pdf_template_id);
    }
  }
}
