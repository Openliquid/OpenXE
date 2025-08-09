<?php
require_once dirname(__DIR__) . '/conf/main.conf.php';
require_once dirname(__DIR__) . '/phpwf/plugins/class.mysql.php';
require_once dirname(__DIR__) . '/www/lib/class.amazonsync.php';

$conf = new Config();
$db   = new DB($conf->WFdbhost, $conf->WFdbname, $conf->WFdbuser, $conf->WFdbpass, null, $conf->WFdbport);

function loadMarketplaceConfig(DB $db, $marketplace)
{
  $db->Update('CREATE TABLE IF NOT EXISTS marketplace_config (marketplace VARCHAR(20), param VARCHAR(64), value TEXT, PRIMARY KEY (marketplace,param))');
  $rows = $db->SelectArr('SELECT param, value FROM marketplace_config WHERE marketplace=:m', $marketplace);
  $config = [];
  if ($rows) {
    foreach ($rows as $row) {
      $config[$row['param']] = $row['value'];
    }
  }
  return $config;
}

$config = loadMarketplaceConfig($db, 'amazon');
$sync   = new AmazonSync($db, $config);

try {
  $sync->syncProducts();
  $sync->syncOrders();
  $sync->updateStock();
  $sync->sendInvoices();
} catch (Exception $e) {
  error_log('AmazonSync cronjob failed: ' . $e->getMessage());
}
