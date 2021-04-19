<?php
namespace Cdntaxreceipts\Tests\Mink;

use Drupal\Tests\civicrm\FunctionalJavascript\CiviCrmTestBase;

class CdntaxreceiptsBase extends CiviCrmTestBase {

  use \Drupal\Tests\mink_civicrm_helpers\Traits\Utils;

  /**
   * @var array
   */
  protected static $modules = [
    'mink_civicrm_helpers',
  ];

  /**
   * @var int
   *   The uf_id of the logged in user.
   */
  protected $_loggedInUser = NULL;

  public function setUp(): void {
    parent::setUp();

    $this->setUpExtension('org.civicrm.cdntaxreceipts');

    $this->configureTaxReceiptSettings();
  }

  /**
   * Pretty much every test is going to start with this.
   */
  public function createUserAndLogIn(): void {
    $account = $this->createUser([
      'administer CiviCRM',
      'access CiviCRM',
      'administer CiviCRM system',
      'administer CiviCRM data',
      'access all custom data',
      'edit all contacts',
      'delete contacts',
      'access CiviContribute',
      'edit contributions',
      'delete in CiviContribute',
      'issue cdn tax receipts',
    ]);
    $this->drupalLogin($account);
    $this->_loggedInUser = $account->uid;
  }

  /**
   * Create a contact. Helper function since the usual individualCreate()
   * is not available.
   * @param int $index There are a couple stock contacts. You can pick one as the base params to use.
   * @param array $params Some params to merge into the base.
   * @return array
   */
  public function createContact(int $index = 0, array $params = []): array {
    $stockContacts = [
      'first_name' => ['Anthony', 'Joe', 'Terrence', 'Lucie', 'Albert', 'Bill', 'Kim'],
      'last_name' => ['Anderson', 'Miller', 'Smith', 'Collins', 'Peterson', 'Johnson', 'Li'],
    ];
    $vars = array_merge([
      'contact_type' => 'Individual',
      'first_name' => $stockContacts['first_name'][$index],
      'last_name' => $stockContacts['last_name'][$index],
      'email' => strtolower("{$stockContacts['first_name'][$index]}.{$stockContacts['last_name'][$index]}@example.org"),
      'phone' => preg_replace('/[^0-9]/', '', bin2hex("{$stockContacts['first_name'][$index]}{$stockContacts['last_name'][$index]}")),
    ], $params);

    // Phone doesn't work the same as email for create.
    // If the input params didn't blank it out, convert to right format.
    if (!empty($vars['phone'])) {
      $vars['api.Phone.create'] = ['phone' => $vars['phone']];
      unset($vars['phone']);
    }

    return civicrm_api3('Contact', 'create', $vars);
  }

  /**
   * Configure the extension, i.e. fill out the settings page.
   */
  private function configureTaxReceiptSettings(): void {
    \Civi::settings()->add([
      'org_name' => 'CDN Tax Org',
      'org_address_line1' => '123 Main St.',
      'org_address_line2' => 'Ababa, AA  A1A 1A1',
      'org_tel' => '123-456-7890',
      'org_fax' => '',
      'org_email' => 'cdntaxorg@example.org',
      'org_web' => 'https://cdntaxorg.example.org',
      'org_charitable_no' => '12345-678-RR0001',
      'receipt_prefix' => 'C-',
      'receipt_serial' => 0,
      'receipt_authorized_signature_text' => 'Receet Sighnor',
      'issue_inkind' => 0,
      'delivery_method' => CDNTAX_DELIVERY_DATA_ONLY,
      'attach_to_workflows' => 0,
      'enable_advanced_eligibility_report' => 0,
      'email_from' => 'cdntaxorg@example.org',
      'email_archive' => 'cdntaxorg@example.org',
    ]);
  }

}
