<?php
/*
 * Placeholder Amazon synchronization class for OpenXE.
 * Provides bidirectional communication skeleton via Amazon SP-API SDK.
 */

class AmazonSync
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
   * Fetch basic product information from Amazon SP-API and map to ERP database.
   */
  public function syncProducts()
  {
    try {
      $skus = $this->db->SelectArr('SELECT nummer FROM artikel LIMIT 20');
      if (empty($skus)) {
        return;
      }
      foreach ($skus as $row) {
        $sku = $row['nummer'];
        $res = $this->request('GET', '/catalog/v0/items', ['MarketplaceId' => $this->config['marketplace_id'] ?? '', 'SellerSKU' => $sku]);
        if (empty($res['Items'][0])) {
          continue;
        }
        $item = $res['Items'][0];
        $name = $item['AttributeSets'][0]['Title'] ?? '';
        if ($name !== '') {
          $this->db->Update('UPDATE artikel SET name_de=:name WHERE nummer=:sku', $name, $sku);
        }
        $this->db->Insert('REPLACE INTO marketplace_products (marketplace, external_id, sku, stock, data) VALUES ("amazon", :asin, :sku, 0, :data)', $item['Identifiers']['MarketplaceASIN']['ASIN'] ?? '', $sku, json_encode($item));
      }
    } catch (Exception $e) {
      $this->logError('syncProducts: ' . $e->getMessage());
    }
  }

  /**
   * Retrieve new orders from Amazon and store them locally.
   */
  public function syncOrders()
  {
    try {
      $res = $this->request('GET', '/orders/v0/orders', [
        'MarketplaceIds' => $this->config['marketplace_id'] ?? '',
        'CreatedAfter'   => gmdate('c', strtotime('-1 day')),
      ]);
      if (empty($res['Orders'])) {
        return;
      }
      foreach ($res['Orders'] as $order) {
        $amazonId = $order['AmazonOrderId'];
        $exists   = $this->db->Select('SELECT external_id FROM marketplace_orders WHERE marketplace="amazon" AND external_id=:id', $amazonId);
        if (!$exists) {
          $this->db->Insert('INSERT INTO marketplace_orders (marketplace, external_id, status, data) VALUES ("amazon", :id, :status, :data)', $amazonId, $order['OrderStatus'], json_encode($order));
        } else {
          $this->db->Update('UPDATE marketplace_orders SET status=:status, data=:data WHERE marketplace="amazon" AND external_id=:id', $order['OrderStatus'], json_encode($order), $amazonId);
        }
      }
    } catch (Exception $e) {
      $this->logError('syncOrders: ' . $e->getMessage());
    }
  }

  /**
   * Submit inventory feed to Amazon based on local stock levels.
   */
  public function updateStock()
  {
    try {
      $rows = $this->db->SelectArr('SELECT sku, stock FROM marketplace_products WHERE marketplace="amazon"');
      if (empty($rows)) {
        return;
      }
      $feed = [];
      foreach ($rows as $row) {
        $feed[] = [
          'SKU'          => $row['sku'],
          'Quantity'     => (int)$row['stock'],
          'FulfillmentCenterID' => 'DEFAULT',
        ];
      }
      $payload = ['inventory' => $feed];
      $this->request('POST', '/feeds/2021-06-30/feeds', [], [
        'feedType'  => 'POST_INVENTORY_AVAILABILITY_DATA',
        'marketplaceIds' => [$this->config['marketplace_id'] ?? ''],
        'inputFeedDocumentId' => base64_encode(json_encode($payload)),
      ]);
    } catch (Exception $e) {
      $this->logError('updateStock: ' . $e->getMessage());
    }
  }

  /**
   * Send invoice feed to Amazon for completed orders.
   */
  public function sendInvoices()
  {
    try {
      $orders = $this->db->SelectArr('SELECT external_id, data FROM marketplace_orders WHERE marketplace="amazon" AND status="Shipped"');
      if (empty($orders)) {
        return;
      }
      foreach ($orders as $order) {
        $data = json_decode($order['data'], true);
        if (empty($data['AmazonOrderId'])) {
          continue;
        }
        $payload = [
          'orderId' => $data['AmazonOrderId'],
          'invoice' => ['document' => base64_encode('PDFDATA')],
        ];
        $this->request('POST', '/feeds/2021-06-30/feeds', [], [
          'feedType' => 'POST_INVOICE_DATA',
          'marketplaceIds' => [$this->config['marketplace_id'] ?? ''],
          'inputFeedDocumentId' => base64_encode(json_encode($payload)),
        ]);
      }
    } catch (Exception $e) {
      $this->logError('sendInvoices: ' . $e->getMessage());
    }
  }

  /**
   * Execute signed HTTP request to Amazon SP-API.
   *
   * @param string $method
   * @param string $path
   * @param array  $query
   * @param array  $body
   *
   * @return array|null
   * @throws Exception
   */
  private function request($method, $path, array $query = [], array $body = [])
  {
    $endpoint = rtrim($this->config['endpoint'] ?? 'https://sellingpartnerapi-eu.amazon.com', '/');
    $region   = $this->config['region'] ?? 'eu-west-1';
    $uri      = $endpoint . $path;
    if (!empty($query)) {
      $uri .= '?' . http_build_query($query);
    }

    $headers = [
      'content-type'       => 'application/json',
      'x-amz-access-token' => $this->config['lwa_access_token'] ?? '',
    ];
    $request = new \GuzzleHttp\Psr7\Request($method, $uri, $headers, empty($body) ? null : json_encode($body));
    $signer  = new \Aws\Signature\SignatureV4('execute-api', $region);
    $credentials = new \Aws\Credentials\Credentials($this->config['access_key'] ?? '', $this->config['secret_key'] ?? '');
    $signed  = $signer->signRequest($request, $credentials);
    $client  = new \GuzzleHttp\Client();
    $response = $client->send($signed);
    $code = $response->getStatusCode();
    if ($code >= 400) {
      throw new Exception('HTTP ' . $code . ': ' . (string)$response->getBody());
    }
    return json_decode((string)$response->getBody(), true);
  }

  /**
   * Ensure local helper tables exist.
   */
  private function ensureTables()
  {
    $this->db->Update('CREATE TABLE IF NOT EXISTS marketplace_products (marketplace VARCHAR(20), external_id VARCHAR(64), sku VARCHAR(64), stock INT, data LONGTEXT, PRIMARY KEY(marketplace, external_id))');
    $this->db->Update('CREATE TABLE IF NOT EXISTS marketplace_orders (marketplace VARCHAR(20), external_id VARCHAR(64), status VARCHAR(32), data LONGTEXT, PRIMARY KEY(marketplace, external_id))');
  }

  /**
   * Basic error logging helper.
   *
   * @param string $message
   */
  private function logError($message)
  {
    error_log('[AmazonSync] ' . $message);
  }
}
