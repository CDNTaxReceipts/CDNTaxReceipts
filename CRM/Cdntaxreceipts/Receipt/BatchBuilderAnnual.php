<?php

class CRM_Cdntaxreceipts_Receipt_BatchBuilderAnnual extends CRM_Cdntaxreceipts_Receipt_BatchBuilder {

  private $_contactIds;
  private $_years;

  function __construct($contactIds, $years) {
    $this->_contactIds = $contactIds;
    $this->_years = $years;
    parent::__construct();

    $receipts = $this->_receiptBatchSummary;
    $this->_receiptBatchSummary = array();
    foreach ( $this->_years as $year ) {
      $this->_receiptBatchSummary[$year] = $receipts;
    }
  }

  /**
   * buildBatch - Return an array of receipts for issuing
   * @return array
   */
  function buildBatch() {
    $histories = array();
    // count and categorize contributions
    foreach ($this->_years as $year) {
      foreach ( $this->_contactIds as $contactId ) {
        $contributions = cdntaxreceipts_contributions_not_receipted($contactId, $year);

        $eligible_contribs = array();
        if ( count($contributions) > 0 ) {
          foreach($contributions as $contrib) {
            if ( cdntaxreceipts_eligibleForReceipt($contrib['contribution_id']) ) {
              $eligible_contribs[] = $contrib;
              $histories[$contrib['contribution_id']] = CRM_Cdntaxreceipts_Receipt::getIssueHistory($contrib['contribution_id']);
            }
          }
          $eligible = count($eligible_contribs);
          $valid = $this->validateAnnual($eligible_contribs, $histories);
          // TODO: This check should be encapsulated somewhere
          if ($eligible > 0 && $valid) {
            $key = empty($history['original']) ? 'original' : 'duplicate';
            // TODO: Check for issued otherwise and or test to see if automagically works with annual etc.
            if ($key == 'duplicate') {
              $receipt = $history['original'];
              // Update method, if contact now has primary email etc.
              $receipt->updateIssueMethod();
              $method = $receipt->getIssueMethod();
              $this->_receiptBatch['duplicate'][$method][$contactId] = $receipt;
              $this->_receiptBatch['toIssue'][$contactId] = $receipt;
            }
            else {
              $receipt = CRM_Cdntaxreceipts_Receipt::createFromContributionList('annual', $contactId, $contributions);
              if ($receipt == NULL) {
                CRM_Core_Error::fatal( "CDNTaxReceipts: Could not retrieve details for this contact's contributions: %1", array(1 => $contactId));
              }
              $method = $receipt->getIssueMethod();
              $this->_receiptBatch['original'][$method][$contactId] = $receipt;
              $this->_receiptBatch['toIssue'][$contactId] = $receipt;
            }
            $this->_receiptBatchSummary[$year][$method]++;
            $this->_receiptBatchSummary[$year]['total']++;
            $this->_receiptBatchSummary[$year]['contrib'] += $eligible;
            $this->_receiptBatchSummary[$year][$key][$method]++;
          }
          else {
            $this->_receiptBatchSummary[$year]['invalid_contacts']++;
            $this->_receiptBatchSummary[$year]['invalid_contributions'] += count($contributions);
          }
        }
      }
    }

    //dpm($this->_receiptBatch);
    return $this->_receiptBatch['toIssue'];
  }

  function updateBatch($issueParams, $originalOnly) {
    $statuses = $originalOnly ? array('original') : array('original', 'duplicate');
    $this->_receiptBatch['toIssue'] = array();
    foreach ($statuses as $status) {
      $this->_receiptBatch['toIssue'] += $this->_receiptBatch[$status]['email'];
      $this->_receiptBatch['toIssue'] += $this->_receiptBatch[$status]['print'];
    }
    return $this->_receiptBatch['toIssue'];
  }

  function validateAnnual($eligible_contribs, $histories) {
    // TODO: Actually do something
    return TRUE;
  }
}
