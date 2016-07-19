<?php

/**
 * Collection of upgrade steps
 */
class CRM_Cdntaxreceipts_Upgrader extends CRM_Cdntaxreceipts_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Run the fresh install script when the module is installed
   */
  public function install() {
    $this->executeSqlFile('sql/install.sql');
  }

  /**
   * Run the uninstall script when the module is uninstalled
   */
  public function uninstall() {
    $this->executeSqlFile('sql/uninstall.sql');
  }

  public function upgrade_1320() {
    $this->ctx->log->info('Applying update 1.3.2');
    $dao =& CRM_Core_DAO::executeQuery("SELECT 1");
    $db_name = $dao->_database;
    $dao =& CRM_Core_DAO::executeQuery("
SELECT COUNT(*) as col_count
FROM information_schema.COLUMNS
WHERE
    TABLE_SCHEMA = '{$db_name}'
AND TABLE_NAME = 'cdntaxreceipts_log'
AND COLUMN_NAME = 'receipt_status'");
   if ($dao->fetch()) {
     if ($dao->col_count == 0) {
       CRM_Core_DAO::executeQuery("ALTER TABLE cdntaxreceipts_log ADD COLUMN receipt_status varchar(10) DEFAULT 'issued'");
       $ndao =& CRM_Core_DAO::executeQuery("
SELECT COUNT(*) as col_count
FROM information_schema.COLUMNS
WHERE
    TABLE_SCHEMA = '{$db_name}'
AND TABLE_NAME = 'cdntaxreceipts_log'
AND COLUMN_NAME = 'receipt_status'");
       if($ndao->fetch()) {
         if ($ndao->col_count == 1) {
           return TRUE;
         }
       }
     }
   }
    return FALSE;
  }

  public function upgrade_1321() {
    $this->ctx->log->info('Applying update 1321');
    CRM_Core_DAO::executeQuery('ALTER TABLE cdntaxreceipts_log ADD email_tracking_id varchar(64) NULL');
    CRM_Core_DAO::executeQuery('ALTER TABLE cdntaxreceipts_log ADD email_opened datetime NULL');
    CRM_Core_DAO::executeQuery('CREATE INDEX contribution_id ON cdntaxreceipts_log_contributions (contribution_id)');
    return TRUE;
  } 

  /**
   * Example: Run an external SQL script
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } // */

}
