<?php

require_once('CRM/Contribute/Form/Task.php');

/**
 * This class provides the common functionality for issuing CDN Tax Receipts for
 * one or a group of contact ids.
 */
class CRM_Cdntaxreceipts_Task_IssueSingleTaxReceipts extends CRM_Contribute_Form_Task {

  const MAX_RECEIPT_COUNT = 1000;

  private $_receipts;
  private $_batch;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    //check for permission to edit contributions
    if ( ! CRM_Core_Permission::check('issue cdn tax receipts') ) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page', array('domain' => 'org.civicrm.cdntaxreceipts')));
    }
    parent::preProcess();

    $this->_batch = new CRM_Cdntaxreceipts_ReceiptBatch(new CRM_Cdntaxreceipts_ReceiptBatchBuilderSingle($this->_contributionIds));
    $this->_receipts = $this->_batch->getPreviewSummary();

  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {

    CRM_Utils_System::setTitle(ts('Issue Tax Receipts', array('domain' => 'org.civicrm.cdntaxreceipts')));

    // assign the counts
    $receipts = $this->_receipts;
    $originalTotal = $receipts['original']['print'] + $receipts['original']['email'];
    $duplicateTotal = $receipts['duplicate']['print'] + $receipts['duplicate']['email'];
    $receiptTotal = $originalTotal + $duplicateTotal;
    $this->assign('receiptCount', $receipts);
    $this->assign('originalTotal', $originalTotal);
    $this->assign('duplicateTotal', $duplicateTotal);
    $this->assign('receiptTotal', $receiptTotal);

    // add radio buttons
    $this->addElement('radio', 'receipt_option', NULL, ts('Issue tax receipts for the %1 unreceipted contributions only.', array(1=>$originalTotal, 'domain' => 'org.civicrm.cdntaxreceipts')), 'original_only');
    $this->addElement('radio', 'receipt_option', NULL, ts('Issue tax receipts for all %1 contributions. Previously-receipted contributions will be marked \'duplicate\'.', array(1=>$receiptTotal, 'domain' => 'org.civicrm.cdntaxreceipts')), 'include_duplicates');
    $this->addRule('receipt_option', ts('Selection required', array('domain' => 'org.civicrm.cdntaxreceipts')), 'required');

    $this->add('checkbox', 'is_preview', ts('Run in preview mode?', array('domain' => 'org.civicrm.cdntaxreceipts')));

    $buttons = array(
      array(
        'type' => 'cancel',
        'name' => ts('Back', array('domain' => 'org.civicrm.cdntaxreceipts')),
      ),
      array(
        'type' => 'next',
        'name' => 'Issue Tax Receipts',
        'isDefault' => TRUE,
        'js' => array('onclick' => "return submitOnce(this,'{$this->_name}','" . ts('Processing', array('domain' => 'org.civicrm.cdntaxreceipts')) . "');"),
      ),
    );
    $this->addButtons($buttons);

  }

  function setDefaultValues() {
    return array('receipt_option' => 'original_only');
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

    $originalOnly = FALSE;
    if ($params['receipt_option'] == 'original_only') {
      $originalOnly = TRUE;
    }

    $previewMode = FALSE;
    if (isset($params['is_preview']) && $params['is_preview'] == 1 ) {
      $previewMode = TRUE;
    }

    $batchCounts = $this->postProcessBatch($this->_batch, $previewMode, $originalOnly);

    // 3. Set session status
    if ( $previewMode ) {
      $status = ts('%1 tax receipt(s) have been previewed.  No receipts have been issued.',
        array(1=>$batchCounts['print'], 'domain' => 'org.civicrm.cdntaxreceipts'));
      CRM_Core_Session::setStatus($status, '', 'success');
    }
    else {
      $status = ts('%1 tax receipt(s) were sent by email.', array(1=>$batchCounts['email'], 'domain' => 'org.civicrm.cdntaxreceipts'));
      CRM_Core_Session::setStatus($status, '', 'success');
      $status = ts('%1 tax receipt(s) need to be printed.', array(1=>$batchCounts['print'], 'domain' => 'org.civicrm.cdntaxreceipts'));
      CRM_Core_Session::setStatus($status, '', 'success');
    }

    if ( $batchCounts['fail'] > 0 ) {
      $status = ts('%1 tax receipt(s) failed to process.', array(1=>$batchCounts['fail'], 'domain' => 'org.civicrm.cdntaxreceipts'));
      CRM_Core_Session::setStatus($status, '', 'error');
    }

    // Issue 1895204: Reset geocoding
    $config->geocodeMethod = $oldGeocode;

    // 4. send the collected PDF for download
    // NB: This exits if a file is sent.
    $collectedPDF = $this->_batch->getCollectedPDF();
    $collectedPDF->closeAndSend('Receipts-To-Print-' . (int) $_SERVER['REQUEST_TIME'] . '.pdf');
  }

  /**
   * @param $previewMode
   * @param $originalOnly
   * @return mixed
   */
  protected function postProcessBatch(CRM_Cdntaxreceipts_ReceiptBatch $batch, $previewMode, $originalOnly) {
    $batch->issue($previewMode, $originalOnly);
    $errors = $batch->getErrors();
    foreach ($errors as $severity => $messages) {
      foreach ($messages as $msg) {
        CRM_Core_Session::setStatus($msg, '', $severity);
      }
    }
    $batchCounts = $batch->getCounts();
    return $batchCounts;
  }
}

