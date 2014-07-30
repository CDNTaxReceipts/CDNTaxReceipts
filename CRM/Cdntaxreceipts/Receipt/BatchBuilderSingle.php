<?php

class CRM_Cdntaxreceipts_Receipt_BatchBuilderSingle extends CRM_Cdntaxreceipts_Receipt_BatchBuilder {

  private $_contributionIds;

  function __construct($contributionIds) {
    $this->_contributionIds = $contributionIds;
    parent::__construct();
  }

  /**
   * buildBatch - Return an array of receipts for issuing
   * @return array
   */
  function buildBatch() {

    // count and categorize contributions
    foreach ( $this->_contributionIds as $id ) {
      // TODO: This check should be encapsulated somewhere
      if ( cdntaxreceipts_eligibleForReceipt($id) ) {
        $history = CRM_Cdntaxreceipts_Receipt::getIssueHistory($id);
        $key = empty($history['original']) ? 'original' : 'duplicate';

        // TODO: Check for issued otherwise and or test to see if automagically works with annual etc.
        if ($key == 'duplicate') {
          $receipt = $history['original'];
          // Update method, if contact now has primary email etc.
          $receipt->updateIssueMethod();
          $method = $receipt->getIssueMethod();
          $this->_receiptBatch['duplicate'][$method][$id] = $receipt;
          $this->_receiptBatch['toIssue'][$id] = $receipt;
        }
        else {
          $receipt = CRM_Cdntaxreceipts_Receipt::createFromContribution($id);
          if ($receipt == NULL) {
            CRM_Core_Error::fatal( "CDNTaxReceipts: Could not retrieve details for this contribution: %1", array(1 => $id));
          }
          $method = $receipt->getIssueMethod();
          $this->_receiptBatch['original'][$method][$id] = $receipt;
          $this->_receiptBatch['toIssue'][$id] = $receipt;
        }
        $this->_receiptBatchSummary[$key][$method]++;
      }
    }
    return $this->_receiptBatch['toIssue'];
  }

  function updateBatch($issueParams, $previewMode, $originalOnly) {
    $statuses = $originalOnly ? array('original') : array('original', 'duplicate');
    $this->_receiptBatch['toIssue'] = array();
    foreach ($statuses as $status) {
      $this->_receiptBatch['toIssue'] += $this->_receiptBatch[$status]['email'];
      $this->_receiptBatch['toIssue'] += $this->_receiptBatch[$status]['print'];
    }
    return $this->_receiptBatch['toIssue'];
  }
}
