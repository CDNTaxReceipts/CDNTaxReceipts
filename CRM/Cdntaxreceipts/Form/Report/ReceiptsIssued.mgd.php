<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'CRM_Cdntaxreceipts_Form_Report_ReceiptsIssued',
    'entity' => 'ReportTemplate',
    'params' => 
    array (
      'version' => 3,
      'label' => 'Tax Receipts - Receipts Issued',
      'description' => 'Tax Receipts - ReceiptsIssued (org.civicrm.cdntaxreceipts)',
      'class_name' => 'CRM_Cdntaxreceipts_Form_Report_ReceiptsIssued',
      'report_url' => 'cdntaxreceipts/receiptsissued',
      'component' => 'CiviContribute',
    ),
  ),
);
