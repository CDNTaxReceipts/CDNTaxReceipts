<?php

abstract class CRM_Cdntaxreceipts_Receipt_BatchBuilder {
  protected $_errors;
  protected $_receiptBatch;
  protected $_receiptBatchSummary;

  function __construct() {
    $this->_errors = array();

    // TODO: Creating any structure here may be useless for now
    $this->_receiptBatch = array(
      'original'  => array('email' => array(), 'print' => array()),
      'duplicate' => array('email' => array(), 'print' => array()),
    );

    $this->_receiptBatchSummary = array(
      'original'  => array('email' => 0, 'print' => 0),
      'duplicate' => array('email' => 0, 'print' => 0),
    );
  }

  function getBatchSummary() {
    return $this->_receiptBatchSummary;
  }

  function getErrors() {
    return $this->_errors;
  }

  /**
   * buildBatch - Initial build of the batch to show the summary.
   *   This might include more receipts than are actually going to be issued.
   *   This should return a flat array for handling in a simple loop.
   * @return array
   */
  abstract function buildBatch();

  /**
   * updateBatch - After the batch has been built we might want to limit it with some information from the submitted
   *   form. This feels like a kludge but is OK for now.
   *   This should still return a flat array for handling in a simple loop.
   * @param $issueParams - array() - any other params we might need from the form exported values
   * @param $previewMode - Boolean, do not log or send by email
   * @param $originalOnly - Boolean, do not issue duplicates
   * @return array
   */
  abstract function updateBatch($issueParams, $previewMode, $originalOnly);
}
