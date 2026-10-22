# GoAdServerApi\TrackerReportsApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1TrackerCostSummaryGet()**](TrackerReportsApi.md#apiV1TrackerCostSummaryGet) | **GET** /api/v1/tracker/cost-summary | Spend against revenue. |
| [**apiV1TrackerDailyStatsGet()**](TrackerReportsApi.md#apiV1TrackerDailyStatsGet) | **GET** /api/v1/tracker/daily-stats | Daily performance, filtered. |
| [**apiV1TrackerInlineStatsGet()**](TrackerReportsApi.md#apiV1TrackerInlineStatsGet) | **GET** /api/v1/tracker/inline-stats | Per-row performance for a listing. |
| [**apiV1TrackerPlayerLtvGet()**](TrackerReportsApi.md#apiV1TrackerPlayerLtvGet) | **GET** /api/v1/tracker/player-ltv | Lifetime value by cohort. |
| [**apiV1TrackerPostbacksStatsGet()**](TrackerReportsApi.md#apiV1TrackerPostbacksStatsGet) | **GET** /api/v1/tracker/postbacks/stats | Outbound postback firing log. |
| [**apiV1TrackerRecentClicksGet()**](TrackerReportsApi.md#apiV1TrackerRecentClicksGet) | **GET** /api/v1/tracker/recent-clicks | The most recent clicks. |


## `apiV1TrackerCostSummaryGet()`

```php
apiV1TrackerCostSummaryGet($from, $to): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Spend against revenue.

Imported ad-account spend joined to tracker revenue — the ROI view. Only as complete as the last cost pull; see /source-accounts for sync state.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerReportsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$from = 'from_example'; // string | Start date, YYYY-MM-DD.
$to = 'to_example'; // string | End date, YYYY-MM-DD.

try {
    $result = $apiInstance->apiV1TrackerCostSummaryGet($from, $to);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerReportsApi->apiV1TrackerCostSummaryGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **from** | **string**| Start date, YYYY-MM-DD. | [optional] |
| **to** | **string**| End date, YYYY-MM-DD. | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerDailyStatsGet()`

```php
apiV1TrackerDailyStatsGet($from, $to, $offer_ids, $link_ids, $source_ids, $countries, $device_ids): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Daily performance, filtered.

Clicks, conversions, revenue and cost per day for the caller's tracker traffic. Every filter is repeatable and ANDs with the rest.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerReportsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$from = 'from_example'; // string | Start date, YYYY-MM-DD.
$to = 'to_example'; // string | End date, YYYY-MM-DD.
$offer_ids = 'offer_ids_example'; // string | Comma-separated offer ids.
$link_ids = 'link_ids_example'; // string | Comma-separated tracking-link ids.
$source_ids = 'source_ids_example'; // string | Comma-separated source ids.
$countries = 'countries_example'; // string | Comma-separated country codes.
$device_ids = 'device_ids_example'; // string | Comma-separated device-type ids.

try {
    $result = $apiInstance->apiV1TrackerDailyStatsGet($from, $to, $offer_ids, $link_ids, $source_ids, $countries, $device_ids);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerReportsApi->apiV1TrackerDailyStatsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **from** | **string**| Start date, YYYY-MM-DD. | [optional] |
| **to** | **string**| End date, YYYY-MM-DD. | [optional] |
| **offer_ids** | **string**| Comma-separated offer ids. | [optional] |
| **link_ids** | **string**| Comma-separated tracking-link ids. | [optional] |
| **source_ids** | **string**| Comma-separated source ids. | [optional] |
| **countries** | **string**| Comma-separated country codes. | [optional] |
| **device_ids** | **string**| Comma-separated device-type ids. | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerInlineStatsGet()`

```php
apiV1TrackerInlineStatsGet($from, $to, $type): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Per-row performance for a listing.

The numbers the offer and tracking-link listings show inline, so an integration can render the same table without a stats query per row.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerReportsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$from = 'from_example'; // string | Start date, YYYY-MM-DD.
$to = 'to_example'; // string | End date, YYYY-MM-DD.
$type = 'type_example'; // string | Which listing to size.

try {
    $result = $apiInstance->apiV1TrackerInlineStatsGet($from, $to, $type);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerReportsApi->apiV1TrackerInlineStatsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **from** | **string**| Start date, YYYY-MM-DD. | [optional] |
| **to** | **string**| End date, YYYY-MM-DD. | [optional] |
| **type** | **string**| Which listing to size. | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerPlayerLtvGet()`

```php
apiV1TrackerPlayerLtvGet($days): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Lifetime value by cohort.

Revenue per acquired user over time, for offers that keep paying after the first conversion.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerReportsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$days = 30; // int | Cohort window in days.

try {
    $result = $apiInstance->apiV1TrackerPlayerLtvGet($days);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerReportsApi->apiV1TrackerPlayerLtvGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **days** | **int**| Cohort window in days. | [optional] [default to 30] |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerPostbacksStatsGet()`

```php
apiV1TrackerPostbacksStatsGet($datestart, $dateend, $siteid, $conv_type): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Outbound postback firing log.

Every postback the tracker sent upstream and how the receiver answered — the evidence when a network says it never heard from you.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerReportsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$datestart = 'datestart_example'; // string | Start date, YYYY-MM-DD.
$dateend = 'dateend_example'; // string | End date, YYYY-MM-DD.
$siteid = 56; // int | Narrow to one traffic source.
$conv_type = 'conv_type_example'; // string | Narrow to one conversion-type letter.

try {
    $result = $apiInstance->apiV1TrackerPostbacksStatsGet($datestart, $dateend, $siteid, $conv_type);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerReportsApi->apiV1TrackerPostbacksStatsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **datestart** | **string**| Start date, YYYY-MM-DD. | [optional] |
| **dateend** | **string**| End date, YYYY-MM-DD. | [optional] |
| **siteid** | **int**| Narrow to one traffic source. | [optional] |
| **conv_type** | **string**| Narrow to one conversion-type letter. | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerRecentClicksGet()`

```php
apiV1TrackerRecentClicksGet($limit): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

The most recent clicks.

A live tail of the click stream with the macro values each click carried — what you read while checking that a new link fires correctly.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerReportsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$limit = 100; // int | Rows to return.

try {
    $result = $apiInstance->apiV1TrackerRecentClicksGet($limit);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerReportsApi->apiV1TrackerRecentClicksGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **limit** | **int**| Rows to return. | [optional] [default to 100] |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
