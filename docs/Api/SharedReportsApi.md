# GoAdServerApi\SharedReportsApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1SharedReportsGet()**](SharedReportsApi.md#apiV1SharedReportsGet) | **GET** /api/v1/shared-reports | List shared reports. |
| [**apiV1SharedReportsIdDelete()**](SharedReportsApi.md#apiV1SharedReportsIdDelete) | **DELETE** /api/v1/shared-reports/{id} | Delete a shared report. |
| [**apiV1SharedReportsIdGet()**](SharedReportsApi.md#apiV1SharedReportsIdGet) | **GET** /api/v1/shared-reports/{id} | Show a shared report. |
| [**apiV1SharedReportsIdPut()**](SharedReportsApi.md#apiV1SharedReportsIdPut) | **PUT** /api/v1/shared-reports/{id} | Update a shared report. |
| [**apiV1SharedReportsIdToggleActivePut()**](SharedReportsApi.md#apiV1SharedReportsIdToggleActivePut) | **PUT** /api/v1/shared-reports/{id}/toggle-active | Switch a report&#39;s link on or off. |
| [**apiV1SharedReportsPost()**](SharedReportsApi.md#apiV1SharedReportsPost) | **POST** /api/v1/shared-reports | Create a shared report. |
| [**apiV1SharedReportsSetupDataGet()**](SharedReportsApi.md#apiV1SharedReportsSetupDataGet) | **GET** /api/v1/shared-reports/setup-data | Campaigns and sites a report can cover. |


## `apiV1SharedReportsGet()`

```php
apiV1SharedReportsGet($page, $per_page, $search, $filter_isactive)
```

List shared reports.

Advertiser / publisher / SSP / DSP reports (tracker reports are listed in the tracker section of the panel; they can still be read and changed here by id). Each row carries the public `url` and the DSA-transparency `dsa_url`. `filter_isactive` = 0 / 1, `search` matches the name.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SharedReportsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$page = 1; // int
$per_page = 20; // int
$search = 'search_example'; // string
$filter_isactive = 56; // int

try {
    $apiInstance->apiV1SharedReportsGet($page, $per_page, $search, $filter_isactive);
} catch (Exception $e) {
    echo 'Exception when calling SharedReportsApi->apiV1SharedReportsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 20] |
| **search** | **string**|  | [optional] |
| **filter_isactive** | **int**|  | [optional] |

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

## `apiV1SharedReportsIdDelete()`

```php
apiV1SharedReportsIdDelete($id)
```

Delete a shared report.

The link stops working immediately.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SharedReportsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1SharedReportsIdDelete($id);
} catch (Exception $e) {
    echo 'Exception when calling SharedReportsApi->apiV1SharedReportsIdDelete: ', $e->getMessage(), PHP_EOL;
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

## `apiV1SharedReportsIdGet()`

```php
apiV1SharedReportsIdGet($id)
```

Show a shared report.

The stored row with `campaigns` / `sites` / `settings` / `options` decoded.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SharedReportsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1SharedReportsIdGet($id);
} catch (Exception $e) {
    echo 'Exception when calling SharedReportsApi->apiV1SharedReportsIdGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1SharedReportsIdPut()`

```php
apiV1SharedReportsIdPut($id, $api_v1_shared_reports_id_put_request)
```

Update a shared report.

Partial: `name` (non-empty), `tagids` and each of `campaigns` / `sites` / `settings` / `options` replace the stored value when sent. `statstype` cannot change (create a new report) — sending it is 422 `unknown_fields`. The link (`hash`) stays the same.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SharedReportsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_shared_reports_id_put_request = {"campaigns":[{"id":812,"type":"ron"},{"id":813,"type":"ron"}]}; // \GoAdServerApi\Model\ApiV1SharedReportsIdPutRequest

try {
    $apiInstance->apiV1SharedReportsIdPut($id, $api_v1_shared_reports_id_put_request);
} catch (Exception $e) {
    echo 'Exception when calling SharedReportsApi->apiV1SharedReportsIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_shared_reports_id_put_request** | [**\GoAdServerApi\Model\ApiV1SharedReportsIdPutRequest**](../Model/ApiV1SharedReportsIdPutRequest.md)|  | |

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

## `apiV1SharedReportsIdToggleActivePut()`

```php
apiV1SharedReportsIdToggleActivePut($id)
```

Switch a report's link on or off.

No body — flips `isactive`; an inactive link answers 404 to viewers.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SharedReportsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1SharedReportsIdToggleActivePut($id);
} catch (Exception $e) {
    echo 'Exception when calling SharedReportsApi->apiV1SharedReportsIdToggleActivePut: ', $e->getMessage(), PHP_EOL;
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

## `apiV1SharedReportsPost()`

```php
apiV1SharedReportsPost($shared_report_body)
```

Create a shared report.

Mints a public link (`data.url`) that shows the chosen stats to anyone who has it, without a login. Scope:  - `statstype` — advertiser (default), publisher, ssp, dsp or tracker. - `campaigns` — `[{id, type}]` (type ron / cpm / flatrate /   directdeal); empty with no `settings` = an empty report.   `settings.follow_groups: true` plus `tagids` adds every campaign   currently filed under those tags (advertiser reports only). - `sites` — `[{id}]` for publisher reports. - `settings` — viewer filters and layout: `ad_types`,   `selling_types`, `device_types` (id arrays), `countries` (codes),   `visible_metrics`, `visible_conv_types`, `allowed_groupby`,   `default_groupby` (default `period`), `allowed_viewtypes`,   `viewer_filters`, `follow_groups`, `show_names` (false hides   entity names). - `options` — stats adjustment shown to viewers   (`{on, impressions|clicks|paid: {on, type: increase|decrease, value}}`);   dropped unless the account holds the `adjust_stats` right.  Ids are only ever reported within the caller's own stats. Unknown top-level fields → 422 `unknown_fields`. GET /api/v1/shared-reports/setup-data lists the pickable campaigns and sites.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SharedReportsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$shared_report_body = {"name":"Client A — weekly","statstype":"advertiser","campaigns":[{"id":812,"type":"ron"}],"settings":{"countries":["us"],"visible_metrics":["views","clicks","ctr","spent"],"follow_groups":true},"tagids":"5"}; // \GoAdServerApi\Model\SharedReportBody

try {
    $apiInstance->apiV1SharedReportsPost($shared_report_body);
} catch (Exception $e) {
    echo 'Exception when calling SharedReportsApi->apiV1SharedReportsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **shared_report_body** | [**\GoAdServerApi\Model\SharedReportBody**](../Model/SharedReportBody.md)|  | |

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

## `apiV1SharedReportsSetupDataGet()`

```php
apiV1SharedReportsSetupDataGet()
```

Campaigns and sites a report can cover.

The account's live campaigns (`{id, name, type}`) and sites, the panel host the links are built on, and whether `options` (stats adjustment) is honoured for this account.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SharedReportsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1SharedReportsSetupDataGet();
} catch (Exception $e) {
    echo 'Exception when calling SharedReportsApi->apiV1SharedReportsSetupDataGet: ', $e->getMessage(), PHP_EOL;
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
