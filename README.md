CDNTaxReceipts
==============

Canadian Tax Receipts extension for CiviCRM

To set up the extension:
---------------

- Make sure your CiviCRM Extensions directory is set (Administer > System Settings > Directories).  I suggest $HOME/public_html/administrator/components/com_civicrm/extensions.
- Make sure your CiviCRM Extensions Resource URL is set (Administer > System Settings > Resource URLs). If you used the directory above, then this will be http://<url>/administrator/components/com_civicrm/extensions/.
- Unpack the attached code in the Extensions directory. This will create the directory tree public_html/administrator/components/com_civicrm/extensions/org.civicrm.cdntaxreceipts/...
- Enable the extension at Administer > System Settings > Manage Extensions
- Configure CDN Tax Receipts at Administer > CiviContribute > CDN Tax Receipts. (Take note of the dimensions for each of the image parameters. Correct sizing is important. You might need to try a few times to get it right.)


Now you should be able to use the module:

- Pull up a contact, go to Contributions tab, view the contribution. You should see a "Tax Receipt" button with a white (or red) maple leaf. White indicates no receipt has been issued, red indicates receipt has been issued.
- Go to Contributions > Find Contributions. You will see "Issue Tax Receipts" in the - Actions - drop-down. This lets you issue tax receipts in bulk.
- Go to Contacts > Find Contacts. You will see "Issue Annual Tax Receipts" in the - Actions - drop-down. This lets you issue annual tax receipts.

The extension also enables two report templates, which can be used as the basis for some reports:

- Tax Receipts - Receipts Issued
- Tax Receipts - Receipts Not Issued


hook_cdntaxreceipts_eligible()
---------------------

You may be in a situation where certain Contributions are eligible for tax receipts and others are not (e.g. donations are receiptable, but only for individuals, and event fees are not receiptable). If this is the case, there is a PHP hook hook_cdntaxreceipts_eligible($contribution) that can be used for complex eligibility criteria. Hook implementations should return one of TRUE or FALSE, wrapped in an array.

    // Example hook implementation:
    //  Contributions have a custo yes/no field called "receiptable. Issue tax receipt
    //  on any contribution where receiptable = Yes.
    function mymodule_receipts_cdntaxreceipts_eligible( $contribution ) {

      // load custom field
      $query = "
      SELECT receiptable_119
      FROM civicrm_value_tax_receipt_23
      WHERE entity_id = %1";

      $params = array(1 => array($contribution->id, 'Integer'));
      $field = CRM_Core_DAO::singleValueQuery($query, $params);

      if ( $field == 1 ) {
        return array(TRUE);
      }
      else {
        return array(FALSE);
      }

    }

By default, a contribution is eligible for tax receipting if it is completed, and if its Financial Type is deductible.

