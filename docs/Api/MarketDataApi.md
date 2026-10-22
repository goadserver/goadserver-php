# GoAdServerApi\MarketDataApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1PricingFiltersGet()**](MarketDataApi.md#apiV1PricingFiltersGet) | **GET** /api/v1/pricing/filters | Labels for the pricing table&#39;s ids. |
| [**apiV1PricingGet()**](MarketDataApi.md#apiV1PricingGet) | **GET** /api/v1/pricing | Network floor prices (minimum bids). |
| [**apiV1SmartNetworksGet()**](MarketDataApi.md#apiV1SmartNetworksGet) | **GET** /api/v1/smart-networks | List the network&#39;s active smart networks. |
| [**apiV1SmartNetworksIdGet()**](MarketDataApi.md#apiV1SmartNetworksIdGet) | **GET** /api/v1/smart-networks/{id} | One smart network. |
| [**apiV1VolumesFiltersGet()**](MarketDataApi.md#apiV1VolumesFiltersGet) | **GET** /api/v1/volumes/filters | Option lists for the volume query. |
| [**apiV1VolumesGet()**](MarketDataApi.md#apiV1VolumesGet) | **GET** /api/v1/volumes | Available traffic volume per country (traffic estimate). |


## `apiV1PricingFiltersGet()`

```php
apiV1PricingFiltersGet()
```

Labels for the pricing table's ids.

Ad types (with the pay types each sells), countries, device / connection types, languages, ISPs, ad zone types and locations. Same handler as the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\MarketDataApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1PricingFiltersGet();
} catch (Exception $e) {
    echo 'Exception when calling MarketDataApi->apiV1PricingFiltersGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PricingGet()`

```php
apiV1PricingGet()
```

Network floor prices (minimum bids).

Every `min_prices_ron` row of the network — the minimum bid per ad type × pay type × device × connection × country × language × ISP × display type × ad zone location. Unfiltered (the panel filters client-side). `price` is returned as stored, in the network's main currency units, exactly as the panel's Pricing page shows it. `paytype`: 0 CPC, 1 CPM. 404 when the tenant's pricing page module is off. Same handler as the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\MarketDataApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1PricingGet();
} catch (Exception $e) {
    echo 'Exception when calling MarketDataApi->apiV1PricingGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1SmartNetworksGet()`

```php
apiV1SmartNetworksGet($search, $filter_id, $filter_name, $sort, $page, $per_page)
```

List the network's active smart networks.

The `rtb_networks` partners SmartAds campaigns buy through (active, not deleted) — the ids behind `smart_network_id` on ads. Same handler as the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\MarketDataApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$search = 'search_example'; // string
$filter_id = 56; // int
$filter_name = 'filter_name_example'; // string
$sort = 'sort_example'; // string
$page = 1; // int
$per_page = 56; // int

try {
    $apiInstance->apiV1SmartNetworksGet($search, $filter_id, $filter_name, $sort, $page, $per_page);
} catch (Exception $e) {
    echo 'Exception when calling MarketDataApi->apiV1SmartNetworksGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **search** | **string**|  | [optional] |
| **filter_id** | **int**|  | [optional] |
| **filter_name** | **string**|  | [optional] |
| **sort** | **string**|  | [optional] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1SmartNetworksIdGet()`

```php
apiV1SmartNetworksIdGet($id)
```

One smart network.

`endpoint` and `bid_type` are always empty (kept for shape compatibility). Same handler as the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\MarketDataApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1SmartNetworksIdGet($id);
} catch (Exception $e) {
    echo 'Exception when calling MarketDataApi->apiV1SmartNetworksIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1VolumesFiltersGet()`

```php
apiV1VolumesFiltersGet()
```

Option lists for the volume query.

Ad types (in the network's own ad type order), device types, connection types (and whether the network filters by them) and the pay types the network has volume rows for. Same handler as the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\MarketDataApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1VolumesFiltersGet();
} catch (Exception $e) {
    echo 'Exception when calling MarketDataApi->apiV1VolumesFiltersGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1VolumesGet()`

```php
apiV1VolumesGet($adtypeid, $device_typeid, $connection_typeid, $paytype)
```

Available traffic volume per country (traffic estimate).

The network's quoted traffic volume (`volume_data`) for one ad type × device type × connection type, summed per country, largest first. Without `paytype` CPC and CPM rows are summed. Same handler as the panel's Volumes page; 404 when its module is off.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\MarketDataApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$adtypeid = 56; // int
$device_typeid = 56; // int
$connection_typeid = 56; // int
$paytype = 56; // int | 0 CPC, 1 CPM (GET /api/v1/volumes/filters → pay_types).

try {
    $apiInstance->apiV1VolumesGet($adtypeid, $device_typeid, $connection_typeid, $paytype);
} catch (Exception $e) {
    echo 'Exception when calling MarketDataApi->apiV1VolumesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **adtypeid** | **int**|  | |
| **device_typeid** | **int**|  | |
| **connection_typeid** | **int**|  | |
| **paytype** | **int**| 0 CPC, 1 CPM (GET /api/v1/volumes/filters → pay_types). | [optional] |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
