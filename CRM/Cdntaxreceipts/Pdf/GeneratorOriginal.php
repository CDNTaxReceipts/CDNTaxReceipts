<?php


class CRM_Cdntaxreceipts_PDF_GeneratorOriginal extends CRM_Cdntaxreceipts_PDF_Generator {

  private $pdf;

  function __construct() {
    parent::__construct();
  }

  function getDefaultLeftMargin() {
    return 12;
  }

  function getDefaultTopMargin() {
    return 6;
  }


  /**
   * open - Initialize the PDF file and set defaults
   *
   * Was OpenCollectedPDF
   */
  function open() {

    $this->pdf = new CRM_Cdntaxreceipts_Pdf_Fpdi(PDF_PAGE_ORIENTATION, PDF_UNIT, 'LETTER', TRUE, 'UTF-8', FALSE);
    $this->pdf->Open();

    $this->pdf->SetAuthor(CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'org_name', NULL, ''));

    $mymargin_left = 12;
    $mymargin_top = 6;
    $mymargin_right = 12;
    $this->pdf->SetMargins($mymargin_left, $mymargin_top, $mymargin_right);

    $this->pdf->setJPEGQuality('100');
    $this->pdf->SetAutoPageBreak('', $margin=0);

  }

  function addPage($pdf_variables) {
    if (!isset($this->pdf)) {
      $this->open();
    }
    $this->pdf->AddPage();

    $pdf_variables['pdf_img_files_path'] = $this->_imageFilesPath;
    //dpm($pdf_variables);

    if ($pdf_variables["is_duplicate"]) {
      // print duplicate copy
     $this->writePage($pdf_variables);
    }
    elseif (!$pdf_variables["is_duplicate"]) {
      // print original AND duplicate copy
      $marginTop = $pdf_variables["mymargin_top"];
      $pdf_variables["is_duplicate"] = FALSE;
      $this->writePage($pdf_variables);
      $pdf_variables["mymargin_top"] = $marginTop + 90;
      $pdf_variables["is_duplicate"] = TRUE;
      $this->writePage($pdf_variables);
      $pdf_variables["mymargin_top"] = $marginTop + 90*2;
      $pdf_variables["is_duplicate"] = TRUE;
      $this->writePage($pdf_variables);
    }
  }

  private function writePage($pdf_variables) {

    // Extract variables
    $preview_mode = $pdf_variables["preview_mode"];
    $mymargin_left = $pdf_variables["mymargin_left"];
    $mymargin_top = $pdf_variables["mymargin_top"];
    $is_duplicate = $pdf_variables["is_duplicate"];
    $pdf_img_files_path = $pdf_variables["pdf_img_files_path"];
    $line_1 = $pdf_variables["line_1"];
    $source_funds = $pdf_variables["source_funds"];
    $amount = $pdf_variables["amount"];
    $display_date = $pdf_variables["display_date"];
    $issued_on = $pdf_variables["issued_on"];
    $receipt_number = $pdf_variables["receipt_number"];
    $displayname = $pdf_variables["displayname"];
    $address_line_1 = $pdf_variables["address_line_1"];
    $address_line_1b = $pdf_variables["address_line_1b"];
    $address_line_2 = $pdf_variables["address_line_2"];
    $address_line_3 = $pdf_variables["address_line_3"];
    $inkind_values = $pdf_variables["inkind_values"];
    $display_year = $pdf_variables["display_year"];
    $issue_type = $pdf_variables["issue_type"];
    $receipt_contributions = $pdf_variables['receipt_contributions'];

    $pdf = $this->pdf;
    // Middle center section
    if ( $preview_mode ) {
      $pdf->Image($pdf_img_files_path . 'preview_mode.png', $mymargin_left + 65, $mymargin_top, '', 45);
    }
    else if ( $is_duplicate ) {
      $pdf->Image($pdf_img_files_path . 'duplicate_trans.png', $mymargin_left + 65, $mymargin_top, '', 45);
    }
    // Top left section
    $pdf_template_file = CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'receipt_pdftemplate');
    if (!empty($pdf_template_file)) {
    }
    else {
      $pdf->Image(CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'receipt_logo', NULL, $pdf_img_files_path . 'your-logo.png'), $mymargin_left, $mymargin_top, '', 30);

      // Top right section
      $pdf->SetFont('Helvetica', '', 8);
      $pdf->SetY($mymargin_top);
      $pdf->Write(10, CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'org_name'), '', 0, 'R', TRUE, 0, FALSE, FALSE, 0);
      $pdf->SetY($mymargin_top + 4);
      $pdf->Write(10, CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'org_address_line1'), '', 0, 'R', TRUE, 0, FALSE, FALSE, 0);
      $pdf->SetY($mymargin_top + 8);
      $pdf->Write(10, CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'org_address_line2'), '', 0, 'R', TRUE, 0, FALSE, FALSE, 0);
      $pdf->SetY($mymargin_top + 12);
      if ( CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'org_fax' ) == '' ) {
        $pdf->Write(10, 'Tel: ' . CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'org_tel'), '', 0, 'R', TRUE, 0, FALSE, FALSE, 0);
      }
      else {
        $pdf->Write(10, 'Tel: ' . CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'org_tel') . '; Fax: ' . CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'org_fax'), '', 0, 'R', TRUE, 0, FALSE, FALSE, 0);
      }
      $pdf->SetFont('Helvetica', 'I', 8);
      $pdf->SetY($mymargin_top + 16);
      $pdf->Write(10, 'Email: ' . CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'org_email') . '; Website: ' . CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'org_web'), '', 0, 'R', TRUE, 0, FALSE, FALSE, 0);
      $pdf->SetY($mymargin_top + 20);
      $pdf->Write(10, 'Charitable Registration ' . CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'org_charitable_no'), '', 0, 'R', TRUE, 0, FALSE, FALSE, 0);
    }

    // Right section
    $x_detailscolumn = 120;
    $y_detailscolumnstart = 22;
    $pdf_template_file = CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'receipt_pdftemplate');
    if (!empty($pdf_template_file)) {
    }
    else {
      $background_image = CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'receipt_watermark');
      if ( $background_image ) $pdf->Image(CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'receipt_watermark'), $mymargin_left + $x_detailscolumn, $mymargin_top + $y_detailscolumnstart + 6, '', 40);
    }
    $pdf->SetXY($mymargin_left + $x_detailscolumn, $mymargin_top + $y_detailscolumnstart + 6);
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->Write(10, "Issue Date: " . $issued_on);
    $pdf->SetXY($mymargin_left + $x_detailscolumn, $mymargin_top + $y_detailscolumnstart + 12);
    $pdf->Write(10, "Received on: " . $display_date);
    $pdf->SetXY($mymargin_left + $x_detailscolumn, $mymargin_top + $y_detailscolumnstart + 18);
    $pdf->Write(10, "Eligible Amount:  $" . number_format($amount, 2));
    $pdf->SetXY($mymargin_left + $x_detailscolumn, $mymargin_top + $y_detailscolumnstart + 34);

    if ($issue_type == 'annual') {
      $pdf->Write(10, ts("Thank you for giving in %1!", array( 1 => $display_year)));
      // $pdf->Write(10, "Contribution(s): ");
      // foreach ($receipt_contributions as $key => $value) {
      //   $test = $receipt_contributions[$key]['contribution_id'];
      //   $pdf->Write(10, $receipt_contributions[$key]['contribution_id'] . ' ');
      // }
    }
    else if (strlen($source_funds) < 36 && strlen($source_funds) > 0) {
      $pdf->Write(10, "Source: " . $source_funds);
    }
    else if (strlen($source_funds) > 0) {
      $source_funds_words = explode(" ", substr($source_funds, 0, 36));
      $source_funds_lastbit = array_pop($source_funds_words);
      $pdf->Write(10, "Source: " . implode(" ", $source_funds_words));
      $source_funds_count = count($source_funds_words);
      // $source_funds_nextline = array_splice(explode(" ", $source_funds), $source_funds_count);
      $exploded = explode(" ", $source_funds);
      $source_funds_nextline = array_splice($exploded, $source_funds_count);
      $pdf->SetXY($mymargin_left + $x_detailscolumn + 16, $mymargin_top + $y_detailscolumnstart + 38);
      $pdf->Write(10, implode(" ", $source_funds_nextline));
    }

    // Left section
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->SetY($mymargin_top + $y_detailscolumnstart + 6);
    $pdf->Write(10, "Receipt No: " . $receipt_number);
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->SetY($mymargin_top + $y_detailscolumnstart + 10);
    $pdf->Write(10, "Received from: ", '', 0, 'L', TRUE, 0, FALSE, FALSE, 0);
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetXY($mymargin_left+10, $mymargin_top + $y_detailscolumnstart + 20);
    $pdf->Write(10, strtoupper($displayname), '', 0, 'L', TRUE, 0, FALSE, FALSE, 0);

    if (!$address_line_1b){
      $pdf->SetFont('Helvetica', '', 10);
      $pdf->SetXY($mymargin_left+10, $mymargin_top + $y_detailscolumnstart + 24);
      $pdf->Write(10, strtoupper($address_line_1), '', 0, 'L', TRUE, 0, FALSE, FALSE, 0);
      $pdf->SetXY($mymargin_left+10, $mymargin_top + $y_detailscolumnstart + 28);
      $pdf->Write(10, strtoupper($address_line_2), '', 0, 'L', TRUE, 0, FALSE, FALSE, 0);
      $pdf->SetXY($mymargin_left+10, $mymargin_top + $y_detailscolumnstart + 32);
      $pdf->Write(10, strtoupper($address_line_3), '', 0, 'L', TRUE, 0, FALSE, FALSE, 0);
    }
    else {
      $pdf->SetFont('Helvetica', '', 10);
      $pdf->SetXY($mymargin_left+10, $mymargin_top + $y_detailscolumnstart + 24);
      $pdf->Write(10, strtoupper($address_line_1), '', 0, 'L', TRUE, 0, FALSE, FALSE, 0);
      $pdf->SetXY($mymargin_left+10, $mymargin_top + $y_detailscolumnstart + 28);
      $pdf->Write(10, strtoupper($address_line_1b), '', 0, 'L', TRUE, 0, FALSE, FALSE, 0);
      $pdf->SetXY($mymargin_left+10, $mymargin_top + $y_detailscolumnstart + 32);
      $pdf->Write(10, strtoupper($address_line_2), '', 0, 'L', TRUE, 0, FALSE, FALSE, 0);
      $pdf->SetXY($mymargin_left+10, $mymargin_top + $y_detailscolumnstart + 36);
      $pdf->Write(10, strtoupper($address_line_3), '', 0, 'L', TRUE, 0, FALSE, FALSE, 0);
    }

    // If this is an in-kind donation
    if ( !empty($inkind_values) ) {

      $inkind_str = 'Donation in-kind: ' . $inkind_values[0];

      if (!empty($inkind_values[3])) {
        if ($inkind_values[3] < $amount) {
          $inkind_str = 'Donation in-kind: ' . $inkind_values[0] . ' - ' . 'Cost: ' . $inkind_values[3];
        }
        else
        {
          $inkind_str = 'Donation in-kind: ' . $inkind_values[0];
        }
      }

      $pdf->SetFont('Helvetica', '', 8);
      $pdf->SetY($mymargin_top + 74);
      $pdf->Write(10, $inkind_str, ' - ', 0, 'L', TRUE, 0, FALSE, FALSE, 0);

      $appraiser = $inkind_values[1] . ' - '. $inkind_values[2];
      $pdf->SetFont('Helvetica', 'B', 8);
      $pdf->SetXY($mymargin_left + $x_detailscolumn, $mymargin_top + $y_detailscolumnstart + 24);

      if (strlen($appraiser) < 36) {
        $pdf->Write(10, "Appraiser: " . $appraiser);
      }
      else {
        $appraiser_words = explode(" ", substr($appraiser, 0, 36));
        $appraiser_lastbit = array_pop($appraiser_words);
        $pdf->Write(10, "Appraiser: " . implode(" ", $appraiser_words));
        $appraiser_count = count($appraiser_words);
        $appraiser_nextline = array_splice(explode(" ", $appraiser), $appraiser_count);
        $pdf->SetXY($mymargin_left + $x_detailscolumn + 16, $mymargin_top + $y_detailscolumnstart + 28);
        $pdf->Write(10, implode(" ", $appraiser_nextline));
      }

    }

    // Bottom left section
    if (!empty($pdf_template_file)) {
    }
    else {
      $pdf->SetFont('Helvetica', 'B', 8);
      $pdf->SetY($mymargin_top + 72);
      $pdf->Write(10, $line_1, '', 0, 'L', TRUE, 0, FALSE, FALSE, 0);
      $pdf->SetFont('Helvetica', '', 8);
      $pdf->SetY($mymargin_top + 76, 0, 'L', TRUE, 0, FALSE, FALSE, 0);
      $pdf->Write(10, 'Canadian Revenue Agency: www.cra-arc.gc.ca/charities');

      // Bottom center section
      $pdf->SetFont('Helvetica', 'B', 8);
      $pdf->SetXY($mymargin_left + 92, $mymargin_top + 74);
      $pdf->Write(10, 'Thank you!');

      // Bottom right section
      $signature = CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'receipt_authorized_signature_text');
      if ( $signature == '' ) {
        $signature = ts('Authorized Signature', array('domain' => 'org.civicrm.cdntaxreceipts'));
      }
      $sig_offset = strlen($signature) - 20;
      $pdf_template_file = CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'receipt_pdftemplate');

      $pdf->Image(CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'receipt_signature', NULL, $pdf_img_files_path . 'authorized-signature.png'), $mymargin_left + 137, $mymargin_top + 67, '', 15);
      $pdf->Line($mymargin_left + 136, $mymargin_top + 79, $mymargin_left + 186, $mymargin_top + 79);
      $pdf->SetXY($mymargin_left + 148 - $sig_offset, $mymargin_top + 76);
      $pdf->SetFont("Helvetica", "I", 7);
      $pdf->Write(10, $signature);
    }

    // Line at the bottom
    $pdf->Line($mymargin_left, $mymargin_top + 85, 198, $mymargin_top + 85, 'dash');
  }


  function closeAndSend($filename) {
    if (isset($this->pdf)) {
      if ( $this->pdf->getNumPages() > 0 ) {
        $this->pdf->Output($filename, 'D');
        // TODO: should we move civiExit outside
        CRM_Utils_System::civiExit();
      }
      else {
        $this->pdf->Close();
      }
    }
  }

  function closeAndSave($pdf_file) {
    if (isset($this->pdf)) {
      // close and output the file
      $this->pdf->Close();
      $this->pdf->Output($pdf_file, 'F');
    }
  }
}

