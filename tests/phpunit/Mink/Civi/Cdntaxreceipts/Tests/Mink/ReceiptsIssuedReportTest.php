<?php
namespace Civi\Cdntaxreceipts\Tests\Mink;

/**
 * @group mink
 */
class ReceiptsIssuedReportTest extends CdntaxreceiptsBase {

  /**
   * @var array
   *   We always create one contact to start with.
   */
  protected $contact;

  public function setUp(): void {
    parent::setUp();
    $this->createUserAndLogIn();
    $this->contact = $this->createContact();
  }

  public function testReport() {
    $this->setDeliveryMethod(CDNTAX_DELIVERY_PRINT_ONLY);

    $contribution = civicrm_api3('Contribution', 'create', [
      'contact_id' => $this->contact['id'],
      'financial_type_id' => 'Donation',
      'total_amount' => '10',
      'receive_date' => date('Y-m-d'),
    ]);

    // go to the contribution
    $this->drupalGet(\CRM_Utils_System::url("civicrm/contact/view/contribution", "reset=1&id={$contribution['id']}&cid={$this->contact['id']}&action=view", TRUE, NULL, FALSE));
    $this->assertPageHasNoErrorMessages();

    // click the tax receipt button
    $this->getSession()->getPage()->pressButton('Tax Receipt');
    $this->assertPageHasNoErrorMessages();
    $this->assertSession()->waitForElementVisible('css', '.crm-button_qf_ViewTaxReceipt_next');
    $this->getSession()->getPage()->pressButton('_qf_ViewTaxReceipt_next-bottom');
    $this->assertPageHasNoErrorMessages();

    // go to the report
    $this->drupalGet(\CRM_Utils_System::url('civicrm/report/cdntaxreceipts%3Areceiptsissued', 'reset=1', TRUE, NULL, FALSE));
    $this->assertPageHasNoErrorMessages();

    // click the button
    $this->getSession()->getPage()->pressButton('_qf_ReceiptsIssued_submit');
    $this->htmlOutput();
    $this->assertPageHasNoErrorMessages();

    $this->assertSession()->pageTextContains('Tax Receipts - Receipts Issued');
    $this->assertSession()->pageTextContains('C-00000001');
    $this->assertSession()->pageTextContains('Total Amount Issued');
    $this->assertSession()->pageTextContains('$ 10.00');
  }

}
