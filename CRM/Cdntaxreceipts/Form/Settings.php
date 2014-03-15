<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Cdntaxreceipts_Form_Settings extends CRM_Core_Form {

  CONST SETTINGS = 'CDNTaxReceipts';

  function buildQuickForm() {

    CRM_Utils_System::setTitle(ts('Configure CDN Tax Receipts'));

    $this->processOrgOptions('build');
    $this->processReceiptOptions('build');
    $this->processSystemOptions('build');
    $this->processEmailOptions('build');

    $arr1 = $this->processOrgOptions('defaults');
    $arr2 = $this->processReceiptOptions('defaults');
    $arr3 = $this->processSystemOptions('defaults');
    $arr4 = $this->processEmailOptions('defaults');
    $defaults = array_merge($arr1, $arr2, $arr3, $arr4);
    $this->setDefaults($defaults);

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    parent::buildQuickForm();
  }

  function processOrgOptions($mode) {
    if ( $mode == 'build' ) {
      $this->add('text', 'org_name', ts('Organization Name'));
      $this->add('text', 'org_address_line1', ts('Address Line 1'));
      $this->add('text', 'org_address_line2', ts('Address Line 2'));
      $this->add('text', 'org_tel', ts('Telephone'));
      $this->add('text', 'org_fax', ts('Fax'));
      $this->add('text', 'org_email', ts('Email'));
      $this->add('text', 'org_web', ts('Website'));
      $this->add('text', 'org_charitable_no', ts('Charitable Registration Number'));

      $this->addRule('org_name', 'Enter Organization Name', 'required');
      $this->addRule('org_address_line1', 'Enter Address Line 1', 'required');
      $this->addRule('org_address_line2', 'Enter Address Line 2', 'required');
      $this->addRule('org_tel', 'Enter Telephone', 'required');
      $this->addRule('org_email', 'Enter Email', 'required');
      $this->addRule('org_web', 'Enter Website', 'required');
      $this->addRule('org_charitable_no', 'Enter Charitable Number', 'required');
    }
    else if ( $mode == 'defaults' ) {
      $defaults = array(
        'org_name' => CRM_Core_BAO_Setting::getItem(self::SETTINGS, 'org_name'),
        'org_address_line1' => CRM_Core_BAO_Setting::getItem(self::SETTINGS, 'org_address_line1'),
        'org_address_line2' => CRM_Core_BAO_Setting::getItem(self::SETTINGS, 'org_address_line2'),
        'org_tel' => CRM_Core_BAO_Setting::getItem(self::SETTINGS, 'org_tel'),
        'org_fax' => CRM_Core_BAO_Setting::getItem(self::SETTINGS, 'org_fax'),
        'org_email' => CRM_Core_BAO_Setting::getItem(self::SETTINGS, 'org_email'),
        'org_web' => CRM_Core_BAO_Setting::getItem(self::SETTINGS, 'org_web'),
        'org_charitable_no' => CRM_Core_BAO_Setting::getItem(self::SETTINGS, 'org_charitable_no'),
      );
      return $defaults;
    }
    else if ( $mode == 'post' ) {
      $values = $this->exportValues();
      CRM_Core_BAO_Setting::setItem($values['org_name'], self::SETTINGS, 'org_name');
      CRM_Core_BAO_Setting::setItem($values['org_address_line1'], self::SETTINGS, 'org_address_line1');
      CRM_Core_BAO_Setting::setItem($values['org_address_line2'], self::SETTINGS, 'org_address_line2');
      CRM_Core_BAO_Setting::setItem($values['org_tel'], self::SETTINGS, 'org_tel');
      CRM_Core_BAO_Setting::setItem($values['org_fax'], self::SETTINGS, 'org_fax');
      CRM_Core_BAO_Setting::setItem($values['org_email'], self::SETTINGS, 'org_email');
      CRM_Core_BAO_Setting::setItem($values['org_web'], self::SETTINGS, 'org_web');
      CRM_Core_BAO_Setting::setItem($values['org_charitable_no'], self::SETTINGS, 'org_charitable_no');
    }

  }

  function processReceiptOptions($mode) {
    if ( $mode == 'build' ) {
      $this->add('text', 'receipt_prefix', ts('Receipt Prefix'));
      $this->add('text', 'receipt_authorized_signature_text', ts('Authorized Signature Text'));

      $config = CRM_Core_Config::singleton( );
      if ($config->maxImportFileSize >= 8388608 ) {
        $uploadFileSize = 8388608;
      } else {
        $uploadFileSize = $config->maxImportFileSize;
      }
      $uploadSize = round(($uploadFileSize / (1024*1024)), 2);

      $this->assign('uploadSize', $uploadSize );
      $this->setMaxFileSize( $uploadFileSize );

      $this->addElement('file', 'receipt_logo', ts('Organization Logo'), 'size=30 maxlength=60');
      $this->addUploadElement('receipt_logo');
      $this->addRule( 'receipt_logo', ts('File size should be less than %1 MBytes (%2 bytes)', array(1 => $uploadSize, 2 => $uploadFileSize)), 'maxfilesize', $uploadFileSize );

      $this->addElement('file', 'receipt_signature', ts('Signature Image'), 'size=30 maxlength=60');
      $this->addUploadElement('receipt_signature');
      $this->addRule( 'receipt_signature', ts('File size should be less than %1 MBytes (%2 bytes)', array(1 => $uploadSize, 2 => $uploadFileSize)), 'maxfilesize', $uploadFileSize );

      $this->addElement('file', 'receipt_watermark', ts('Watermark Image'), 'size=30 maxlength=60');
      $this->addUploadElement('receipt_watermark');
      $this->addRule( 'receipt_watermark', ts('File size should be less than %1 MBytes (%2 bytes)', array(1 => $uploadSize, 2 => $uploadFileSize)), 'maxfilesize', $uploadFileSize );

      $this->addElement('file', 'receipt_pdftemplate', ts('PDF Template'), 'size=30 maxlength=60');
      $this->addUploadElement('receipt_pdftemplate');
      $this->addRule( 'receipt_pdftemplate', ts('File size should be less than %1 MBytes (%2 bytes)', array(1 => $uploadSize, 2 => $uploadFileSize)), 'maxfilesize', $uploadFileSize );
    }
    else if ( $mode == 'defaults' ) {
      $defaults = array(
        'receipt_prefix' => CRM_Core_BAO_Setting::getItem(self::SETTINGS, 'receipt_prefix'),
        'receipt_authorized_signature_text' => CRM_Core_BAO_Setting::getItem(self::SETTINGS, 'receipt_authorized_signature_text'),
      );
      return $defaults;
    }
    else if ( $mode == 'post' ) {
      $values = $this->exportValues();
      CRM_Core_BAO_Setting::setItem($values['receipt_prefix'], self::SETTINGS, 'receipt_prefix');
      CRM_Core_BAO_Setting::setItem($values['receipt_authorized_signature_text'], self::SETTINGS, 'receipt_authorized_signature_text');

      $receipt_logo = $this->getSubmitValue('receipt_logo');
      $receipt_signature = $this->getSubmitValue('receipt_signature');
      $receipt_watermark = $this->getSubmitValue('receipt_watermark');
      $receipt_pdftemplate = $this->getSubmitValue('receipt_pdftemplate');

      $config = CRM_Core_Config::singleton( );
      foreach ( array('receipt_logo', 'receipt_signature', 'receipt_watermark', 'receipt_pdftemplate') as $key ) {
        $upload_file = $this->getSubmitValue($key);
        if (is_array($upload_file)) {
          if ( $upload_file['error'] == 0 ) {
            $filename = $config->customFileUploadDir . CRM_Utils_File::makeFileName($upload_file['name']);
            move_uploaded_file($upload_file['tmp_name'], $filename);
            CRM_Core_BAO_Setting::setItem($filename, self::SETTINGS, $key);
          }
        }
      }
    }
  }

  function processSystemOptions($mode) {
    if ( $mode == 'build' ) {
      $this->addElement('checkbox', 'issue_inkind', ts('Setup in-kind receipts?'));

      $yesno_options = array();
      $yesno_options[] = $this->createElement('radio', NULL, NULL, 'Yes', 1);
      $yesno_options[] = $this->createElement('radio', NULL, NULL, 'No', 0);
      $this->addGroup($yesno_options, 'enable_email', ts('Send receipts by email?'));
      $this->addRule('enable_email', 'Enable or disable email receipts', 'required');
    }
    else if ( $mode == 'defaults' ) {
      $defaults = array(
        'issue_inkind' => 0,
        'enable_email' => CRM_Core_BAO_Setting::getItem(self::SETTINGS, 'enable_email', NULL, 0),
      );
      return $defaults;
    }
    else if ( $mode == 'post' ) {
      $values = $this->exportValues();
      CRM_Core_BAO_Setting::setItem($values['enable_email'], self::SETTINGS, 'enable_email');

      if ( isset($values['issue_inkind']) == 1 ) {
        cdntaxreceipts_configure_inkind_fields();
      }
    }
  }

  function processEmailOptions($mode) {
    if ( $mode == 'build' ) {
      $this->add('text', 'email_subject', ts('Email Subject'));
      $this->add('text', 'email_from', ts('Email From'));
      $this->add('text', 'email_archive', ts('Archive Email'));
      $this->addElement('textarea', 'email_message', ts('Email Message'));

      $this->addRule('email_subject', 'Enter email subject', 'required');
      $this->addRule('email_from', 'Enter email from address', 'required');
      $this->addRule('email_archive', 'Enter email archive address', 'required');
      $this->addRule('email_message', 'Enter email message', 'required');
    }
    else if ( $mode == 'defaults' ) {
      $subject = ts('Your Tax Receipt');
      $message = ts('Attached please find your official tax receipt for income tax purposes.');
      $defaults = array(
        'email_subject' => CRM_Core_BAO_Setting::getItem(self::SETTINGS, 'email_subject', NULL, $subject),
        'email_from' => CRM_Core_BAO_Setting::getItem(self::SETTINGS, 'email_from'),
        'email_archive' => CRM_Core_BAO_Setting::getItem(self::SETTINGS, 'email_archive'),
        'email_message' => CRM_Core_BAO_Setting::getItem(self::SETTINGS, 'email_message', NULL, $message),
      );
      return $defaults;
    }
    else if ( $mode == 'post' ) {
      $values = $this->exportValues();
      CRM_Core_BAO_Setting::setItem($values['email_subject'], self::SETTINGS, 'email_subject');
      CRM_Core_BAO_Setting::setItem($values['email_from'], self::SETTINGS, 'email_from');
      CRM_Core_BAO_Setting::setItem($values['email_archive'], self::SETTINGS, 'email_archive');
      CRM_Core_BAO_Setting::setItem($values['email_message'], self::SETTINGS, 'email_message');
    }
  }

  function postProcess() {
    parent::postProcess();
    $this->processOrgOptions('post');
    $this->processReceiptOptions('post');
    $this->processSystemOptions('post');
    $this->processEmailOptions('post');

    $statusMsg = ts('Your settings have been saved.');
    CRM_Core_Session::setStatus( $statusMsg, '', 'success' );
  }
}
