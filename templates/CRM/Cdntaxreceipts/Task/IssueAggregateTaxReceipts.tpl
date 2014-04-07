{* Confirmation of tax receipts  *}
<div class="crm-block crm-form-block crm-contact-task-delete-form-block">
<div class="messages status no-popup">
  <div class="icon inform-icon"></div>
  {ts}You have selected <strong>{$totalSelectedContributions}</strong> contributions including
  <strong>{$receiptList.totals.original}</strong> originals and <strong>{$receiptList.totals.duplicate}</strong> duplicates
    to issue. Note that duplicates cannot be issued by this method and will be skipped. The summary below includes the
    original receipt issue contributions only.{/ts}
</div>
  <table id="cdntax_original_summary" class="cdntax_summary">
    <thead>
      <th width='15%'>{ts}Tax Year{/ts}</th>
      <th width='15%'>{ts}Selected Contributions{/ts}</th>
      <th width='15%'>{ts}Selected Contribution Amount{/ts}</th>
      <th width='10%'>{ts}Number of Contributors{/ts}</th>
      <th width='10%'>{ts}Email{/ts}</th>
      <th width='10%'>{ts}Print{/ts}</th>
      <th width='15%'>{ts}Contributions Not Eligible{/ts}</th>
      <th width='15%'>{ts}Not Eligible Amount{/ts}</th>
    </thead>
    {foreach from=$receiptYears item=year}
      {assign var="key" value="issue_$year"}
      <tr>
        <td>{$form.receipt_year.$key.html}</td>
        <td class="cdntax_numeric">{$receiptList.original.$year.total_contrib}</td>
        <td class="cdntax_numeric">{$receiptList.original.$year.total_amount|crmMoney}</td>
        <td class="cdntax_numeric">{$receiptList.original.$year.total_contacts}</td>
        <td class="cdntax_numeric">{$receiptList.original.$year.email}</td>
        <td class="cdntax_numeric">{$receiptList.original.$year.print}</td>
        <td class="cdntax_numeric">{$receiptList.original.$year.not_eligible}</td>
        <td class="cdntax_numeric">{$receiptList.original.$year.not_eligible_amount|crmMoney}</td>
      </tr>
    {/foreach}
  </table>

  <p>{ts}Clicking 'Issue Tax Receipts' will issue aggregate tax receipts grouped into the selected year(s). These tax receipts are a sum
    total of all selected eligible contributions, received from the donor during the selected year, that have not already been receipted individually.{/ts}</p>
  <p>{ts}<strong>This action cannot be undone.</strong> Tax receipts will be logged for auditing purposes,
    and a copy of each receipt will be submitted to the tax receipt archive.{/ts}
  <ul>
  <li>{ts}Email receipts will be emailed directly to the contributor.{/ts}</li>
  <li>{ts}Print receipts will be compiled into a file for download.  Please print and mail any receipts in this file.{/ts}</li>
  </ul></p>
  <p>{$form.is_preview.html} {$form.is_preview.label} {ts}(Generates receipts marked 'preview', but does not issue the receipts.  No logging or emails sent.){/ts}</p>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
</div>
