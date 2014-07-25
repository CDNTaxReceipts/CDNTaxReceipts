<?php

class CRM_Cdntaxreceipts_Receipt_Batch {

  const MAX_RECEIPT_COUNT = 1000;

  private $_batchBuilder = NULL;

  private $_built = FALSE;
  private $_batch = NULL;
  private $_errors = NULL;
  private $_summary = NULL;

  private $_emailCount = 0;
  private $_printCount = 0;
  private $_failCount = 0;

  private $_collectedPDF;

  public function __construct(CRM_Cdntaxreceipts_Receipt_BatchBuilder $batchBuilder) {
    $this->_collectedPDF = CRM_Cdntaxreceipts_PDF_Factory::getPDFLib();
    $this->_batchBuilder = $batchBuilder;
  }

  /**
   * @param array $issueParams - pass any parameters you want to the batch builder
   * @param bool $previewMode
   * @param bool $originalOnly
   */
  public function issue($issueParams = array(), $previewMode = FALSE, $originalOnly = FALSE) {

    if ($this->_built) {
      $this->_batch = $this->_batchBuilder->updateBatch($issueParams, $previewMode, $originalOnly);
      foreach ($this->_batch as $receiptToPrint) {
        $result = $this->issueOne($receiptToPrint, $this->_collectedPDF, $previewMode);
        if ($result == FALSE) {
          $this->_failCount++;
        }
        //TODO: Add to errors here
        if ( $this->_emailCount + $this->_printCount + $this->_failCount >= self::MAX_RECEIPT_COUNT ) {
          $errors['info'][] = ts('Maximum of %1 tax receipt(s) were sent. Please repeat to continue processing.',
            array(1=>self::MAX_RECEIPT_COUNT, 'domain' => 'org.civicrm.cdntaxreceipts'));
        }
      }
    }
  }

  public function build() {
    if (!$this->_built) {
      $this->_batch = $this->_batchBuilder->buildBatch();
      $this->_errors = $this->_batchBuilder->getErrors();
      $this->_built = TRUE;
    }
    return $this->_batch;
  }

  public function getPreviewSummary() {
    if (!$this->_built) {
      $this->build();
    }
    $this->_summary = $this->_batchBuilder->getBatchSummary();
    return $this->_summary;
  }

  private function issueOne(CRM_Cdntaxreceipts_Receipt $receiptToPrint,
                            CRM_Cdntaxreceipts_PDF_Generator $collectedPDF, $previewMode = FALSE) {
    $singlePDF = CRM_Cdntaxreceipts_PDF_Factory::getPDFLib();
    $result = $receiptToPrint->issue($singlePDF, $collectedPDF, $previewMode);
    $issueMethod = $receiptToPrint->getIssueMethod();
    if ($issueMethod == 'email') {
      $this->_emailCount++;
    }
    else {
      $this->_printCount++;
    }

    return $result;
  }

  /**
   * @return errors - array of building errors
   */
  public function getErrors() {
    return $this->_errors;
  }

  public function getCounts() {
    return array(
      'email' => $this->_emailCount,
      'print' => $this->_printCount,
      'fail' => $this->_failCount,
    );
  }

  public function getCollectedPDF() {
    return $this->_collectedPDF;
  }
}
