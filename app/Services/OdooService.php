<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Item;
use App\Constants\Location;

class OdooService
{
    protected string $url;
    protected string $db;
    protected string $user;
    protected string $password;
    protected ?int $uid = null;

    // Field name patterns for auto-detection (Odoo field => internal field)
    protected array $fieldPatterns = [
        'lot_number' => ['lot_id', 'x_lot_number', 'license_plate', 'x_license_plate', 'name', 'x_nopol'],
        'product' => ['product_id', 'x_product', 'product_name', 'x_product_name', 'display_name'],
        'location' => ['location_id', 'x_location', 'location_name', 'x_location_name'],
        'internal_reference' => ['default_code', 'x_internal_ref', 'internal_reference', 'x_code'],
        'rental_id' => ['x_rental_id', 'rental_id', 'x_so_id', 'sale_order_id', 'x_contract_id'],
        'rental_type' => ['x_rental_type', 'rental_type', 'x_type', 'x_contract_type'],
        'actual_start_rental' => ['x_start_date', 'x_rental_start', 'start_date', 'x_actual_start'],
        'actual_end_rental' => ['x_end_date', 'x_rental_end', 'end_date', 'x_actual_end'],
        'is_vendor_rent' => ['x_is_vendor_rent', 'x_vendor_rent', 'is_vendor', 'x_sewa_vendor'],
        'reserved_lot' => ['x_reserved_lot', 'x_original_lot', 'reserved_lot'],
        'year' => ['x_year', 'year', 'x_tahun', 'manufacture_year'],
        'km_last' => ['x_km', 'x_km_last', 'odometer', 'x_odometer'],
        'on_hand_quantity' => ['quantity', 'qty', 'product_qty', 'qty_available'],
    ];

    public function __construct()
    {
        $config = Setting::getOdooConfig();
        $this->url = rtrim($config['url'] ?? '', '/');
        $this->db = $config['db'] ?? '';
        $this->user = $config['user'] ?? '';
        $this->password = $config['password'] ?? '';
    }

    /**
     * Test connection to Odoo
     */
    public function testConnection(): array
    {
        try {
            if (empty($this->url) || empty($this->db) || empty($this->user) || empty($this->password)) {
                return ['success' => false, 'message' => 'Missing configuration. Please fill all fields.'];
            }

            $uid = $this->authenticate();
            
            if ($uid && is_numeric($uid)) {
                return ['success' => true, 'message' => "Connection successful! User ID: {$uid}"];
            }
            
            return ['success' => false, 'message' => 'Authentication failed. Check credentials.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Authenticate with Odoo and return user ID
     */
    protected function authenticate(): ?int
    {
        $commonUrl = $this->url . '/xmlrpc/2/common';
        
        $request = $this->xmlrpcEncode('authenticate', [
            $this->db,
            $this->user,
            $this->password,
            []
        ]);

        $response = $this->sendRequest($commonUrl, $request);
        $result = $this->xmlrpcDecode($response);
        
        if (is_array($result) && isset($result['faultCode'])) {
            throw new \Exception($result['faultString'] ?? 'Unknown XML-RPC error');
        }

        $this->uid = is_numeric($result) ? (int)$result : null;
        return $this->uid;
    }

    /**
     * Execute a method on an Odoo model
     */
    protected function execute(string $model, string $method, array $args = [], array $kwargs = []): mixed
    {
        if (!$this->uid) {
            $this->authenticate();
        }

        if (!$this->uid) {
            throw new \Exception('Not authenticated');
        }

        $objectUrl = $this->url . '/xmlrpc/2/object';
        
        $request = $this->xmlrpcEncode('execute_kw', [
            $this->db,
            $this->uid,
            $this->password,
            $model,
            $method,
            $args,
            $kwargs
        ]);

        $response = $this->sendRequest($objectUrl, $request);
        $result = $this->xmlrpcDecode($response);

        if (is_array($result) && isset($result['faultCode'])) {
            throw new \Exception($result['faultString'] ?? 'Unknown XML-RPC error');
        }

        return $result;
    }

    /**
     * Detect available fields from Odoo model
     */
    public function detectFields(string $model = 'stock.quant'): array
    {
        try {
            $fields = $this->execute($model, 'fields_get', [], ['attributes' => ['string', 'type']]);
            return ['success' => true, 'fields' => $fields, 'model' => $model];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'fields' => []];
        }
    }

    /**
     * Fetch a sample record to understand structure
     */
    public function fetchSample(string $model = 'stock.quant'): array
    {
        try {
            $records = $this->execute($model, 'search_read', [[]], ['limit' => 1]);
            return ['success' => true, 'sample' => $records[0] ?? [], 'model' => $model];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'sample' => []];
        }
    }

    /**
     * Auto-detect field mapping based on available fields
     */
    public function autoDetectMapping(array $availableFields): array
    {
        $mapping = [];
        
        foreach ($this->fieldPatterns as $internalField => $odooPatterns) {
            foreach ($odooPatterns as $pattern) {
                if (isset($availableFields[$pattern])) {
                    $mapping[$internalField] = $pattern;
                    break;
                }
            }
        }
        
        return $mapping;
    }

    /**
     * Fetch inventory data from Odoo with auto-detection
     */
    public function fetchInventory(string $model = 'stock.quant'): array
    {
        try {
            // First detect available fields
            $fieldsResult = $this->detectFields($model);
            if (!$fieldsResult['success']) {
                return $fieldsResult;
            }
            
            $availableFields = $fieldsResult['fields'];
            $mapping = $this->autoDetectMapping($availableFields);
            
            // Fetch all records with mapped fields
            $odooFields = array_values($mapping);
            if (empty($odooFields)) {
                // Fallback to basic fields
                $odooFields = ['name', 'product_id', 'location_id', 'quantity'];
            }
            
            $records = $this->execute($model, 'search_read', [[]], [
                'fields' => $odooFields,
                'limit' => 10000
            ]);
            
            return [
                'success' => true, 
                'data' => $records, 
                'count' => count($records),
                'mapping' => $mapping,
                'model' => $model
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Transform Odoo data to internal Item format
     */
    public function transformToItems(array $odooRecords, array $mapping): array
    {
        $items = [];
        $today = now()->format('Y-m-d');
        
        foreach ($odooRecords as $record) {
            $item = [
                'product' => $this->extractValue($record, $mapping, 'product'),
                'lot_number' => $this->extractValue($record, $mapping, 'lot_number'),
                'internal_reference' => $this->extractValue($record, $mapping, 'internal_reference'),
                'location' => $this->extractValue($record, $mapping, 'location'),
                'on_hand_quantity' => (float)($this->extractValue($record, $mapping, 'on_hand_quantity') ?? 1),
                'rental_id' => $this->extractValue($record, $mapping, 'rental_id'),
                'rental_type' => $this->extractValue($record, $mapping, 'rental_type'),
                'actual_start_rental' => $this->parseDate($this->extractValue($record, $mapping, 'actual_start_rental')),
                'actual_end_rental' => $this->parseDate($this->extractValue($record, $mapping, 'actual_end_rental')),
                'reserved_lot' => $this->extractValue($record, $mapping, 'reserved_lot'),
                'year' => $this->extractValue($record, $mapping, 'year'),
                'km_last' => $this->extractValue($record, $mapping, 'km_last'),
                'is_vendor_rent' => $this->parseBool($this->extractValue($record, $mapping, 'is_vendor_rent')),
            ];
            
            // Skip empty records
            if (empty($item['lot_number']) && empty($item['product'])) {
                continue;
            }
            
            // Calculate location category
            $item = $this->calculateLocationFlags($item, $today);
            
            $items[] = $item;
        }
        
        return $items;
    }

    /**
     * Extract value from Odoo record using mapping
     */
    protected function extractValue(array $record, array $mapping, string $internalField): mixed
    {
        $odooField = $mapping[$internalField] ?? null;
        if (!$odooField || !isset($record[$odooField])) {
            return null;
        }
        
        $value = $record[$odooField];
        
        // Handle Odoo many2one fields (returns [id, name])
        if (is_array($value) && count($value) === 2 && is_int($value[0])) {
            return $value[1]; // Return the name part
        }
        
        return $value;
    }

    /**
     * Parse date from various formats
     */
    protected function parseDate($value): ?string
    {
        if (empty($value)) return null;
        
        try {
            if (is_string($value)) {
                return \Carbon\Carbon::parse($value)->format('Y-m-d');
            }
        } catch (\Exception $e) {
            return null;
        }
        
        return null;
    }

    /**
     * Parse boolean from various formats
     */
    protected function parseBool($value): bool
    {
        if (is_bool($value)) return $value;
        if (is_string($value)) {
            return in_array(strtolower($value), ['true', 'yes', '1', 'ya']);
        }
        return (bool)$value;
    }

    /**
     * Calculate location-based flags using Location constants
     */
    protected function calculateLocationFlags(array $item, string $today): array
    {
        $location = $item['location'] ?? '';
        
        // Check if it's a rental location
        $isRentalCustomer = $location === Location::RENTAL_CUSTOMER || 
                            str_contains($location, 'Partners/Customers/Rental');
        
        // Check if sold
        $isSold = str_contains($location, 'SOLD') || 
                  str_contains($location, 'Disposal') ||
                  $location === Location::SOLD ||
                  $location === Location::SOLD_STOCK;
        
        // Check if in service
        $isService = str_contains($location, 'Service') ||
                     str_contains($location, 'Workshop') ||
                     $location === Location::SERVICE_INTERNAL ||
                     $location === Location::SERVICE_EXTERNAL ||
                     $location === Location::INSURANCE;
        
        // Check if in stock (any stock car location)
        $isStock = str_contains($location, 'STOCK CAR') ||
                   str_contains($location, 'Transit') ||
                   $location === Location::OPERATION ||
                   $location === Location::TRANSIT;
        
        // Set flags as integers for SQLite compatibility
        $item['is_sold'] = $isSold ? 1 : 0;
        $item['in_stock'] = ($isStock && !$isSold) ? 1 : 0;
        $item['is_active_rental'] = $isRentalCustomer ? 1 : 0;
        $item['is_vendor_rent'] = ($item['is_vendor_rent'] ?? false) ? 1 : 0;
        $item['is_on_hand'] = 1;
        $item['is_stock'] = $isStock ? 1 : 0;
        $item['rental_id_count'] = 0;
        
        return $item;
    }

    /**
     * Sync data from Odoo and save to database
     */
    public function syncAndSave(string $model = 'stock.quant'): array
    {
        $result = $this->fetchInventory($model);
        
        if (!$result['success']) {
            return $result;
        }
        
        $mapping = $result['mapping'];
        $items = $this->transformToItems($result['data'], $mapping);
        
        if (empty($items)) {
            return [
                'success' => false, 
                'message' => 'No valid items found after transformation. Check field mapping.',
                'mapping' => $mapping
            ];
        }
        
        // Add timestamps
        $now = now()->toDateTimeString();
        foreach ($items as &$item) {
            $item['created_at'] = $now;
            $item['updated_at'] = $now;
        }
        
        // Clear existing and insert new
        Item::truncate();
        
        foreach (array_chunk($items, 500) as $chunk) {
            Item::insert($chunk);
        }
        
        // Update metadata
        Setting::set('last_import_source', 'odoo');
        Setting::set('imported_at', now()->toIso8601String());
        
        return [
            'success' => true,
            'message' => "Successfully imported " . count($items) . " items from Odoo.",
            'count' => count($items),
            'mapping' => $mapping
        ];
    }

    /**
     * Encode a method call to XML-RPC format (pure PHP, no extension needed)
     */
    protected function xmlrpcEncode(string $method, array $params): string
    {
        $xml = '<?xml version="1.0"?>';
        $xml .= '<methodCall>';
        $xml .= '<methodName>' . htmlspecialchars($method) . '</methodName>';
        $xml .= '<params>';
        
        foreach ($params as $param) {
            $xml .= '<param>' . $this->encodeValue($param) . '</param>';
        }
        
        $xml .= '</params>';
        $xml .= '</methodCall>';
        
        return $xml;
    }

    /**
     * Encode a PHP value to XML-RPC value
     */
    protected function encodeValue($value): string
    {
        if (is_null($value)) {
            return '<value><nil/></value>';
        }
        
        if (is_bool($value)) {
            return '<value><boolean>' . ($value ? '1' : '0') . '</boolean></value>';
        }
        
        if (is_int($value)) {
            return '<value><int>' . $value . '</int></value>';
        }
        
        if (is_float($value)) {
            return '<value><double>' . $value . '</double></value>';
        }
        
        if (is_string($value)) {
            return '<value><string>' . htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</string></value>';
        }
        
        if (is_array($value)) {
            if ($this->isAssoc($value)) {
                $xml = '<value><struct>';
                foreach ($value as $k => $v) {
                    $xml .= '<member>';
                    $xml .= '<name>' . htmlspecialchars($k) . '</name>';
                    $xml .= $this->encodeValue($v);
                    $xml .= '</member>';
                }
                $xml .= '</struct></value>';
                return $xml;
            } else {
                $xml = '<value><array><data>';
                foreach ($value as $v) {
                    $xml .= $this->encodeValue($v);
                }
                $xml .= '</data></array></value>';
                return $xml;
            }
        }
        
        return '<value><string>' . htmlspecialchars((string)$value) . '</string></value>';
    }

    /**
     * Check if array is associative
     */
    protected function isAssoc(array $arr): bool
    {
        if (empty($arr)) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Decode XML-RPC response to PHP value
     */
    protected function xmlrpcDecode(string $xml): mixed
    {
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        
        if ($doc === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new \Exception('Failed to parse XML response: ' . ($errors[0]->message ?? 'Unknown error'));
        }

        if (isset($doc->fault)) {
            $fault = $this->decodeValue($doc->fault->value);
            return ['faultCode' => $fault['faultCode'] ?? 0, 'faultString' => $fault['faultString'] ?? 'Unknown fault'];
        }

        if (isset($doc->params->param->value)) {
            return $this->decodeValue($doc->params->param->value);
        }

        return null;
    }

    /**
     * Decode XML-RPC value to PHP value
     */
    protected function decodeValue($valueNode): mixed
    {
        if (isset($valueNode->int) || isset($valueNode->i4)) {
            return (int)($valueNode->int ?? $valueNode->i4);
        }
        
        if (isset($valueNode->boolean)) {
            return (string)$valueNode->boolean === '1';
        }
        
        if (isset($valueNode->string)) {
            return (string)$valueNode->string;
        }
        
        if (isset($valueNode->double)) {
            return (float)$valueNode->double;
        }
        
        if (isset($valueNode->nil)) {
            return null;
        }
        
        if (isset($valueNode->array)) {
            $result = [];
            if (isset($valueNode->array->data->value)) {
                foreach ($valueNode->array->data->value as $val) {
                    $result[] = $this->decodeValue($val);
                }
            }
            return $result;
        }
        
        if (isset($valueNode->struct)) {
            $result = [];
            if (isset($valueNode->struct->member)) {
                foreach ($valueNode->struct->member as $member) {
                    $name = (string)$member->name;
                    $result[$name] = $this->decodeValue($member->value);
                }
            }
            return $result;
        }

        return (string)$valueNode;
    }

    /**
     * Send HTTP request to Odoo
     */
    protected function sendRequest(string $url, string $body): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: text/xml; charset=utf-8'],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("cURL error: {$error}");
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception("HTTP error {$httpCode}");
        }
        
        return $response;
    }
}
