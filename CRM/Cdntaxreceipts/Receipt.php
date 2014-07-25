<?php


class CRM_Cdntaxreceipts_Receipt {
  //TODO: Rationalize public private
  //----- Vars loaded from DB
  private $_id;
  public $_receipt_no;
  public $_issued_on;
  public $_contactId;
  public $_receipt_amount = 0;
  public $_is_duplicate;
  public $_uid;
  public $_uname;
  public $_ip;
  public $_issue_type;
  public $_display_type;
  private $_issue_method;

  /**
   * @var $_contributions array - Loaded from db table
   */
  public $_contributions;

  // Vars calculated at runtime
  public $_destinationEmail;
  public $_receive_date;

  private $fullContributionObjects;

  /**
   * @var $_loaded bool - Is this a receipt we loaded from the DB
   */
  private $_loaded = FALSE;

  public function __construct() {

  }

  public function toArray() {
    $array_version = array();
    $keys = array(
      '_id',
      '_receipt_no',
      '_issued_on',
      '_contactId',
      '_receipt_amount',
      '_is_duplicate',
      '_uid',
      '_uname',
      '_ip',
      '_display_type',
      '_issue_method',
      '_contributions',
    );

    foreach ($keys as $key) {
      $array_version[substr($key, 1)] = $this->$key;
    }

    return $array_version;
  }

  /**
   * loadFromDB - Loads from DB using MySQL unique receipt id
   * TODO: Should this really be static? Then how to access the private loaded function
   * Was cdntaxreceipts_load_receipt
   * @param $receipt_id
   * @return $this|null
   */
  public function loadFromDB($receipt_id) {

    if (!isset($receipt_id)) {
      return NULL;
    }

    $sql = "SELECT l.id, l.receipt_no, l.issued_on, l.contact_id, l.receipt_amount as total_receipt,
        l.is_duplicate, l.uid, l.ip, l.issue_type, l.issue_method,
        c.contribution_id, c.contribution_amount, c.receipt_amount, c.receive_date
    FROM cdntaxreceipts_log l
    LEFT JOIN cdntaxreceipts_log_contributions c ON l.id = c.receipt_id
    WHERE l.id = %1";

    $dao = CRM_Core_DAO::executeQuery($sql, array(1 => array($receipt_id, 'Integer')));

    $contributions = array();

    if ( $dao->fetch() ) {
      $issued_by = CRM_Core_BAO_UFMatch::getUFValues($dao->uid);
      $this->_id = $dao->id;
      $this->_receipt_no = $dao->receipt_no;
      $this->_issued_on = date('Y-m-d H:i:s', $dao->issued_on);
      $this->_receipt_amount = $dao->total_receipt;
      $this->_is_duplicate = $dao->is_duplicate;
      $this->_uid = $dao->uid;
      $this->_uname = $issued_by['uf_name'] . ' (' . $dao->uid . ')';
      $this->_ip = $dao->ip;
      $this->_issue_type = $dao->issue_type;
      $this->_display_type = _cdntaxreceipts_get_display_type($dao->issue_type);
      $this->_issue_method = $dao->issue_method;
      $this->setContactId($dao->contact_id);

      do {
        $contributions[] = array(
          'contribution_id' => $dao->contribution_id,
          'contribution_amount' => $dao->contribution_amount,
          'receipt_amount' => $dao->receipt_amount,
          'receive_date' => $dao->receive_date,
        );
      } while ( $dao->fetch() );

      $this->_contributions = $contributions;
    }
    $this->_loaded = TRUE;

    return $this;
  }

  /**
   * createFromContribution - Create a tax receipt from a single contribution
   *
   * @param $contribution
   * @return CRM_Cdntaxreceipts_Receipt|null
   */
  public static function createFromContribution($contribution) {
    $my_contribution = NULL;
    if (is_object($contribution)) {
      $my_contribution = $contribution;
    }
    else {
      $my_contribution =  new CRM_Contribute_DAO_Contribution();
      $my_contribution->id = $contribution;

      if (!$my_contribution->find(TRUE)) {
        return NULL;
      }
    }
    $receipt = new CRM_Cdntaxreceipts_Receipt();
    $receipt->setIssueType('single');
    $receipt->setContactId($my_contribution->contact_id);
    $receipt->addContribution($my_contribution);
    return $receipt;
  }

  /**
   * createFromContributionList - Create a tax receipt from a single contribution
   *
   * @param $contribution
   * @return CRM_Cdntaxreceipts_Receipt|null
   */
  public static function createFromContributionList($issueType, $contactId, $contributions = array()) {

    $receipt = new CRM_Cdntaxreceipts_Receipt();
    $receipt->setIssueType($issueType);
    $receipt->setContactId($contactId);
    foreach ($contributions as $contribution) {
      // TODO: Make sure we have objects
      $receipt->addContribution($contribution);
    }
    return $receipt;
  }

  private function save($uid = NULL) {
    // TODO: use a transaction
    if (!isset($uid)) {
      $uid = CRM_Utils_System::getLoggedInUfID();
    }

    // create the main entry
    $params = array(
      1 => array( $this->_receipt_no, 'String' ),
      2 => array( $this->_issued_on, 'Integer' ),
      3 => array( $this->_contactId, 'Integer' ),
      4 => array( $this->_receipt_amount, 'Money' ),
      5 => array( $this->_is_duplicate, 'Boolean' ),
      6 => array( $uid, 'Integer' ),
      7 => array( $_SERVER['REMOTE_ADDR'], 'String' ),
      8 => array( $this->_issue_type, 'String' ),
      9 => array( $this->_issue_method, 'String' ),
    );
    $sql = "INSERT INTO cdntaxreceipts_log (receipt_no, issued_on, contact_id, receipt_amount,
    is_duplicate, uid, ip, issue_type, issue_method)
      VALUES (%1, %2, %3, %4, %5, %6, %7, %8, %9)";
    $result = CRM_Core_DAO::executeQuery($sql, $params);
    $receipt_id = CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');

    // add line items
    foreach ( $this->_contributions as $contribution ) {
      $params = array(
        1 => array( $receipt_id, 'Integer' ),
        2 => array( $contribution['contribution_id'], 'Integer' ),
        3 => array( $contribution['contribution_amount'], 'Money' ),
        4 => array( $contribution['receipt_amount'], 'Money' ),
        5 => array( $contribution['receive_date'], 'String' ),
      );
      //TODO: Combine into one SQL call with multiple values lists
      //TODO: Record inkind values
      $sql = "INSERT INTO cdntaxreceipts_log_contributions (receipt_id, contribution_id,
      contribution_amount, receipt_amount, receive_date)
        VALUES (%1, %2, %3, %4, %5)";
      CRM_Core_DAO::executeQuery($sql, $params);
    }
  }

  function issue(CRM_Cdntaxreceipts_PDF_Generator $attachmentPDF,
                 CRM_Cdntaxreceipts_PDF_Generator $collectedPdf = NULL,
                 $previewMode = FALSE) {
    // Generate a receipt no
    $receipt_no = $this->generateReceiptNo();
    if (!$receipt_no) {
      return FALSE;
    }
    $validated = $this->validateIssueSetDuplicate();
    if (!$validated) {
      return FALSE;
    }
    // TODO: Where should we do this. Record server vars
    $this->_issued_on = (int) $_SERVER['REQUEST_TIME'];

    // Set attachment file name
    $pdf_file  = $this->getFileName();
    // Get contact details
    // TODO: I think we can skip this we should already have it
    list($displayName, $email) = CRM_Contact_BAO_Contact::getContactDetails($this->_contactId);

    // generate the PDF file
    $attachmentPDF = $this->printReceipt($attachmentPDF);
    $attachmentPDF->closeAndSave($pdf_file);

    // Add to the collected PDF
    if ($this->getIssueMethod()== 'print' || $previewMode) {
      $this->printReceipt($collectedPdf);
    }

    // form a mailParams array to pass to the CiviCRM mail utility
    $attachment = array(
      'fullPath' => $pdf_file,
      'mime_type' => 'application/pdf',
      'cleanName' => $this->getFileName(TRUE),
    );

    $email_message = CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'email_message');
    $org_name = CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'org_name');

    $email_subject_admin = CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'email_subject');
    $email_subject = $email_subject_admin . " " . $receipt_no;

    $email_from = CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'email_from');
    $email_archive = CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'email_archive');

    $mailParams = array(
      // TODO: Get from name from settings (domain emails) or set default to that
      'from' => $org_name . ' <' . $email_from . '> ',
      'toName' => $displayName,
      'attachments' => array($attachment),
      'subject' => $email_subject,
      'text' => $email_message,
      'returnPath' => $email_from,
    );

    // if method is email, we send to contact and BCC email archive.
    // if method is print, we send to email archive only.
    if ( $this->_issue_method == 'email' ) {
      $mailParams['toEmail'] = $email;
      $mailParams['bcc'] = $email_archive;
    }
    else {
      $mailParams['toEmail'] = $email_archive;
    }

    if ( $previewMode ) {
      $ret = TRUE;
    }
    else {
      $ret = CRM_Utils_Mail::send($mailParams);
      if ($ret == TRUE) {
        // we have successfully processed.  Log the receipt.
        $this->save();
      }
    }

    if ($this->_issue_method == 'email') {
      unlink($pdf_file); // file is no longer needed
      return $ret;
    }
    else {
      if (isset($collectedPdf)) {
        unlink($pdf_file); // file is no longer needed
        $pdf_file = NULL;
      }
      // if not unset, cron will clean up the PDF file later on
      return $ret;
    }
  }

  /**
   * process - Calculate some temporary display variables before printing
   * and generate an array for original PDF generator,
   * @return array
   */
  function process() {
    $address = cdntaxreceipts_getAddress($this->_contactId, $previewMode = FALSE);

    $address_line_1 = isset($address['street_address']) ? $address['street_address'] : '';
    $parts = array();
    foreach(array('city', 'state_province', 'postal_code')  as $addr_part) {
      if (isset($address[$addr_part])) {
        $parts[] = $address[$addr_part];
      }
    }
    $address_line_1b = isset($address['supplemental_address_1']) ? $address['supplemental_address_1'] : '';
    $address_line_2 = implode(' ', $parts);
    $address_line_3 = isset($address['country']) ? $address['country'] : '';

    list($displayName, $email) = CRM_Contact_BAO_Contact::getContactDetails($this->_contactId);
    if ( $this->_issue_type == 'single' ) {
      $pos = strpos($this->_receive_date, '-');
      if ($pos === FALSE) {
        $date = substr($this->_receive_date, 0, 8);
        $display_date = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
      }
      else {
        $display_date = substr($this->_receive_date, 0, 10);
      }
      $display_year = '';
    }
    else {
      $display_year = $this->_receive_date;
      $display_date = date('M j, Y', mktime(0 ,0, 0, 1, 1, $this->_receive_date)) . ' - ' .
        date('M j, Y', mktime(0, 0, 0, 12, 31, $this->_receive_date));
    }

    $line_1 = ts("This is your Official Receipt for income tax purposes.", array('domain' => 'org.civicrm.cdntaxreceipts'));

    $this->_issued_on = (int) $_SERVER['REQUEST_TIME'];
    $this->_ip = $_SERVER['REMOTE_ADDR'];

    $pdf_variables = array(
      "preview_mode" => $previewMode,
      "is_duplicate" => $this->_is_duplicate,
      "line_1" => $line_1,
      "source_funds" => isset($receipt['source']) ? $receipt['source'] : '',
      "amount" => $this->_receipt_amount,
      "display_date" => $display_date,
      "display_year" => $display_year,
      "issued_on" => date('Y-m-d', $this->_issued_on),
      "ip" => $this->_ip,
      "issue_type" => $this->_issue_type,
      "receipt_number" => $this->_receipt_no,
      "displayname" => $displayName,
      "address_line_1" => $address_line_1,
      "address_line_1b" => $address_line_1b,
      "address_line_2" => $address_line_2,
      "address_line_3" => $address_line_3,
      "inkind_values" => isset($this->_inkind_values) ? $this->_inkind_values : array(),
      "receipt_contributions" => $this->_contributions,
    );

    return $pdf_variables;
  }

  function printReceipt(CRM_Cdntaxreceipts_PDF_Generator $pdf) {
    $pdf_variables = $this->process();
    $pdf_variables['mymargin_left'] = $pdf->getDefaultLeftMargin();
    $pdf_variables['mymargin_top'] = $pdf->getDefaultTopMargin();
    $pdf->addPage($pdf_variables);
    return $pdf;
  }

  /**
   * getIssueHistory
   *
   * Return all records of when the contribution was receipted
   * This checks 'single', 'annual' and 'aggregate' receipts.
   *
   * @param $contribution_id - the contribution being looked up
   */
  static function getIssueHistory($contribution_id) {
    $issued_on = array('receipts' => array(), 'original' => NULL);

    $sql = "
    SELECT c.receipt_id as receipt_id
    FROM cdntaxreceipts_log_contributions c
    WHERE contribution_id = %1
    GROUP BY c.receipt_id";

    $dao = CRM_Core_DAO::executeQuery($sql, array(1 => array($contribution_id, 'Integer')));
    $count = $originalCount = 0;
    while ($dao->fetch()) {
      $rec = new CRM_Cdntaxreceipts_Receipt;
      $rec->loadFromDB($dao->receipt_id);
      $issued_on['receipts'][$count] = $rec;
      $count++;
      if (!$rec->getIsDuplicate()) {
        $issued_on['original'] = $rec;
        $originalCount++;
      }
    }
    $issued_on['count'] = $count;
    $issued_on['error'] = ($originalCount != 1);

    return $issued_on;
  }

  /**
   * @return mixed
   */
  public function getReceiptAmount() {
    return $this->_receipt_amount;
  }

  /**
   * @param mixed $receipt_amount
   */
  public function setReceiptAmount($receipt_amount) {
    $this->_receipt_amount = $receipt_amount;
  }

  /**
   * @return mixed
   */
  public function getIsDuplicate() {
    return $this->_is_duplicate;
  }

  /**
   * @param mixed $is_duplicate
   */
  public function setIsDuplicate($is_duplicate) {
    $this->_is_duplicate = $is_duplicate;
  }

  /**
   * @return mixed
   */
  public function getContactId() {
    return $this->_contactId;
  }

  /**
   * @param mixed $contact_id
   */
  public function setContactId($contactId) {
    if (!$this->_loaded) {
      $this->_contactId = $contactId;
      $this->updateIssueMethod();
    }
  }

  public function updateIssueMethod() {
    // default
    $this->_issue_method = 'print';

    // TODO: Use new api
    $global_email = CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'enable_email', NULL, TRUE);
    if ($global_email) {
      list($displayname, $email, $doNotEmail, $onHold) = CRM_Contact_BAO_Contact::getContactDetails($this->_contactId);

      if ( isset($email) ) {
        if ( ! $doNotEmail && ! $onHold ) {
          $this->_issue_method = 'email';
          $this->_destinationEmail = $email;
        }
      }
    }
  }

  /**
   * @return mixed
   */
  public function getReceiptNo() {
    return $this->_receipt_no;
  }


  public function generateReceiptNo() {
    // TODO: What do _contributions hold vs. full contributions
    if (!empty($this->_contributions)) {
      $this->_receipt_no =  CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'receipt_prefix')
        . str_pad($this->_contributions[0]['contribution_id'], 8, 0, STR_PAD_LEFT);
      return $this->_receipt_no;
    }
    else {
      return FALSE;
    }
  }

  public function getFileName($userFriendly = FALSE) {
    if ($this->_receipt_no) {
      $config = CRM_Core_Config::singleton();
      $friendly = 'Receipt-' . $this->_receipt_no . '.pdf';
      if ($userFriendly) {
        return $friendly;
      }
      else {
        return $config->customFileUploadDir . 'Receipt-' . $friendly;
      }
    }
  }

  /**
   * @return mixed
   */
  public function getIp() {
    return $this->_ip;
  }

  /**
   * @return mixed
   */
  public function getIssueMethod() {
    return $this->_issue_method;
  }

  /**
   * @param mixed $issue_type
   */
  public function setIssueMethod($issue_method) {
    $this->_issue_method = $issue_method;
  }

  /**
   * @return mixed
   */
  public function getIssueType() {
    return $this->_issue_type;
  }

  /**
   * @param mixed $issue_type
   */
  public function setIssueType($issue_type) {
    $this->_issue_type = $issue_type;
  }

  /**
   * @return mixed
   */
  public function getDestinationEmail() {
    return $this->_destinationEmail;
  }

  /**
   * @param mixed $destinationEmail
   */
  public function setDestinationEmail($destinationEmail) {
      $this->_destinationEmail = $destinationEmail;
  }

  public function addContribution($contribution) {
    $result = $contribution;
    $fullContributionObjects = $this->fullContributionObjects;
    if (!$this->_contactId && empty($fullContributionObjects)) {
      $this->setContactId($contribution->contact_id);
    }
    if($this->_contactId && ($this->_contactId == $contribution->contact_id)) {
      $this->fullContributionObjects[$contribution->id] = $contribution;
      $inkind_values = array();
      $contributiontype =  _cdntaxreceipts_get_type_for_contribution($contribution);

      $eligibleAmount = cdntaxreceipts_eligibleAmount($contribution->id);
      $this->_receipt_amount += $eligibleAmount;

      $this->_contributions[] = array(
          'contribution_id' => $contribution->id,
          'contribution_amount' => $contribution->total_amount,
          'receipt_amount' => $eligibleAmount,
          'receive_date' => $contribution->receive_date,
        );
      //TODO: Handle other kinds
      if ($this->_issue_type == 'single') {
        $this->_receive_date = $contribution->receive_date;
        $this->_source =  $contribution->source;
        // check if this is an 'In-kind" contribution.
        if ($contributiontype->name == 'In-kind') {
          $inkind_values = $this->getInKind($contribution->id);
        }
        $this->_inkind_values = $inkind_values;
      }
      return $result;
    }
    // Contribution from wrong contact id
    else {
      return FALSE;
    }
  }

  function getInKind($contributionId) {
    // in this case get the custom field values:
    $groupTitle = 'In-kind donation fields';
    $fieldLabel_description = 'Description of property';
    $customFieldID_description = CRM_Core_BAO_CustomField::getCustomFieldID( $fieldLabel_description, $groupTitle );
    $fieldLabel_appraisedby = 'Appraised by';
    $customFieldID_appraisedby = CRM_Core_BAO_CustomField::getCustomFieldID( $fieldLabel_appraisedby, $groupTitle );
    $fieldLabel_appraiseraddress = 'Address of Appraiser';
    $customFieldID_appraiseraddress = CRM_Core_BAO_CustomField::getCustomFieldID( $fieldLabel_appraiseraddress, $groupTitle );
    $fieldLabel_cost = 'Original cost';
    $customFieldID_cost = CRM_Core_BAO_CustomField::getCustomFieldID( $fieldLabel_cost, $groupTitle );

    $custom_id = 'custom_' . $customFieldID_description;
    $params = array('entityID' => $contributionId, $custom_id => 1);
    $values = CRM_Core_BAO_CustomValueTable::getValues($params);
    $inkind_values[] = $values[$custom_id];

    $custom_id = 'custom_' . $customFieldID_appraisedby;
    $params = array('entityID' => $contributionId, $custom_id => 1);
    $values = CRM_Core_BAO_CustomValueTable::getValues($params);
    $inkind_values[] = $values[$custom_id];

    $custom_id = 'custom_' . $customFieldID_appraiseraddress;
    $params = array('entityID' => $contributionId, $custom_id => 1);
    $values = CRM_Core_BAO_CustomValueTable::getValues($params);
    $inkind_values[] = $values[$custom_id];

    $custom_id = 'custom_' . $customFieldID_cost;
    $params = array('entityID' => $contributionId, $custom_id => 1);
    $values = CRM_Core_BAO_CustomValueTable::getValues($params);
    $inkind_values[] = $values[$custom_id];

    return $inkind_values;
  }

  /**
   * @return mixed
   */
  public function getId() {
    return $this->_id;
  }


  /**
   * Performs the automatic duplicate detection
   * @return bool
   */
  private function validateIssueSetDuplicate() {
    // TODO: This function should probably throw exceptions with error messages
    if (!$this->_contactId || !$this->_issue_method || empty($this->_contributions)) {
      return FALSE;
    }
    if ($this->_issue_method == 'single' && count($this->_contributions) != 1) {
      return FALSE;
    }
    // This was loaded from DB. It must be valid unless something has gone very wrong.
    // It can also only be issued as a duplicate
    if ($this->_loaded) {
      $this->_is_duplicate = 1;
      return TRUE;
    }
    // This is not loaded from DB
    // For now we will assume that it must be a new original issue so no contribution should be on any other receipt
    $histories = array();
    foreach ($this->_contributions as $contribution) {
      $histories[$contribution['contribution_id']] = CRM_Cdntaxreceipts_Receipt::getIssueHistory($contribution['contribution_id']);
      if (!empty($histories[$contribution['contribution_id']]['original'])) {
        return FALSE;
      }
    }

    //TODO: Should we store these here?
    $this->_histories = $histories;
    $this->_is_duplicate = 0;
    return TRUE;
  }

}
