<?php
/*
 * Placeholder Mirakl synchronization class for OpenXE.
 * Handles product, order and invoice exchange with Mirakl marketplace.
 */

class MiraklSync
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
   * Retrieve products from Mirakl and store them locally.
   */
  public function syncProducts()
  {
    try {
      $products = $this->request('GET', '/api/products');
      if (empty($products['products'])) {
        return;
      }
      foreach ($products['products'] as $product) {
        $sku  = $product['product_sku'];
        $name = $product['title'];
        $id   = $this->db->Select('SELECT id FROM artikel WHERE nummer=:sku', $sku);
        if ($id) {
          $this->db->Update('UPDATE artikel SET name_de=:name WHERE id=:id', $name, $id);
        } else {
          $this->db->Insert('INSERT INTO artikel (typ, nummer, name_de) VALUES ("default", :sku, :name)', $sku, $name);
        }
        $this->db->Insert('REPLACE INTO marketplace_products (marketplace, external_id, sku, stock, data) VALUES ("mirakl", :id, :sku, :stock, :data)', $product['product_id'], $sku, $product['quantity'], json_encode($product));
      }
    } catch (Exception $e) {
      $this->logError('syncProducts: ' . $e->getMessage());
    }
  }

  /**
   * Retrieve Mirakl orders and persist them.
   */
  public function syncOrders()
  {
    try {
      $orders = $this->request('GET', '/api/orders');
      if (empty($orders['orders'])) {
        return;
      }
      foreach ($orders['orders'] as $order) {
        $externalId = $order['id'];
        $exists = $this->db->Select('SELECT external_id FROM marketplace_orders WHERE marketplace="mirakl" AND external_id=:id', $externalId);
        if (!$exists) {
          $this->db->Insert('INSERT INTO marketplace_orders (marketplace, external_id, status, data) VALUES ("mirakl", :id, :status, :data)', $externalId, $order['order_state'], json_encode($order));
        } else {
          $this->db->Update('UPDATE marketplace_orders SET status=:status, data=:data WHERE marketplace="mirakl" AND external_id=:id', $order['order_state'], json_encode($order), $externalId);
        }
      }
    } catch (Exception $e) {
      $this->logError('syncOrders: ' . $e->getMessage());
    }
  }

  /**
   * Push stock levels to Mirakl.
   */
  public function updateStock()
  {
    try {
      $rows = $this->db->SelectArr('SELECT external_id, stock FROM marketplace_products WHERE marketplace="mirakl"');
      if (empty($rows)) {
        return;
      }
      $payload = [];
      foreach ($rows as $row) {
        $payload[] = ['product_id' => $row['external_id'], 'quantity' => (int)$row['stock']];
      }
      $this->request('POST', '/api/stock', $payload);
    } catch (Exception $e) {
      $this->logError('updateStock: ' . $e->getMessage());
    }
  }

  /**
   * Upload invoices for Mirakl orders.
   */
  public function sendInvoices()
  {
    try {
      $orders = $this->db->SelectArr('SELECT external_id, data FROM marketplace_orders WHERE marketplace="mirakl" AND status="SHIPPED"');
      if (empty($orders)) {
        return;
      }
      foreach ($orders as $order) {
        $this->request('POST', '/api/orders/' . $order['external_id'] . '/documents', ['type' => 'INVOICE', 'data' => base64_encode('PDFDATA')]);
      }
    } catch (Exception $e) {
      $this->logError('sendInvoices: ' . $e->getMessage());
    }
  }

  /**
   * Perform HTTP request against Mirakl API.
   *
   * @param string       $method
   * @param string       $endpoint
   * @param array|string $payload
   *
   * @return array|null
   * @throws Exception
   */
  private function request($method, $endpoint, $payload = [])
  {
    $base = rtrim($this->config['base_url'] ?? '', '/');
    if ($base === '') {
      throw new Exception('Missing base_url config');
    }
    $url = $base . $endpoint;
    $headers = [
      'X-Mirakl-API-Key: ' . ($this->config['api_key'] ?? ''),
      'Content-Type: application/json',
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($method !== 'GET') {
      curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($payload) ? $payload : json_encode($payload));
    } elseif (!empty($payload) && is_array($payload)) {
      $url .= '?' . http_build_query($payload);
      curl_setopt($ch, CURLOPT_URL, $url);
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
   * Ensure helper tables exist for Mirakl sync.
   */
  private function ensureTables()
  {
    $this->db->Update('CREATE TABLE IF NOT EXISTS marketplace_products (marketplace VARCHAR(20), external_id VARCHAR(64), sku VARCHAR(64), stock INT, data LONGTEXT, PRIMARY KEY(marketplace, external_id))');
    $this->db->Update('CREATE TABLE IF NOT EXISTS marketplace_orders (marketplace VARCHAR(20), external_id VARCHAR(64), status VARCHAR(32), data LONGTEXT, PRIMARY KEY(marketplace, external_id))');
  }

  private function logError($message)
  {
    error_log('[MiraklSync] ' . $message);
  }
}
