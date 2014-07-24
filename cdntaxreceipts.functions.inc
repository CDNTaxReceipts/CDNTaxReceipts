<?php

const CDNTAX_SETTINGS = 'CDNTaxReceipts';


/**************************************
 * SECTION: Utility Functions
 */

/**
 * set up the custom field structure for In-Kind tax receipts
 */

function cdntaxreceipts_configure_inkind_fields() {

  // check if the In-kind contribtion type exists.  if not, create it.
  $dao = _cdntaxreceipts_get_contribution_dao();
  $dao->name = 'In-kind';

  if ( ! $dao->find(true) ) {
    $dao->name = 'In-kind';
    $dao->is_deductible = TRUE;
    $dao->is_active = TRUE;
    $dao->save();
  }

  $contrib_type_id = $dao->id;

  // check if the custom group exists.  if not, create it.
  $params = array(
    'title' => 'In-kind donation fields',
    'version' => 3,
  );

  require_once 'api/api.php';
  $result = civicrm_api( 'custom_group', 'get', $params );

  if ( $result['count'] == 0 ) {
    $group = array(
      'title' => 'In-kind donation fields',
      'extends' => array( 'Contribution' ),
      'extends_entity_column_value' => array( $contrib_type_id ),
      'collapse_display' => 0,
      'style' => 'Inline',
      'is_active' => 1,
      'version' => 3
    );
    $result =& civicrm_api('custom_group', 'create', $group);
  }
  foreach ( $result['values'] as $id => $detail ) {
    $custom_group_id = $id;
  }

  // check if the custom fields exist.  if not, create them.
  foreach ( array( 'Description of property', 'Appraised by', 'Address of Appraiser', 'Original cost' ) as $field ) {
    $params = array(
      'custom_group_id' => $custom_group_id,
      'label' => $field,
      'version' => 3,
    );
    $result = civicrm_api( 'custom_field', 'get', $params);

    if ( $result['count'] == 0 ) {
      $field_params = array(
        'custom_group_id' => $custom_group_id,
        'label' => $field,
        'data_type' => 'String',
        'is_required' => 1,
        'is_active' => 1,
        'version' => 3,
      );
      if ( $field == 'Original cost' ) {
        $field_params['is_required'] = 0;
        $field_params['help_post'] = 'Amount originally paid for the item. This item is needed for a special rule'.
          ' that applies if either something was purchased in the last ten years'.
          ' with the intent of making a donation, or if an item is donated within three years'.
          ' of being purchased. In both cases the amount that can be claimed is the lesser'.
          ' of the market value, and the amount paid for the item. This item is used to show'.
          ' the cost of the item donated (and the creditable amount) if either of those '.
          ' situations apply.';
      }
      $result =& civicrm_api('custom_field', 'create', $field_params);
    }
  }
}

/**************************************
 * SECTION: Tax Receipt API
 */


function cdntaxreceipts_eligibleForReceipt( $contributionId ) {
  require_once('CRM/Contribute/DAO/Contribution.php');
  $contribution =  new CRM_Contribute_DAO_Contribution();
  $contribution->id = $contributionId;

  if ( ! $contribution->find( TRUE ) ) {
    CRM_Core_Error::fatal( "CDNTaxReceipts: Could not retrieve details for this contribution" );
  }

  // 1. check that contribution amount is deductible
  $deductibleAmount = cdntaxreceipts_eligibleAmount($contributionId);;

  // 2. check if the contribution is of a Type that is indeed Tax receiptable.
  $contributionType =  _cdntaxreceipts_get_type_for_contribution($contribution);

  // 3. check if the Contribution Status is Completed ( i.e. = 1 )
  $contributionStatus = $contribution->contribution_status_id;
  if ( !isset($contributionStatus) ) {
    CRM_Core_Error::fatal( "CDNTaxReceipts: Could not find status of this Contribution." );
  }

  // invoke hook_cdntaxreceipts_eligible:
  //   module implementations should return an array containing a single item,
  //   either TRUE or FALSE. assumes TRUE until we receive a FALSE. one FALSE
  //   disqualifies the contribution for a tax receipt.
  $results = CRM_Utils_Hook::singleton()->invoke(1, $contribution, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, 'cdntaxreceipts_eligible');
  $hookOk = TRUE;

  if (is_array($results)) {
    foreach ( $results as $result ) {
      if ( $result == FALSE ) {
        $hookOk = FALSE;
      }
    }
  }

  if ( $contributionType->is_deductible && $contributionStatus == '1' && $contribution->is_test == 0 && $deductibleAmount > 0 && $hookOk ) {
    return TRUE;
  }
  else {
    return FALSE;
  }

}

function cdntaxreceipts_eligibleAmount( $contributionId ) {
  require_once('CRM/Contribute/DAO/Contribution.php');
  $contribution =  new CRM_Contribute_DAO_Contribution();
  $contribution->id = $contributionId;

  if ( ! $contribution->find( TRUE ) ) {
    CRM_Core_Error::fatal( "CDNTaxReceipts: Could not retrieve details for this contribution" );
  }

  // 1. calculate deductible amount
  $deductibleAmount = $contribution->total_amount - $contribution->non_deductible_amount;

  // 2. allow modules to alter the amount. lowest amount wins.
  $results = CRM_Utils_Hook::singleton()->invoke(1, $contribution, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, 'cdntaxreceipts_eligibleAmount');

  if (is_array($results)) {
    foreach ( $results as $result ) {
      if ( $result < $deductibleAmount ) {
        $deductibleAmount = $result;
      }
    }
  }

  return $deductibleAmount;
}

/**
 * issueAnnualTaxReceipt()
 * Issues an annual tax receipt for a given year.
 * Detects if the receipt is a duplicate and reacts appropriately.
 */
function cdntaxreceipts_issueAnnualTaxReceipt( $contactId, $year, &$collectedPdf = NULL, $previewMode = FALSE ) {

  // determine the send method
  list( $method, $email ) = cdntaxreceipts_sendMethodForContact($contactId);

  // query for duplicate receipt
  list( $issued_on, $receipt_id ) = cdntaxreceipts_annual_issued_on($contactId, $year);

  // When returning issued_on field:
  //   returns false for non existing $contribution ID
  //   returns 0 for existing but old data
  //   returns timestamp for new data
  $is_duplicate = empty($issued_on) ? 0 : 1;

  if ( $is_duplicate ) {
    // issue an identical receipt
    $receipt = cdntaxreceipts_load_receipt($receipt_id);
    $receipt['issued_on'] = (int) $_SERVER['REQUEST_TIME'];
    $receipt['is_duplicate'] = 1;
    $receipt['receive_date'] = $year;
    $receipt['issue_method'] = $method;
  }
  else {

    $contributions = cdntaxreceipts_contributions_not_receipted($contactId, $year);

    if ( count($contributions) == 0 ) {
      return array(FALSE, $method, NULL);
    }

    // calculate total amount
    $receiptContributions = array();
    $totalReceipt = 0;
    foreach ( $contributions as $c ) {
      $receiptContributions[] = array(
        'contribution_id' => $c['contribution_id'],
        'contribution_amount' => $c['total_amount'],
        'receipt_amount' => cdntaxreceipts_eligibleAmount($c['contribution_id']),
        'receive_date' => $c['receive_date'],
      );
      $totalReceipt += cdntaxreceipts_eligibleAmount($c['contribution_id']);
    }

    // generate a receipt number
    $receiptNo = CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'receipt_prefix') . str_pad($receiptContributions[0]['contribution_id'], 8, 0, STR_PAD_LEFT);

    $receipt = array(
      'receipt_no' => $receiptNo,
      'issued_on' =>  (int) $_SERVER['REQUEST_TIME'],
      'contact_id' => $contactId,
      'receipt_amount' => $totalReceipt,
      'is_duplicate' => 0,
      'issue_type' => 'annual',
      'issue_method' => $method,
      'receive_date' => $year,
      'contributions' => $receiptContributions,
    );

  }

  return cdntaxreceipts_processTaxReceipt($receipt, $collectedPdf, $previewMode);
}

/**
 * cdntaxreceipts_issueAggregateTaxReceipt()
 * Issues an grouped tax receipt for a given contact, year and list of selected contributions.
 * Currently does not issue duplicates. Pass only original contribution ids.
 *
 * @param $contactId
 * @param $year
 * @param $contributions
 * @param $method
 * @param null $collectedPdf
 * @param bool $previewMode
 * @return array
 */
function cdntaxreceipts_issueAggregateTaxReceipt($contactId, $year, $contributions, $method, &$collectedPdf = NULL,
                                                 $previewMode = FALSE ) {
  if ( count($contributions) == 0 ) {
    return array(FALSE, $method, NULL);
  }

  /* Two different formats come into this function in the $contributions array
   * 1. When issuing for the first time:
   * array ($contribution_id => array(
   *   'contribution_id' => $id,
   *   'contact_id' => $dao->contact_id,
   *   'total_amount' => $dao->total_amount,
   *   'non_deductible_amount' => $dao->non_deductible_amount,
   *   'receive_date' => $dao->receive_date,
   *   'receive_year' => $dao->receive_year,
   *   'eligible' => $eligible,
   *   'receipt_id' => $dao->receipt_id,
   * ),);
   * 2. When re-issuing:
   *
   *  $index (not contrib id) => array(
   *    'contribution_id'
   *    'contribution_amount'
   *    'receipt_amount'
   *    'receive_date'
   * );
   * */

  $contrib_copy = $contributions;
  $check_contribution = array_shift($contrib_copy);
  list($issued_on, $receipt_id) = cdntaxreceipts_issued_on($check_contribution['contribution_id']);

  if (isset($check_contribution['receipt_id']) && $check_contribution['receipt_id'] != 0) {
    $is_duplicate = TRUE;
  }
  else {
    $is_duplicate = empty($issued_on) ? 0 : 1;
  }

  if ( $is_duplicate ) {
    $receipt = cdntaxreceipts_load_receipt($receipt_id);
    $receipt['issued_on'] = (int) $_SERVER['REQUEST_TIME'];
    $receipt['is_duplicate'] = 1;
    $receipt['issue_method'] = $method;
    $receipt['receive_date'] = $year;
  }
  else {
    // calculate total amount
    $receiptContributions = array();
    $totalReceipt = 0;
    foreach ( $contributions as $c ) {
      $receiptContributions[] = array(
        'contribution_id' => $c['contribution_id'],
        'contribution_amount' => $c['total_amount'],
        'receipt_amount' => cdntaxreceipts_eligibleAmount($c['contribution_id']),
        'receive_date' => $c['receive_date'],
      );
      $totalReceipt += cdntaxreceipts_eligibleAmount($c['contribution_id']);
    }

    // generate a receipt number
    $receiptNo = CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'receipt_prefix') . str_pad($receiptContributions[0]['contribution_id'], 8, 0, STR_PAD_LEFT);

    $receipt = array(
      'receipt_no' => $receiptNo,
      'issued_on' =>  (int) $_SERVER['REQUEST_TIME'],
      'contact_id' => $contactId,
      'receipt_amount' => $totalReceipt,
      'is_duplicate' => 0,
      'issue_type' => 'aggregate',
      'issue_method' => $method,
      'receive_date' => $year,
      'contributions' => $receiptContributions,
    );
  }

  return cdntaxreceipts_processTaxReceipt($receipt, $collectedPdf, $previewMode);
}


/* determines the send method for the specified contact
 * returns array, [0] = 'email' or 'print', [1] = email address or NULL
 */
function cdntaxreceipts_sendMethodForContact( $contactId ) {

  $global_email = CRM_Core_BAO_Setting::getItem(CDNTAX_SETTINGS, 'enable_email', NULL, TRUE);
  if ( ! $global_email ) {
    return array('print', NULL);
  }

  require_once 'CRM/Contact/BAO/Contact.php';
  list($displayname, $email, $doNotEmail, $onHold) = CRM_Contact_BAO_Contact::getContactDetails($contactId);

  if ( isset($email) ) {
    if ( ! $doNotEmail && ! $onHold ) {
      return array('email', $email);
    }
  }

  return array('print', NULL);

}

function cdntaxreceipts_sendMethodForContribution( $contributionId ) {

  $contribution =  new CRM_Contribute_DAO_Contribution();
  $contribution->id = $contributionId;

  if ( ! $contribution->find( TRUE ) ) {
    CRM_Core_Error::fatal( "CDNTaxReceipts: Could not retrieve details for this contribution" );
  }

  return cdntaxreceipts_sendMethodForContact( $contribution->contact_id );
}

/**
 * Isolate version checking code
 * @param $contribution
 * @return CRM_Contribute_DAO_ContributionType|CRM_Financial_DAO_FinancialType
 */
function _cdntaxreceipts_get_type_for_contribution($contribution) {
  // Require once lines are only needed if the civicrm.settings.php does not have the autoloader line
  require_once 'CRM/Utils/VersionCheck.php';
  $version_checker = CRM_Utils_VersionCheck::singleton();
  $majorVersion = floatval(substr($version_checker->localVersion, 0, 3));
  if ($majorVersion >= 4.3) {
    require_once 'CRM/Financial/DAO/FinancialType.php';
    $contributionType = new CRM_Financial_DAO_FinancialType();
    $contributionType->id = $contribution->financial_type_id;
    if ( ! $contributionType->find( TRUE ) ) {
      CRM_Core_Error::fatal( "CDNTaxReceipts: Could not find corresponding contribution type." );
    }
  }
  else { // Assume 4.2 or lower
    require_once 'CRM/Contribute/DAO/ContributionType.php';
    $contributionType = new CRM_Contribute_DAO_ContributionType();
    $contributionType->id = $contribution->contribution_type_id;
    if ( ! $contributionType->find( TRUE ) ) {
      CRM_Core_Error::fatal( "CDNTaxReceipts: Could not find corresponding contribution type." );
    }
  }
  return $contributionType;
}

function _cdntaxreceipts_get_contribution_dao() {
  // Require once lines are only needed if the civicrm.settings.php does not have the autoloader line
  require_once 'CRM/Utils/VersionCheck.php';
  $version_checker = CRM_Utils_VersionCheck::singleton();
  $majorVersion = floatval(substr($version_checker->localVersion, 0, 3));
  if ($majorVersion >= 4.3) {
    require_once 'CRM/Financial/DAO/FinancialType.php';
    $contributionType = new CRM_Financial_DAO_FinancialType();
  }
  else { // Assume 4.2 or lower
    require_once 'CRM/Contribute/DAO/ContributionType.php';
    $contributionType = new CRM_Contribute_DAO_ContributionType();
  }
  return $contributionType;
}

/**
 * Get the correct contact address. Get the billing address followed by the is_primary as a fallback.
 * @param $contact_id
 * @return array|mixed|null address : Contact address fixed with named country and state_province
 */
function cdntaxreceipts_getAddress($contact_id) {

  $address = NULL;
  // get Address information via contact
  $params = array(
    'version' => 3,
    'contact_id' => $contact_id,
    'is_billing' => 1,
  );


  $address_results = civicrm_api('Address', 'get', $params);

  if ( $address_results['is_error'] == 0) {
    $address = array_shift($address_results['values']);
  }

  if (!isset($address)) {
    $params = array(
      'version' => 3,
      'contact_id' => $contact_id,
      'is_primary' => 1,
    );
    $address_results = civicrm_api('Address', 'get', $params);

    if ( $address_results['is_error'] == 0) {
      $address = array_shift($address_results['values']);
    }

  }
  $address = isset($address) ? $address : array();
  // add actual names for province and country instead of just having ids
  require_once 'CRM/Core/BAO/Address.php';
  CRM_Core_BAO_Address::fixAddress($address);

  return $address;
}

/**
 * @param $issue_type
 * @return string display formatted Issue type
 */
function _cdntaxreceipts_get_display_type($issue_type) {
  switch ($issue_type) {
    case 'aggregate' :
      return 'Aggregate';
    case 'annual' :
      return 'Annual';
    case 'single' :
      return 'Single';
    default:
      return '';
  }
}
