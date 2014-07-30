<?php

class CRM_Cdntaxreceipts_PDF_GeneratorPDFLetter  extends CRM_Cdntaxreceipts_PDF_Generator {

  /**
   * @var CRM_Core_BAO_MessageTemplate - The CiviCRM message template object
   */
  protected $_template;
  /**
   * @var array - an array of pages
   */
  protected $_htmlPages;
  /**
   * @var text - The HTML string from the CiviCRM message template
   */
  protected $_html_message;
  protected $_pdfFormat;

  function __construct($msgTemplateId) {
    parent::__construct();

    $this->_htmlPages = array();

    // Get template and PDF format
    // TODO: Use civicrm_api here
    $defaults = array();
    $template_finder = new CRM_Core_BAO_MessageTemplate();
    $params = array('id' => intval($msgTemplateId));

    $template = $template_finder->retrieve($params, $defaults);
    $this->_template = $template;
    $this->_html_message = $template->msg_html;

    $this->_pdfFormat = $format = CRM_Core_BAO_PdfFormat::getById($template->pdf_format_id);
  }

  function open() {}

  /**
   * addPage - Function to add a new HTML page to the tax receipt
   *   Code is heavily borrowed from CRM_Contact_Form_Task_PDFLetterCommon::postProcess
   * @param $pdf_variables
   * @return bool
   */
  function addPage($pdf_variables) {
    $skipOnHold = FALSE;
    $skipDeceased = TRUE;

    // Add image url
    $pdf_variables['image_files_url'] = $this->_imageFilesURL;

    $contactId = $pdf_variables['contact_id'];

    $pdf_variables = self::formatPDFVariables($pdf_variables);

    $tokens = array();
    CRM_Utils_Hook::tokens($tokens);
    $categories = array_keys($tokens) + array('tax_receipt');

    $html_message = $this->_html_message;

    CRM_Contact_Form_Task_PDFLetterCommon::formatMessage($html_message);

    $messageToken = CRM_Utils_Token::getTokens($html_message);

    $returnProperties = array();
    if (isset($messageToken['contact'])) {
      foreach ($messageToken['contact'] as $key => $value) {
        $returnProperties[$value] = 1;
      }
    }

    $params = array('contact_id' => $contactId);

    list($contact) = CRM_Utils_Token::getTokenDetails($params,
      $returnProperties,
      $skipOnHold,
      $skipDeceased,
      NULL,
      $messageToken,
      'CRM_Cdntaxreceipts_PDF_GeneratorPDFLetter'
    );
    if (civicrm_error($contact)) {
      return FALSE;
    }
    $contact[$contactId] = $contact[$contactId] + $pdf_variables;

    $tokenHtml = CRM_Utils_Token::replaceContactTokens($html_message, $contact[$contactId], TRUE, $messageToken);
    $tokenHtml = CRM_Utils_Token::replaceHookTokens($tokenHtml, $contact[$contactId], $categories, TRUE);

    if (defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY) {
      $smarty = CRM_Core_Smarty::singleton();
      // also add the contact tokens to the template
      $smarty->assign_by_ref('contact', $contact);
      $tokenHtml = $smarty->fetch("string:$tokenHtml");
    }

    $this->_htmlPages[] = $tokenHtml;

    //TODO: Should we create an activity? Probably not here though
    //self::createActivities($form, $html_message, $form->_contactIds);
  }

  function closeAndSend($filename) {
    CRM_Utils_PDF_Utils::html2pdf($this->_htmlPages, $filename, FALSE, $this->_pdfFormat);
    CRM_Utils_System::civiExit(1);
  }

  function closeAndSave($filename) {
    $pdf_bin = CRM_Utils_PDF_Utils::html2pdf($this->_htmlPages, $filename, TRUE, $this->_pdfFormat);
    file_put_contents($filename, $pdf_bin);
  }

  function getDefaultLeftMargin() {
    return CRM_Core_BAO_PdfFormat::getValue('margin_left', $this->_pdfFormat);
  }

  function getDefaultTopMargin() {
    return CRM_Core_BAO_PdfFormat::getValue('margin_top', $this->_pdfFormat);
  }

  static function formatPDFVariables($pdf_variables) {
    $formatted = array();
    foreach ($pdf_variables as $key => $value) {
      $formatted['tax_receipt.' . $key] = $value;
    }
    return $formatted;
  }
}
