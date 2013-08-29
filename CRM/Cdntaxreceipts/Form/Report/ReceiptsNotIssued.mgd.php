<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'CRM_Cdntaxreceipts_Form_Report_ReceiptsNotIssued',
    'entity' => 'ReportTemplate',
    'params' => 
    array (
      'version' => 3,
      'label' => 'Tax Receipts - Receipts Not Issued',
      'description' => 'Tax Receipts - Receipts Not Issued (org.civicrm.cdntaxreceipts)',
      'class_name' => 'CRM_Cdntaxreceipts_Form_Report_ReceiptsNotIssued',
      'report_url' => 'cdntaxreceipts/receiptsnotissued',
      'component' => 'CiviContribute',
    ),
  ),
);
