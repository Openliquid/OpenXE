<?php
/**
 * Simple configuration UI placeholder for marketplace synchronisation.
 */
class MarketplaceSync
{
  /** @var Application */
  var $app;

  public function __construct($app)
  {
    $this->app = $app;
    $this->app->ActionHandlerInit($this);
    $this->app->ActionHandler('list', 'MarketplaceSyncList');
    $this->app->ActionHandler('save', 'MarketplaceSyncSave');
    $this->app->DefaultAction = 'list';
  }

  /**
   * Display configuration form.
   */
  public function MarketplaceSyncList()
  {
    $config = $this->getConfig();
    $html = '<h1>Marketplace Sync Configuration</h1>';
    $html .= '<form method="post" action="?action=save">';
    $html .= '<h2>Amazon</h2>';
    $html .= 'Access Key <input type="text" name="amazon_access_key" value="'.htmlspecialchars($config['amazon']['access_key'] ?? '').'"><br>';
    $html .= 'Secret Key <input type="text" name="amazon_secret_key" value="'.htmlspecialchars($config['amazon']['secret_key'] ?? '').'"><br>';
    $html .= 'Marketplace ID <input type="text" name="amazon_marketplace_id" value="'.htmlspecialchars($config['amazon']['marketplace_id'] ?? '').'"><br>';
    $html .= 'Region <input type="text" name="amazon_region" value="'.htmlspecialchars($config['amazon']['region'] ?? '').'"><br>';
    $html .= 'Endpoint <input type="text" name="amazon_endpoint" value="'.htmlspecialchars($config['amazon']['endpoint'] ?? '').'"><br>';
    $html .= 'LWA Access Token <input type="text" name="amazon_lwa_access_token" value="'.htmlspecialchars($config['amazon']['lwa_access_token'] ?? '').'"><br>';

    $html .= '<h2>Mirakl</h2>';
    $html .= 'Base URL <input type="text" name="mirakl_base_url" value="'.htmlspecialchars($config['mirakl']['base_url'] ?? '').'"><br>';
    $html .= 'API Key <input type="text" name="mirakl_api_key" value="'.htmlspecialchars($config['mirakl']['api_key'] ?? '').'"><br>';

    $html .= '<h2>WooCommerce</h2>';
    $html .= 'Base URL <input type="text" name="woocommerce_base_url" value="'.htmlspecialchars($config['woocommerce']['base_url'] ?? '').'"><br>';
    $html .= 'Consumer Key <input type="text" name="woocommerce_consumer_key" value="'.htmlspecialchars($config['woocommerce']['consumer_key'] ?? '').'"><br>';
    $html .= 'Consumer Secret <input type="text" name="woocommerce_consumer_secret" value="'.htmlspecialchars($config['woocommerce']['consumer_secret'] ?? '').'"><br>';

    $html .= '<button type="submit">Save</button>';
    $html .= '</form>';
    $this->app->Tpl->Add('PAGE', $html);
  }

  /**
   * Save configuration from form.
   */
  public function MarketplaceSyncSave()
  {
    $this->ensureTable();
    foreach ($_POST as $key => $value) {
      if (strpos($key, 'amazon_') === 0) {
        $marketplace = 'amazon';
        $param = substr($key, 7);
      } elseif (strpos($key, 'mirakl_') === 0) {
        $marketplace = 'mirakl';
        $param = substr($key, 7);
      } elseif (strpos($key, 'woocommerce_') === 0) {
        $marketplace = 'woocommerce';
        $param = substr($key, 12);
      } else {
        continue;
      }
      $this->app->DB->Insert('REPLACE INTO marketplace_config (marketplace, param, value) VALUES (:m,:p,:v)', $marketplace, $param, $value);
    }
    $this->app->Tpl->Set('MESSAGE', 'Configuration saved');
    $this->MarketplaceSyncList();
  }

  /**
   * Load all marketplace config values.
   */
  private function getConfig()
  {
    $this->ensureTable();
    $rows = $this->app->DB->SelectArr('SELECT marketplace, param, value FROM marketplace_config');
    $config = [];
    if ($rows) {
      foreach ($rows as $row) {
        $config[$row['marketplace']][$row['param']] = $row['value'];
      }
    }
    return $config;
  }

  private function ensureTable()
  {
    $this->app->DB->Update('CREATE TABLE IF NOT EXISTS marketplace_config (marketplace VARCHAR(20), param VARCHAR(64), value TEXT, PRIMARY KEY (marketplace,param))');
  }
}
