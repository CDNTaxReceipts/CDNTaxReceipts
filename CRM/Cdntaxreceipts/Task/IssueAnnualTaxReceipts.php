<?php

/**
 * This class provides the common functionality for issuing Annual Tax Receipts for
 * one or a group of contact ids.
 */
class CRM_Cdntaxreceipts_Task_IssueAnnualTaxReceipts extends CRM_Cdntaxreceipts_Task_IssueTaxReceiptsCommon {

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
    parent::preProcess();
    $thisYear = date("Y");
    $this->_years = array($thisYear, $thisYear - 1, $thisYear - 2);

    $this->_batch = new CRM_Cdntaxreceipts_Receipt_Batch(
      new CRM_Cdntaxreceipts_Receipt_BatchBuilderAnnual($this->_contactIds, $this->_years));


  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {

    CRM_Utils_System::setTitle(ts('Issue Annual Tax Receipts', array('domain' => 'org.civicrm.cdntaxreceipts')));

    CRM_Core_Resources::singleton()->addStyleFile('org.civicrm.cdntaxreceipts', 'css/civicrm_cdntaxreceipts.css');

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
      $this->addElement('radio', 'receipt_year', NULL, $year, 'issue_' . $year);
    }
    $this->addRule('receipt_year', ts('Selection required', array('domain' => 'org.civicrm.cdntaxreceipts')), 'required');

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
    return array('receipt_year' => 'issue_' . (date("Y") - 1),);
  }
}

