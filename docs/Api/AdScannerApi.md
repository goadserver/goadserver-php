# GoAdServerApi\AdScannerApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1AdScannerGet()**](AdScannerApi.md#apiV1AdScannerGet) | **GET** /api/v1/ad-scanner | List scan tasks. |
| [**apiV1AdScannerIdDelete()**](AdScannerApi.md#apiV1AdScannerIdDelete) | **DELETE** /api/v1/ad-scanner/{id} | Delete a scan task. |
| [**apiV1AdScannerIdIgnoreDomainPut()**](AdScannerApi.md#apiV1AdScannerIdIgnoreDomainPut) | **PUT** /api/v1/ad-scanner/{id}/ignore-domain | Ignore (or un-ignore) a flagged domain on a task. |
| [**apiV1AdScannerIdManualItemsGet()**](AdScannerApi.md#apiV1AdScannerIdManualItemsGet) | **GET** /api/v1/ad-scanner/{id}/manual-items | List a manual task&#39;s items. |
| [**apiV1AdScannerIdManualItemsItemIdStatePut()**](AdScannerApi.md#apiV1AdScannerIdManualItemsItemIdStatePut) | **PUT** /api/v1/ad-scanner/{id}/manual-items/{itemId}/state | Pause, resume or delete a manual item. |
| [**apiV1AdScannerIdManualItemsPost()**](AdScannerApi.md#apiV1AdScannerIdManualItemsPost) | **POST** /api/v1/ad-scanner/{id}/manual-items | Add or edit a manual item. |
| [**apiV1AdScannerIdPut()**](AdScannerApi.md#apiV1AdScannerIdPut) | **PUT** /api/v1/ad-scanner/{id} | Replace a task&#39;s name, tags and config. |
| [**apiV1AdScannerIdToggleActivePut()**](AdScannerApi.md#apiV1AdScannerIdToggleActivePut) | **PUT** /api/v1/ad-scanner/{id}/toggle-active | Start or stop a scan task. |
| [**apiV1AdScannerPost()**](AdScannerApi.md#apiV1AdScannerPost) | **POST** /api/v1/ad-scanner | Create a scan task. |
| [**apiV1AdScannerReportHashGet()**](AdScannerApi.md#apiV1AdScannerReportHashGet) | **GET** /api/v1/ad-scanner/report/{hash} | One scan&#39;s full report. |
| [**apiV1AdScannerReportsGet()**](AdScannerApi.md#apiV1AdScannerReportsGet) | **GET** /api/v1/ad-scanner/reports | List scan results. |
| [**apiV1AdScannerStatsGet()**](AdScannerApi.md#apiV1AdScannerStatsGet) | **GET** /api/v1/ad-scanner/stats | Scan volume and proxy bandwidth. |
| [**apiV1AdScannerTagsGet()**](AdScannerApi.md#apiV1AdScannerTagsGet) | **GET** /api/v1/ad-scanner/tags | Tags used on the account&#39;s scan tasks. |


## `apiV1AdScannerGet()`

```php
apiV1AdScannerGet()
```

List scan tasks.

A bare array; `config` decoded, `tags` split into an array.  `coverage` — what the task's next run would scan, `{count, reason}` (`null` for `campaigns` targets). When `count` is 0 the task produces no reports, and `reason` says why: `no_smart_zones`, `scanning_off` (no smart zone the task covers has Scanning on), `no_matching_zones` (no zone of an ad type this scan type covers, or none in the chosen networks), `no_recent_traffic` (no matching zone served in the last 5 days), `no_items` (manual task without active items) or `no_matching_items` (manual items of the wrong type for this scan type). `reason` is empty when `count` > 0.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdScannerApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1AdScannerGet();
} catch (Exception $e) {
    echo 'Exception when calling AdScannerApi->apiV1AdScannerGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1AdScannerIdDelete()`

```php
apiV1AdScannerIdDelete($id)
```

Delete a scan task.

Soft delete; the task stops scanning.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdScannerApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1AdScannerIdDelete($id);
} catch (Exception $e) {
    echo 'Exception when calling AdScannerApi->apiV1AdScannerIdDelete: ', $e->getMessage(), PHP_EOL;
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

## `apiV1AdScannerIdIgnoreDomainPut()`

```php
apiV1AdScannerIdIgnoreDomainPut($id, $api_v1_ad_scanner_id_ignore_domain_put_request)
```

Ignore (or un-ignore) a flagged domain on a task.

Toggles `value` in the task's `config.detection.domains.ignored` list: added when absent, removed when present. `type` must be `detection.domains.ignore`. Answers 200 `{success: false}` when the type / value is wrong or the task is not the caller's.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdScannerApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_ad_scanner_id_ignore_domain_put_request = {"type":"detection.domains.ignore","value":"cdn.example.net"}; // \GoAdServerApi\Model\ApiV1AdScannerIdIgnoreDomainPutRequest

try {
    $apiInstance->apiV1AdScannerIdIgnoreDomainPut($id, $api_v1_ad_scanner_id_ignore_domain_put_request);
} catch (Exception $e) {
    echo 'Exception when calling AdScannerApi->apiV1AdScannerIdIgnoreDomainPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_ad_scanner_id_ignore_domain_put_request** | [**\GoAdServerApi\Model\ApiV1AdScannerIdIgnoreDomainPutRequest**](../Model/ApiV1AdScannerIdIgnoreDomainPutRequest.md)|  | |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AdScannerIdManualItemsGet()`

```php
apiV1AdScannerIdManualItemsGet($id)
```

List a manual task's items.

The URLs / tags a `target: manual` task scans. Bare array (empty for an unknown task).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdScannerApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1AdScannerIdManualItemsGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AdScannerApi->apiV1AdScannerIdManualItemsGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1AdScannerIdManualItemsItemIdStatePut()`

```php
apiV1AdScannerIdManualItemsItemIdStatePut($id, $item_id, $api_v1_ad_scanner_id_manual_items_item_id_state_put_request)
```

Pause, resume or delete a manual item.

`fld` = `isactive` (default) or `isdeleted`; `value` 0 / 1. Deleting also deactivates. 200 `{success: false}` when the item is not the caller's.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdScannerApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$item_id = 56; // int
$api_v1_ad_scanner_id_manual_items_item_id_state_put_request = {"fld":"isdeleted","value":1}; // \GoAdServerApi\Model\ApiV1AdScannerIdManualItemsItemIdStatePutRequest

try {
    $apiInstance->apiV1AdScannerIdManualItemsItemIdStatePut($id, $item_id, $api_v1_ad_scanner_id_manual_items_item_id_state_put_request);
} catch (Exception $e) {
    echo 'Exception when calling AdScannerApi->apiV1AdScannerIdManualItemsItemIdStatePut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **item_id** | **int**|  | |
| **api_v1_ad_scanner_id_manual_items_item_id_state_put_request** | [**\GoAdServerApi\Model\ApiV1AdScannerIdManualItemsItemIdStatePutRequest**](../Model/ApiV1AdScannerIdManualItemsItemIdStatePutRequest.md)|  | |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AdScannerIdManualItemsPost()`

```php
apiV1AdScannerIdManualItemsPost($id, $api_v1_ad_scanner_id_manual_items_post_request)
```

Add or edit a manual item.

Without `id` adds an item; with `id` edits that item. `type` = url (default), html, iframe or vast_url; `value` is required. Failures (no value, task not the caller's) answer 200 `{success: false}`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdScannerApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_ad_scanner_id_manual_items_post_request = {"value":"https://lander.example.com/","type":"url","description":"Main lander","isactive":1}; // \GoAdServerApi\Model\ApiV1AdScannerIdManualItemsPostRequest

try {
    $apiInstance->apiV1AdScannerIdManualItemsPost($id, $api_v1_ad_scanner_id_manual_items_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AdScannerApi->apiV1AdScannerIdManualItemsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_ad_scanner_id_manual_items_post_request** | [**\GoAdServerApi\Model\ApiV1AdScannerIdManualItemsPostRequest**](../Model/ApiV1AdScannerIdManualItemsPostRequest.md)|  | |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AdScannerIdPut()`

```php
apiV1AdScannerIdPut($id, $api_v1_ad_scanner_id_put_request)
```

Replace a task's name, tags and config.

Full replace of these three: a `config` or `tags` left out is cleared, so send the whole task. `target` / `scan_type` / `adtypeid` are fixed at create.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdScannerApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_ad_scanner_id_put_request = {"name":"Landing pages","tags":["daily"],"config":{"interval":{"type":"daily","amount":1}}}; // \GoAdServerApi\Model\ApiV1AdScannerIdPutRequest

try {
    $apiInstance->apiV1AdScannerIdPut($id, $api_v1_ad_scanner_id_put_request);
} catch (Exception $e) {
    echo 'Exception when calling AdScannerApi->apiV1AdScannerIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_ad_scanner_id_put_request** | [**\GoAdServerApi\Model\ApiV1AdScannerIdPutRequest**](../Model/ApiV1AdScannerIdPutRequest.md)|  | |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AdScannerIdToggleActivePut()`

```php
apiV1AdScannerIdToggleActivePut($id, $api_v1_ad_scanner_id_toggle_active_put_request)
```

Start or stop a scan task.

`is_active` > 0 starts it; 0 or no body stops it.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdScannerApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_ad_scanner_id_toggle_active_put_request = {"is_active":1}; // \GoAdServerApi\Model\ApiV1AdScannerIdToggleActivePutRequest

try {
    $apiInstance->apiV1AdScannerIdToggleActivePut($id, $api_v1_ad_scanner_id_toggle_active_put_request);
} catch (Exception $e) {
    echo 'Exception when calling AdScannerApi->apiV1AdScannerIdToggleActivePut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_ad_scanner_id_toggle_active_put_request** | [**\GoAdServerApi\Model\ApiV1AdScannerIdToggleActivePutRequest**](../Model/ApiV1AdScannerIdToggleActivePutRequest.md)|  | [optional] |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AdScannerPost()`

```php
apiV1AdScannerPost($api_v1_ad_scanner_post_request)
```

Create a scan task.

- `target` — `campaigns` (your own campaigns' ads), `smartzones`   (default) or `manual` (items you add under   …/{id}/manual-items). Unknown values become `smartzones`. - `scan_type` — `ad_scan` (default), `url_scan` or `vast_scan`. - `config` — only these keys are kept: `smartzone_networks`,   `device_types`, `browsers` `{items, random}`, `interval`   `{type: minutely|hourly|daily, amount}`, `detection` (push,   alerts, popups, cookies, crypto_miners, `domains` `{on, malicious,   suspicious, ignored[], ignore_dates}`, `suspicious_dom`,   `fingerprinting`, `obfuscated_js`, `permission`,   `scan_landing_ads`, `max_landing_ad_scans`), `proxy` `{on,   locations[]}`, `notify` `{enabled, level: all|serious, dest}`. - `tags` — a CSV string or an array of strings.  `target`, `scan_type` and `adtypeid` cannot change later. The task starts active. Proxy scans use metered bandwidth.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdScannerApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_ad_scanner_post_request = {"name":"Landing pages","target":"campaigns","scan_type":"url_scan","tags":["daily"],"config":{"interval":{"type":"hourly","amount":1},"detection":{"push":true,"popups":true,"domains":{"true":true,"malicious":true,"suspicious":true}},"notify":{"enabled":true,"level":"serious","dest":0}}}; // \GoAdServerApi\Model\ApiV1AdScannerPostRequest

try {
    $apiInstance->apiV1AdScannerPost($api_v1_ad_scanner_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AdScannerApi->apiV1AdScannerPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_ad_scanner_post_request** | [**\GoAdServerApi\Model\ApiV1AdScannerPostRequest**](../Model/ApiV1AdScannerPostRequest.md)|  | |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AdScannerReportHashGet()`

```php
apiV1AdScannerReportHashGet($hash)
```

One scan's full report.

The browser report of a single scan (`uid` from the results list): events, redirects, requests, findings, plus the task config. Only the caller's own scans — another account's uid is 404.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdScannerApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$hash = 'hash_example'; // string

try {
    $apiInstance->apiV1AdScannerReportHashGet($hash);
} catch (Exception $e) {
    echo 'Exception when calling AdScannerApi->apiV1AdScannerReportHashGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **hash** | **string**|  | |

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

## `apiV1AdScannerReportsGet()`

```php
apiV1AdScannerReportsGet($datestart, $dateend, $scan_type, $scan_target, $scan_state, $page)
```

List scan results.

One row per scan, 2500 per page (`page`). Defaults to today. `scan_state` 1 = scans that found something, 0 = clean. Rows carry `uid` (open the full report with GET …/report/{uid}), `task_name` and `scanned_item`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdScannerApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$datestart = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime
$dateend = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime
$scan_type = 'scan_type_example'; // string
$scan_target = 'scan_target_example'; // string
$scan_state = 56; // int
$page = 1; // int

try {
    $apiInstance->apiV1AdScannerReportsGet($datestart, $dateend, $scan_type, $scan_target, $scan_state, $page);
} catch (Exception $e) {
    echo 'Exception when calling AdScannerApi->apiV1AdScannerReportsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **datestart** | **\DateTime**|  | [optional] |
| **dateend** | **\DateTime**|  | [optional] |
| **scan_type** | **string**|  | [optional] |
| **scan_target** | **string**|  | [optional] |
| **scan_state** | **int**|  | [optional] |
| **page** | **int**|  | [optional] [default to 1] |

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

## `apiV1AdScannerStatsGet()`

```php
apiV1AdScannerStatsGet($datestart, $dateend, $stats_selcols, $config_id, $country, $proxy_used)
```

Scan volume and proxy bandwidth.

Totals grouped by `stats_selcols` (CSV of scandate, config_id, country; default scandate). `bandwidth` is proxy bytes used.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdScannerApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$datestart = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime
$dateend = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime
$stats_selcols = scandate,config_id; // string
$config_id = 56; // int
$country = 'country_example'; // string
$proxy_used = 56; // int

try {
    $apiInstance->apiV1AdScannerStatsGet($datestart, $dateend, $stats_selcols, $config_id, $country, $proxy_used);
} catch (Exception $e) {
    echo 'Exception when calling AdScannerApi->apiV1AdScannerStatsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **datestart** | **\DateTime**|  | [optional] |
| **dateend** | **\DateTime**|  | [optional] |
| **stats_selcols** | **string**|  | [optional] |
| **config_id** | **int**|  | [optional] |
| **country** | **string**|  | [optional] |
| **proxy_used** | **int**|  | [optional] |

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

## `apiV1AdScannerTagsGet()`

```php
apiV1AdScannerTagsGet()
```

Tags used on the account's scan tasks.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdScannerApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1AdScannerTagsGet();
} catch (Exception $e) {
    echo 'Exception when calling AdScannerApi->apiV1AdScannerTagsGet: ', $e->getMessage(), PHP_EOL;
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
