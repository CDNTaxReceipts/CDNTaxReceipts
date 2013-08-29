{* Confirmation of tax receipts  *}
<div class="crm-block crm-form-block crm-contact-task-delete-form-block">
<div class="messages status no-popup">
  <div class="icon inform-icon"></div>
  You have selected <strong>{$totalSelectedContacts}</strong> contacts. The summary below includes these contacts only.
</div>
  <table>
    <thead>
      <th width=30%>Select Tax Year</th>
      <th width=30%>Receipts Outstanding</th>
      <th width=20%>Email</th>
      <th>Print</th>
    </thead>
    {foreach from=$receiptYears item=year}
      {assign var="key" value="issue_$year"}
      <tr>
        <td>{$form.receipt_year.$key.html}</td>
        <td>{if $receiptCount.$year.total}{$receiptCount.$year.total} ({$receiptCount.$year.contrib} contributions){else}0{/if}</td>
        <td>{$receiptCount.$year.email}</td>
        <td>{$receiptCount.$year.print}</td>
      </tr>
    {/foreach}
  </table>
  <p>Clicking 'Issue Tax Receipts' will issue annual tax receipts for the selected year. Annual tax receipts are a sum
    total of all eligible contributions, received from the donor during the selected year, that have not been receipted individually.</p>
  <p>Only one annual tax receipt can be issued per donor, per year. If the donor has eligible contributions that
  were recorded after the annual receipt was issued, those contributions must be receipted one at a time. Use the Find
  Contributions action to issue those receipts.</p>
  <p><strong>This action cannot be undone.</strong> Tax receipts will be logged for auditing purposes,
    and a copy of each receipt will be submitted to the tax receipt archive.
  <ul>
  <li>Email receipts will be emailed directly to the contributor.</li>
  <li>Print receipts will be compiled into a file for download.  Please print and mail any receipts in this file.</li>
  </ul></p>
  <p>{$form.is_preview.html} {$form.is_preview.label} (Generates receipts marked 'preview', but does not issue the receipts.  No logging or emails sent.)</p>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
</div>
