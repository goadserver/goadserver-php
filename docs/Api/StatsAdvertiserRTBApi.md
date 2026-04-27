# GoAdServerApi\StatsAdvertiserRTBApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1AdvertiserRtbStatsGet()**](StatsAdvertiserRTBApi.md#apiV1AdvertiserRtbStatsGet) | **GET** /api/v1/advertiser/rtb/stats | DSP / RTB-side stats. |


## `apiV1AdvertiserRtbStatsGet()`

```php
apiV1AdvertiserRtbStatsGet($from, $to, $interval, $group_by, $metrics, $format, $page, $per_page, $sort): \GoAdServerApi\Model\StatsResponse
```

DSP / RTB-side stats.

**group_by:** `campaign`, `country`, `isp`, `domain`, `spaceid`.  **Metrics:** `requests`, `bids`, `wins`, `noresult`, `errors`, `views`, `clicks`, `ctr`, `wpr`, `conversions`, `paid`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\StatsAdvertiserRTBApi(
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
    $result = $apiInstance->apiV1AdvertiserRtbStatsGet($from, $to, $interval, $group_by, $metrics, $format, $page, $per_page, $sort);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling StatsAdvertiserRTBApi->apiV1AdvertiserRtbStatsGet: ', $e->getMessage(), PHP_EOL;
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
