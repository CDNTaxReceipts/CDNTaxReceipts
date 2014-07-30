<?php

require_once('CRM/Contribute/Form/Task.php');

/**
 * This class provides the common functionality for issuing CDN Tax Receipts for
 * one or a group of contact ids.
 */
class CRM_Cdntaxreceipts_Task_IssueTaxReceiptsCommon extends CRM_Contribute_Form_Task {

  protected $_receipts;
  protected $_batch;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    parent::preProcess();
    //check for permission to edit contributions
    if ( ! CRM_Core_Permission::check('issue cdn tax receipts') ) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page', array('domain' => 'org.civicrm.cdntaxreceipts')));
    }
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {

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
    $config = CRM_Core_Config::singleton();
    $oldGeocode = $this->startPostProcess($config);

    $params = $this->controller->exportValues($this->_name);

    $originalOnly = FALSE;
    if ($params['receipt_option'] == 'original_only') {
      $originalOnly = TRUE;
    }

    $previewMode = FALSE;
    if (isset($params['is_preview']) && $params['is_preview'] == 1 ) {
      $previewMode = TRUE;
    }

    $batchCounts = $this->postProcessBatch($this->_batch, $params, $previewMode, $originalOnly);
    $this->setSessionStatus($previewMode, $batchCounts);
    $this->endPostProcess($oldGeocode, $config);
  }

  /**
   * @param CRM_Cdntaxreceipts_Receipt_Batch - batch to run
   * @param $issueParams - Let the batch take any params from the form it wants
   * @param $previewMode - Special case for this param, all batch types support it
   * @param $originalOnly - Special case too? Maybe this one should just be in issueParams
   * @return mixed
   */
  protected function postProcessBatch(CRM_Cdntaxreceipts_Receipt_Batch $batch, $issueParams = array(),
                                      $previewMode = FALSE, $originalOnly = FALSE) {
    $batch->issue($issueParams, $previewMode, $originalOnly);
    $errors = $batch->getErrors();
    foreach ($errors as $severity => $messages) {
      foreach ($messages as $msg) {
        CRM_Core_Session::setStatus($msg, '', $severity);
      }
    }
    $batchCounts = $batch->getCounts();
    return $batchCounts;
  }

  /**
   * @param $previewMode
   * @param $batchCounts
   */
  protected function setSessionStatus($previewMode, $batchCounts) {
// 3. Set session status
    if ($previewMode) {
      $status = ts('%1 tax receipt(s) have been previewed.  No receipts have been issued.',
        array(1 => $batchCounts['print'], 'domain' => 'org.civicrm.cdntaxreceipts'));
      CRM_Core_Session::setStatus($status, '', 'success');
    }
    else {
      $status = ts('%1 tax receipt(s) were sent by email.',
        array( 1 => $batchCounts['email'], 'domain' => 'org.civicrm.cdntaxreceipts'));
      CRM_Core_Session::setStatus($status, '', 'success');
      $status = ts('%1 tax receipt(s) need to be printed.',
        array(1 => $batchCounts['print'],'domain' => 'org.civicrm.cdntaxreceipts'));
      CRM_Core_Session::setStatus($status, '', 'success');
    }

    if ($batchCounts['fail'] > 0) {
      $status = ts('%1 tax receipt(s) failed to process.',
        array(1 => $batchCounts['fail'],'domain' => 'org.civicrm.cdntaxreceipts'));
      CRM_Core_Session::setStatus($status, '', 'error');
    }
  }

  /**
   * startPostProcess - Turn off geocode and set time limit
   * @param object - Core global $config
   * @return array
   */
  protected function startPostProcess($config) {
    // lets get around the time limit issue if possible
    if (!ini_get('safe_mode')) {
      set_time_limit(0);
    }
    // Issue 1895204: Turn off geocoding to avoid hitting Google API limits
    $oldGeocode = $config->geocodeMethod;
    unset($config->geocodeMethod);
    return $oldGeocode;
  }

  /**
   * @param $oldGeocode
   * @param $config
   */
  protected function endPostProcess($oldGeocode, $config) {
    // Issue 1895204: Reset geocoding
    $config->geocodeMethod = $oldGeocode;

    // 4. send the collected PDF for download
    // NB: This exits if a file is sent.
    $collectedPDF = $this->_batch->getCollectedPDF();
    $collectedPDF->closeAndSend('Receipts-To-Print-' . (int) $_SERVER['REQUEST_TIME'] . '.pdf');
  }
}

