# GoAdServerApi\EventLogApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1EventLogExportsGet()**](EventLogApi.md#apiV1EventLogExportsGet) | **GET** /api/v1/event-log/exports | Your event log exports, newest first. |
| [**apiV1EventLogExportsIdDownloadGet()**](EventLogApi.md#apiV1EventLogExportsIdDownloadGet) | **GET** /api/v1/event-log/exports/{id}/download | Download a finished export (.csv.gz). |
| [**apiV1EventLogExportsPost()**](EventLogApi.md#apiV1EventLogExportsPost) | **POST** /api/v1/event-log/exports | Queue a CSV export of your events. |
| [**apiV1EventLogGet()**](EventLogApi.md#apiV1EventLogGet) | **GET** /api/v1/event-log | Your raw events — impressions and clicks with IP and user agent (90 days). |


## `apiV1EventLogExportsGet()`

```php
apiV1EventLogExportsGet()
```

Your event log exports, newest first.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\EventLogApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1EventLogExportsGet();
} catch (Exception $e) {
    echo 'Exception when calling EventLogApi->apiV1EventLogExportsGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1EventLogExportsIdDownloadGet()`

```php
apiV1EventLogExportsIdDownloadGet($id)
```

Download a finished export (.csv.gz).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\EventLogApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1EventLogExportsIdDownloadGet($id);
} catch (Exception $e) {
    echo 'Exception when calling EventLogApi->apiV1EventLogExportsIdDownloadGet: ', $e->getMessage(), PHP_EOL;
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
- **Accept**: `application/gzip`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1EventLogExportsPost()`

```php
apiV1EventLogExportsPost($api_v1_event_log_exports_post_request)
```

Queue a CSV export of your events.

Queues a CSV export; it is built in the background, one export at a time, and the 202 answer is the export row (`status: queued`). Follow it on the exports list; the file is a gzip-compressed CSV with the same columns as the preview, at most 31 days and 20,000,000 rows (`truncated: true` when the cap was hit), kept 7 days. At most 3 exports may wait per requester (429 `too_many_exports`). You are notified (in-app / email, per your notification settings) when it is ready.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\EventLogApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_event_log_exports_post_request = {"date_from":"2026-10-22","date_to":"2026-10-28","ad_id":3891355,"event":"click"}; // \GoAdServerApi\Model\ApiV1EventLogExportsPostRequest

try {
    $apiInstance->apiV1EventLogExportsPost($api_v1_event_log_exports_post_request);
} catch (Exception $e) {
    echo 'Exception when calling EventLogApi->apiV1EventLogExportsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_event_log_exports_post_request** | [**\GoAdServerApi\Model\ApiV1EventLogExportsPostRequest**](../Model/ApiV1EventLogExportsPostRequest.md)|  | |

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

## `apiV1EventLogGet()`

```php
apiV1EventLogGet($date_from, $date_to, $side, $campaign_id, $ad_id, $site_id, $zone_id, $event, $country, $ip, $page, $per_page)
```

Your raw events — impressions and clicks with IP and user agent (90 days).

Only for accounts the network enabled the event log for (user right `raw_event_log`, off by default; 403 otherwise). Not available on ad networks, where the event log is for the network's administrators only (403). Every event where you are the advertiser or the publisher, kept 90 days from the day the right was switched on: `time` (network timezone), `time_utc`, `event`, the campaign / ad / publisher / site / zone ids, `ip`, `user_agent`, `country` and device / os / browser / isp ids. No money columns. Newest first, 100 rows a page at most; `total` is the full match count. For more than a screenful, queue an export. Same handler as the panel's Event Log page.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\EventLogApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$date_from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | First day (YYYY-MM-DD, network timezone). Default date_to. Events are kept 90 days; older dates are a 422.
$date_to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Last day. Default today.
$side = 'side_example'; // string | Only events where the account is the advertiser, or the publisher. Omit for both.
$campaign_id = 56; // int
$ad_id = 56; // int
$site_id = 56; // int
$zone_id = 56; // int
$event = 'event_example'; // string
$country = NL; // string | Two-letter country code.
$ip = 'ip_example'; // string | Exact visitor IP.
$page = 1; // int
$per_page = 50; // int

try {
    $apiInstance->apiV1EventLogGet($date_from, $date_to, $side, $campaign_id, $ad_id, $site_id, $zone_id, $event, $country, $ip, $page, $per_page);
} catch (Exception $e) {
    echo 'Exception when calling EventLogApi->apiV1EventLogGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **date_from** | **\DateTime**| First day (YYYY-MM-DD, network timezone). Default date_to. Events are kept 90 days; older dates are a 422. | [optional] |
| **date_to** | **\DateTime**| Last day. Default today. | [optional] |
| **side** | **string**| Only events where the account is the advertiser, or the publisher. Omit for both. | [optional] |
| **campaign_id** | **int**|  | [optional] |
| **ad_id** | **int**|  | [optional] |
| **site_id** | **int**|  | [optional] |
| **zone_id** | **int**|  | [optional] |
| **event** | **string**|  | [optional] |
| **country** | **string**| Two-letter country code. | [optional] |
| **ip** | **string**| Exact visitor IP. | [optional] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 50] |

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
