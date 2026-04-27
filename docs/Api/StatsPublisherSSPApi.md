# GoAdServerApi\StatsPublisherSSPApi



All URIs are relative to https://up.goadserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1PublisherSspStatsGet()**](StatsPublisherSSPApi.md#apiV1PublisherSspStatsGet) | **GET** /api/v1/publisher/ssp/stats | SSP / smart-campaign earnings. |


## `apiV1PublisherSspStatsGet()`

```php
apiV1PublisherSspStatsGet($from, $to, $interval, $group_by, $metrics, $format, $page, $per_page, $sort): \GoAdServerApi\Model\StatsResponse
```

SSP / smart-campaign earnings.

**group_by:** `campaign`, `country`, `website`, `adzone`, `network`.  **Metrics:** `requests`, `wins`, `views`, `clicks`, `ctr`, `ecpm`, `earned`, `errors`.  **Scoping (priority order):** - `filter[feed_id]=<hash>` — the SSP feed hash from the campaign row   (the same identifier you give to ad networks). Limits results to   one campaign without needing the internal numeric ID. - `filter[campaign_id]=<id>` — internal numeric ID. - (default) every SSP campaign owned by the caller, optionally   narrowed with `filter[ssptype]`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\StatsPublisherSSPApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | ISO date — inclusive start. Defaults to 7 days ago.
$to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | ISO date — inclusive end. Defaults to today. Max range 92 days.
$interval = 'sum'; // string
$group_by = 'group_by_example'; // string | Comma-separated dimensions to aggregate by, in priority order. Max 4 dimensions. Available dimensions vary per endpoint — see the per-endpoint description.
$metrics = 'metrics_example'; // string | Optional comma-separated metric whitelist. When omitted, all metrics for the endpoint are returned.
$format = 'json'; // string
$page = 1; // int
$per_page = 100; // int
$sort = 'sort_example'; // string | Field name. Prefix with `-` for descending (e.g. `-views`).

try {
    $result = $apiInstance->apiV1PublisherSspStatsGet($from, $to, $interval, $group_by, $metrics, $format, $page, $per_page, $sort);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling StatsPublisherSSPApi->apiV1PublisherSspStatsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **from** | **\DateTime**| ISO date — inclusive start. Defaults to 7 days ago. | [optional] |
| **to** | **\DateTime**| ISO date — inclusive end. Defaults to today. Max range 92 days. | [optional] |
| **interval** | **string**|  | [optional] [default to &#39;sum&#39;] |
| **group_by** | **string**| Comma-separated dimensions to aggregate by, in priority order. Max 4 dimensions. Available dimensions vary per endpoint — see the per-endpoint description. | [optional] |
| **metrics** | **string**| Optional comma-separated metric whitelist. When omitted, all metrics for the endpoint are returned. | [optional] |
| **format** | **string**|  | [optional] [default to &#39;json&#39;] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 100] |
| **sort** | **string**| Field name. Prefix with &#x60;-&#x60; for descending (e.g. &#x60;-views&#x60;). | [optional] |

### Return type

[**\GoAdServerApi\Model\StatsResponse**](../Model/StatsResponse.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
