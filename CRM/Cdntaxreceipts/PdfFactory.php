<?php

class CRM_Cdntaxreceipts_PdfFactory {

  private static $_singleton = NULL;

  /**
   * Get or set the single instance of CRM_Cdntaxreceipts_PdfFactory
   *
   * @param $instance CRM_Core_Resources, new copy of the manager
   * @return CRM_Core_Resources
   */
  public static function singleton() {
    if (self::$_singleton === NULL) {

      self::$_singleton = new CRM_Cdntaxreceipts_PdfFactory();
    }
    return self::$_singleton;
  }

  public static function getPDFLib() {
    $pdf_template_id = CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'pdf_template_id');
    //dpm($pdf_template_id);
    if (empty($pdf_template_id)) {
      return new CRM_Cdntaxreceipts_PDFGeneratorOriginal();
    }
    else {
      //TODO: Create a new class to use PDF letter templates and use it here
      return new CRM_Cdntaxreceipts_PDFGeneratorOriginal();
    }
  }
}
