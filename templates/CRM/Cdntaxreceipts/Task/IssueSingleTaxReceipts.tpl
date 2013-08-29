{* Confirmation of tax receipts  *}
<div class="crm-block crm-form-block crm-contact-task-delete-form-block">
<div class="messages status no-popup">
  <div class="icon inform-icon"></div>
  You have selected <strong>{$totalSelectedContributions}</strong> contributions. Of these, <strong>{$receiptTotal}</strong> are eligible to receive tax receipts.
</div>
  <table>
    <thead>
      <th>Tax Receipt Status</th>
      <th>Total</th>
      <th>Email</th>
      <th>Print</th>
    </thead>
    <tr>
      <td>Not yet receipted</td>
      <td>{$originalTotal}</td>
      <td>{$receiptCount.original.email}</td>
      <td>{$receiptCount.original.print}</td>
    </tr>
    <tr>
      <td>Already receipted</td>
      <td>{$duplicateTotal}</td>
      <td>{$receiptCount.duplicate.email}</td>
      <td>{$receiptCount.duplicate.print}</td>
    </tr>
  </table>
  <p>{$form.receipt_option.original_only.html}<br />
     {$form.receipt_option.include_duplicates.html}</p>
  <p>Clicking 'Issue Tax Receipts' will issue the selected tax receipts.
    <strong>This action cannot be undone.</strong> Tax receipts will be logged for auditing purposes,
    and a copy of each receipt will be submitted to the tax receipt archive.
  <ul>
  <li>Email receipts will be emailed directly to the contributor.</li>
  <li>Print receipts will be compiled into a file for download.  Please print and mail any receipts in this file.</li>
  </ul></p>
  <p>{$form.is_preview.html} {$form.is_preview.label} (Generates receipts marked 'preview', but does not issue the receipts.  No logging or emails sent.)</p>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
</div>
