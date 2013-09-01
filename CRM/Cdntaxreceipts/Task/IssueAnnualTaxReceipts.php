<?php

/**
 * This class provides the common functionality for issuing Annual Tax Receipts for
 * one or a group of contact ids.
 */
class CRM_Cdntaxreceipts_Task_IssueAnnualTaxReceipts extends CRM_Contact_Form_Task {

  const MAX_RECEIPT_COUNT = 1000;

  private $_receipts;
  private $_years;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {

    //check for permission to edit contributions
    if ( ! CRM_Core_Permission::check('edit contributions') ) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
    } 

    parent::preProcess();

    $thisYear = date("Y");
    $this->_years = array($thisYear, $thisYear - 1, $thisYear - 2);

    $receipts = array();
    foreach ( $this->_years as $year ) {
      $receipts[$year] = array('email' => 0, 'print' => 0, 'total' => 0, 'contrib' => 0);
    }

    // count and categorize contributions
    foreach ( $this->_contactIds as $id ) {
      foreach ( $this->_years as $year ) {
        list( $issuedOn, $receiptId ) = cdntaxreceipts_annual_issued_on($id, $year);

        $eligible = count(cdntaxreceipts_contributions_not_receipted($id, $year));
        if ( $eligible > 0 ) {
          list( $method, $email ) = cdntaxreceipts_sendMethodForContact($id);
          $receipts[$year][$method]++;
          $receipts[$year]['total']++;
          $receipts[$year]['contrib'] += $eligible;
        }
      }
    }

    $this->_receipts = $receipts;

  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {

    CRM_Utils_System::setTitle(ts('Issue Annual Tax Receipts'));

    // assign the counts
    $receipts = $this->_receipts;
    $receiptTotal = 0;
    foreach ( $this->_years as $year ) {
      $receiptTotal += $receipts[$year]['total'];
    }

    $this->assign('receiptCount', $receipts);
    $this->assign('receiptTotal', $receiptTotal);
    $this->assign('receiptYears', $this->_years);

    // add radio buttons
    foreach ( $this->_years as $year ) {
      $this->addElement('radio', 'receipt_year', NULL, ts('%1', array(1=>$year)), 'issue_' . $year);
    }
    $this->addRule('receipt_year', ts('Selection required'), 'required');

    $this->add('checkbox', 'is_preview', ts('Run in preview mode?'));

    $buttons = array(
      array(
        'type' => 'cancel',
        'name' => ts('Back'),
      ),
      array(
        'type' => 'next',
        'name' => 'Issue Tax Receipts',
        'isDefault' => TRUE,
        'js' => array('onclick' => "return submitOnce(this,'{$this->_name}','" . ts('Processing') . "');"),
      ),
    );
    $this->addButtons($buttons);

  }

  function setDefaultValues() {
    return array('receipt_year' => 'issue_' . (date("Y") - 1),);
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */

  function postProcess() {

    // lets get around the time limit issue if possible
    if ( ! ini_get( 'safe_mode' ) ) {
      set_time_limit( 0 );
    }

    // Issue 1895204: Turn off geocoding to avoid hitting Google API limits
    $config =& CRM_Core_Config::singleton();
    $oldGeocode = $config->geocodeMethod;
    unset($config->geocodeMethod);

    $params = $this->controller->exportValues($this->_name);
    $year = $params['receipt_year'];
    if ( $year ) {
      $year = substr($year, strlen('issue_')); // e.g. issue_2012
    }

    $previewMode = FALSE;
    if (isset($params['is_preview']) && $params['is_preview'] == 1 ) {
      $previewMode = TRUE;
    }

    /**
     * Drupal module include
     */
    //module_load_include('.inc','civicrm_cdntaxreceipts','civicrm_cdntaxreceipts');
    //module_load_include('.module','civicrm_cdntaxreceipts','civicrm_cdntaxreceipts');

    // start a PDF to collect receipts that cannot be emailed
    $receiptsForPrinting =& cdntaxreceipts_openCollectedPDF();

    $emailCount = 0;
    $printCount = 0;
    $failCount = 0;

    foreach ($this->_contactIds as $contactId ) {

      list( $issuedOn, $receiptId ) = cdntaxreceipts_annual_issued_on($contactId, $year);
      $contributions = cdntaxreceipts_contributions_not_receipted($contactId, $year);

      if ( $emailCount + $printCount + $failCount >= self::MAX_RECEIPT_COUNT ) {
        $status[] = ts('Maximum of %1 tax receipt(s) were sent. Please repeat to continue processing.', array(1=>self::MAX_RECEIPT_COUNT));
        break;
      }

      if ( empty($issuedOn) && count($contributions) > 0 ) { 

        list( $ret, $method ) = cdntaxreceipts_issueAnnualTaxReceipt($contactId, $year, $receiptsForPrinting, $previewMode);

        if ( $ret == 0 ) {
          $failCount++;
        }
        elseif ( $method == 'email' ) {
          $emailCount++;
        }
        else {
          $printCount++;
        }
      }
    }

    // 3. Set session status
    $status = array('');
    if ( $previewMode ) {
      $status[] = ts('%1 tax receipt(s) have been previewed.  No receipts have been issued.', array(1=>$printCount));
    }
    else {
      $status[] = ts('%1 tax receipt(s) were sent by email.', array(1=>$emailCount));
      $status[] = ts('%1 tax receipt(s) need to be printed.', array(1=>$printCount));
    }

    if ( $failCount > 0 ) {
      $status[] = ts('%1 tax receipt(s) failed to process.', array(1=>$failCount));
    }

    CRM_Core_Session::setStatus($status, '', 'info');

    // Issue 1895204: Reset geocoding
    $config->geocodeMethod = $oldGeocode;

    // 4. send the collected PDF for download
    // NB: This exits if a file is sent.
    cdntaxreceipts_sendCollectedPDF($receiptsForPrinting, 'Receipts-To-Print-' . REQUEST_TIME . '.pdf');  // EXITS.
  }
}

