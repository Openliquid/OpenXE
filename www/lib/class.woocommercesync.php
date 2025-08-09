<?php
/*
 * Placeholder WooCommerce synchronization class for OpenXE.
 * Uses REST API via cURL for bidirectional data exchange.
 */

class WooCommerceSync
{
  /** @var DB */
  private $db;
  /** @var array */
  private $config;

  public function __construct($db, array $config = [])
  {
    $this->db     = $db;
    $this->config = $config;
    $this->ensureTables();
  }

  /**
   * Fetch products from WooCommerce and store/update them in ERP database.
   */
  public function syncProducts()
  {
    try {
      $products = $this->request('GET', '/products', ['per_page' => 100]);
      if (empty($products)) {
        return;
      }
      foreach ($products as $product) {
        $sku   = $product['sku'];
        if ($sku === '') {
          continue;
        }
        $name  = $product['name'];
        $id    = $this->db->Select('SELECT id FROM artikel WHERE nummer=:sku', $sku);
        if ($id) {
          $this->db->Update('UPDATE artikel SET name_de=:name WHERE id=:id', $name, $id);
        } else {
          $this->db->Insert('INSERT INTO artikel (typ, nummer, name_de) VALUES ("default", :sku, :name)', $sku, $name);
        }
        $this->db->Insert('REPLACE INTO marketplace_products (marketplace, external_id, sku, stock, data) VALUES ("woocommerce", :ext, :sku, :stock, :data)', $product['id'], $sku, $product['stock_quantity'], json_encode($product));
      }
    } catch (Exception $e) {
      $this->logError('syncProducts: ' . $e->getMessage());
    }
  }

  /**
   * Fetch orders from WooCommerce and store them in ERP database.
   */
  public function syncOrders()
  {
    try {
      $orders = $this->request('GET', '/orders', ['per_page' => 50]);
      if (empty($orders)) {
        return;
      }
      foreach ($orders as $order) {
        $externalId = $order['id'];
        $exists = $this->db->Select('SELECT external_id FROM marketplace_orders WHERE marketplace="woocommerce" AND external_id=:id', $externalId);
        if (!$exists) {
          $this->db->Insert('INSERT INTO marketplace_orders (marketplace, external_id, status, data) VALUES ("woocommerce", :id, :status, :data)', $externalId, $order['status'], json_encode($order));
        } else {
          $this->db->Update('UPDATE marketplace_orders SET status=:status, data=:data WHERE marketplace="woocommerce" AND external_id=:id', $order['status'], json_encode($order), $externalId);
        }
      }
    } catch (Exception $e) {
      $this->logError('syncOrders: ' . $e->getMessage());
    }
  }

  /**
   * Push local stock quantities to WooCommerce.
   */
  public function updateStock()
  {
    try {
      $rows = $this->db->SelectArr('SELECT external_id, sku, stock FROM marketplace_products WHERE marketplace="woocommerce"');
      if (empty($rows)) {
        return;
      }
      foreach ($rows as $row) {
        $this->request('PUT', '/products/' . $row['external_id'], ['stock_quantity' => (int)$row['stock']]);
      }
    } catch (Exception $e) {
      $this->logError('updateStock: ' . $e->getMessage());
    }
  }

  /**
   * Create invoice notes back to WooCommerce orders.
   */
  public function sendInvoices()
  {
    try {
      $orders = $this->db->SelectArr('SELECT external_id, data FROM marketplace_orders WHERE marketplace="woocommerce" AND status="completed"');
      if (empty($orders)) {
        return;
      }
      foreach ($orders as $order) {
        $data = json_decode($order['data'], true);
        if (empty($data['invoice_number'])) {
          continue;
        }
        $this->request('POST', '/orders/' . $order['external_id'] . '/notes', ['note' => 'Invoice ' . $data['invoice_number']]);
      }
    } catch (Exception $e) {
      $this->logError('sendInvoices: ' . $e->getMessage());
    }
  }

  /**
   * Execute HTTP request against WooCommerce REST API.
   *
   * @param string $method
   * @param string $endpoint
   * @param array  $params
   *
   * @return array|null
   * @throws Exception
   */
  private function request($method, $endpoint, array $params = [])
  {
    $base = rtrim($this->config['base_url'] ?? '', '/');
    if ($base === '') {
      throw new Exception('Missing base_url config');
    }
    $url = $base . '/wp-json/wc/v3' . $endpoint;
    if ($method === 'GET' && !empty($params)) {
      $url .= '?' . http_build_query($params);
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, ($this->config['consumer_key'] ?? '') . ':' . ($this->config['consumer_secret'] ?? ''));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    if ($method !== 'GET') {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    }
    $result = curl_exec($ch);
    if ($result === false) {
      throw new Exception(curl_error($ch));
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 400) {
      throw new Exception('HTTP ' . $code . ': ' . $result);
    }
    return json_decode($result, true);
  }

  /**
   * Ensure helper tables exist for WooCommerce synchronisation.
   */
  private function ensureTables()
  {
    $this->db->Update('CREATE TABLE IF NOT EXISTS marketplace_products (marketplace VARCHAR(20), external_id VARCHAR(64), sku VARCHAR(64), stock INT, data LONGTEXT, PRIMARY KEY(marketplace, external_id))');
    $this->db->Update('CREATE TABLE IF NOT EXISTS marketplace_orders (marketplace VARCHAR(20), external_id VARCHAR(64), status VARCHAR(32), data LONGTEXT, PRIMARY KEY(marketplace, external_id))');
  }

  private function logError($message)
  {
    error_log('[WooCommerceSync] ' . $message);
  }
}
