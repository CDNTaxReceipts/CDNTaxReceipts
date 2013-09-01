<?php

require_once 'cdntaxreceipts.civix.php';
require_once 'cdntaxreceipts.functions.inc';
require_once 'cdntaxreceipts.db.inc';

function cdntaxreceipts_civicrm_buildForm( $formName, &$form ) {

  if ( is_a( $form, 'CRM_Contribute_Form_ContributionView' ) ) {

    // add "Issue Tax Receipt" button to the "View Contribution" page
    // if the Tax Receipt has NOT yet been issued -> display a white maple leaf icon
    // if the Tax Receipt has already been issued -> display a red maple leaf icon
   
    CRM_Core_Resources::singleton()->addStyleFile('org.civicrm.cdntaxreceipts', 'css/civicrm_cdntaxreceipts.css');

    $contributionId = $form->get( 'id' );

    if ( isset($contributionId) && cdntaxreceipts_eligibleForReceipt($contributionId) ) {

      list($issued_on, $receipt_id) = cdntaxreceipts_issued_on($contributionId);
      $is_original_receipt = empty($issued_on);

      if ($is_original_receipt) {
        $buttons = array(array('type'      => 'submit',
                               'subName'   => 'issue_tax_receipt',
                               'name'      => t('Tax Receipt'),
                               'isDefault' => FALSE ), );
      }
      else {
        // this is essentially the same button - but it has a different
        // subName -> which is used (css) to display the red maple leaf instead.
        $buttons = array(array('type'      => 'submit',
                               'subName'   => 'view_tax_receipt',
                               'name'      => t('Tax Receipt'),
                               'isDefault' => FALSE ), );
      }
      $form->addButtons( $buttons );

    }
  }
}

/**
 * Implementation of hook_civicrm_postProcess().
 *
 * Called when a form comes back for processing. Basically, we want to process
 * the button we added in cdntaxreceipts_civicrm_buildForm().
 */

function cdntaxreceipts_civicrm_postProcess( $formName, &$form ) {

  // first check whether I really need to process this form
  if ( ! is_a( $form, 'CRM_Contribute_Form_ContributionView' ) ) {
    return;
  }
  $types = array('issue_tax_receipt','view_tax_receipt');
  $action = '';
  foreach($types as $type) {
    $post = '_qf_ContributionView_submit_'.$type;
    if (isset($_POST[$post])) {
      if ($_POST[$post] == t('Tax Receipt')) {
        $action = $post;
      }
    }
  }
  if (empty($action)) {
    return;
  }

  // the tax receipt button has been pressed.  redirect to the tax receipt 'view' screen, preserving context.
  $contributionId = $form->get( 'id' );
  $contactId = $form->get( 'cid' );

  $session = CRM_Core_Session::singleton();
  $session->pushUserContext(CRM_Utils_System::url('civicrm/contact/view/contribution',
    "reset=1&id=$contributionId&cid=$contactId&action=view&context=contribution&selectedChild=contribute"
  ));

  $urlParams = array('reset=1', 'id='.$contributionId, 'cid='.$contactId);
  CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/cdntaxreceipts/view', implode('&',$urlParams)));
}

/**
 * Implementation of hook_civicrm_searchTasks().
 *
 * For users with permission to issue tax receipts, give them the ability to do it
 * as a batch of search results.
 */

function cdntaxreceipts_civicrm_searchTasks($objectType, &$tasks ) {
  if ( $objectType == 'contribution' && CRM_Core_Permission::check( 'edit contributions' ) ) { // 'issue cdn tax receipts') ) {
    $alreadyinlist = FALSE;
    foreach ($tasks as $key => $task) {
      if($task['class'] == 'CRM_Cdntaxreceipts_Task_IssueSingleTaxReceipts') {
        $alreadyinlist = TRUE;
      }
    }
    if (!$alreadyinlist) {
      $tasks[] = array (
        'title' => ts('Issue Tax Receipts'),
        'class' => 'CRM_Cdntaxreceipts_Task_IssueSingleTaxReceipts',
        'result' => TRUE);  
    }
  }
  elseif ( $objectType == 'contact' && CRM_Core_Permission::check( 'edit contributions' ) ) { //'issue cdn tax receipts') ) {
    $alreadyinlist = FALSE;
    foreach ($tasks as $key => $task) {
      if($task['class'] == 'CRM_Cdntaxreceipts_Task_IssueAnnualTaxReceipts') {
        $alreadyinlist = TRUE;
      }
    }
    if (!$alreadyinlist) {
      $tasks[] = array (
        'title' => ts('Issue Annual Tax Receipts'),
        'class' => 'CRM_Cdntaxreceipts_Task_IssueAnnualTaxReceipts',
        'result' => TRUE);
    }
  }
}

/**
 * JAKE -- not working
 * Implementation of hook_civicrm_permissions().
 *
function cdntaxreceipts_civicrm_permissions( &$permissions ) {
  if ( ! $is_array( $permissions ) ) {
    $permissions = array();
  }
  $prefix = ts('CiviCRM CDN Tax Receipts') . ': ';
  $permissions['issue cdn tax receipts'] = $prefix . ts('Issue Tax Receipts');
}
*/

/**
 * Implementation of hook_civicrm_config
 */
function cdntaxreceipts_civicrm_config(&$config) {
  _cdntaxreceipts_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function cdntaxreceipts_civicrm_xmlMenu(&$files) {
  _cdntaxreceipts_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function cdntaxreceipts_civicrm_install() {
  return _cdntaxreceipts_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function cdntaxreceipts_civicrm_uninstall() {
  return _cdntaxreceipts_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function cdntaxreceipts_civicrm_enable() {

  // add a menu item to the Administer > CiviContribute menu
  require_once 'CRM/Core/BAO/Navigation.php';

  // check there is no admin item
  $cdntax_search = array('url' => 'civicrm/cdntaxreceipts/settings?reset=1');
  $cdntax_item = array();
  CRM_Core_BAO_Navigation::retrieve($cdntax_search, $cdntax_item);

  if ( ! empty($cdntax_item) ) {
    return;
  }

  // get path to Administer > CiviContribute and place admin item there
  $administer_search = array('label' => 'Administer');
  $administer_item = array();
  CRM_Core_BAO_Navigation::retrieve($administer_search, $administer_item);

  if ($administer_item) {
    $contribute_search = array('label' => 'CiviContribute', 'parent_id' => $administer_item['id']);
    $contribute_item = array();
    CRM_Core_BAO_Navigation::retrieve($contribute_search, $contribute_item);

    if ($contribute_item) {
      $new_item = array(
        'name' => 'CDN Tax Receipts',
        'label' => 'CDN Tax Receipts',
        'url' => 'civicrm/cdntaxreceipts/settings?reset=1',
        'permission' => 'administer CiviCRM',
        'parent_id' => $contribute_item['id'],
        'is_active' => TRUE,
      );
      CRM_Core_BAO_Navigation::add($new_item);
    }
  }

  return _cdntaxreceipts_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function cdntaxreceipts_civicrm_disable() {
  return _cdntaxreceipts_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function cdntaxreceipts_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _cdntaxreceipts_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function cdntaxreceipts_civicrm_managed(&$entities) {
  return _cdntaxreceipts_civix_civicrm_managed($entities);
}
