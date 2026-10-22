# GoAdServerApi\StatsPublisherSmartZonesApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1PublisherSmartzonesStatsFiltersGet()**](StatsPublisherSmartZonesApi.md#apiV1PublisherSmartzonesStatsFiltersGet) | **GET** /api/v1/publisher/smartzones/stats/filters | Filter values for smart-zone stats. |
| [**apiV1PublisherSmartzonesStatsGet()**](StatsPublisherSmartZonesApi.md#apiV1PublisherSmartzonesStatsGet) | **GET** /api/v1/publisher/smartzones/stats | SmartZone (waterfall) earnings. |


## `apiV1PublisherSmartzonesStatsFiltersGet()`

```php
apiV1PublisherSmartzonesStatsFiltersGet()
```

Filter values for smart-zone stats.

Your websites, ad zones and smart network accounts, as `{id, name}` lists for the `filter[…]` parameters of `GET /api/v1/publisher/smartzones/stats`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\StatsPublisherSmartZonesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1PublisherSmartzonesStatsFiltersGet();
} catch (Exception $e) {
    echo 'Exception when calling StatsPublisherSmartZonesApi->apiV1PublisherSmartzonesStatsFiltersGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1PublisherSmartzonesStatsGet()`

```php
apiV1PublisherSmartzonesStatsGet($from, $to, $interval, $group_by, $metrics, $filter_website, $filter_adzone_id, $filter_ad_type, $filter_device, $filter_country, $filter_smart_network_id, $format, $page, $per_page, $sort): \GoAdServerApi\Model\StatsResponse
```

SmartZone (waterfall) earnings.

SmartZone (waterfall) earnings.  SmartZone earnings are recorded per day, so `interval=hour` is not supported on this endpoint and returns a validation error. The other intervals (`day`, `week`, `month`, `sum`) all apply.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\StatsPublisherSmartZonesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | ISO date — inclusive start. Defaults to 7 days ago.
$to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | ISO date — inclusive end. Defaults to today. Max range 92 days.
$interval = 'sum'; // string
$group_by = ["website","adzone"]; // string[] | Dimensions to aggregate by. Multi-pick up to 8.
$metrics = array('metrics_example'); // string[] | Metrics to return. Multi-pick — leave empty for all.
$filter_website = 56; // int | Site ID — look up via `GET /api/v1/sites`.
$filter_adzone_id = 12345,12346; // string | Adspace ID, or comma-separated list. Look up via `GET /api/v1/adspaces`.
$filter_ad_type = 56; // int | Ad-type ID. Available types are tenant-specific (banner, popunder, native, video, push, …); the dropdown shows the types active on your system.
$filter_device = 56; // int | Device type:  | ID | Device | |----|--------| | 1  | Computer | | 2  | Tablet | | 3  | Phone | | 4  | Console | | 5  | Wearable | | 6  | TV |
$filter_country = US; // string | ISO-3166 alpha-2 country code (e.g. `US`, `DE`, `BR`).
$filter_smart_network_id = 56; // int | SmartZone network ID.
$format = 'json'; // string
$page = 1; // int
$per_page = 100; // int
$sort = 'sort_example'; // string | Field name. Prefix with `-` for descending (e.g. `-views`).

try {
    $result = $apiInstance->apiV1PublisherSmartzonesStatsGet($from, $to, $interval, $group_by, $metrics, $filter_website, $filter_adzone_id, $filter_ad_type, $filter_device, $filter_country, $filter_smart_network_id, $format, $page, $per_page, $sort);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling StatsPublisherSmartZonesApi->apiV1PublisherSmartzonesStatsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **from** | **\DateTime**| ISO date — inclusive start. Defaults to 7 days ago. | [optional] |
| **to** | **\DateTime**| ISO date — inclusive end. Defaults to today. Max range 92 days. | [optional] |
| **interval** | **string**|  | [optional] [default to &#39;sum&#39;] |
| **group_by** | [**string[]**](../Model/string.md)| Dimensions to aggregate by. Multi-pick up to 8. | [optional] |
| **metrics** | [**string[]**](../Model/string.md)| Metrics to return. Multi-pick — leave empty for all. | [optional] |
| **filter_website** | **int**| Site ID — look up via &#x60;GET /api/v1/sites&#x60;. | [optional] |
| **filter_adzone_id** | **string**| Adspace ID, or comma-separated list. Look up via &#x60;GET /api/v1/adspaces&#x60;. | [optional] |
| **filter_ad_type** | **int**| Ad-type ID. Available types are tenant-specific (banner, popunder, native, video, push, …); the dropdown shows the types active on your system. | [optional] |
| **filter_device** | **int**| Device type:  | ID | Device | |----|--------| | 1  | Computer | | 2  | Tablet | | 3  | Phone | | 4  | Console | | 5  | Wearable | | 6  | TV | | [optional] |
| **filter_country** | **string**| ISO-3166 alpha-2 country code (e.g. &#x60;US&#x60;, &#x60;DE&#x60;, &#x60;BR&#x60;). | [optional] |
| **filter_smart_network_id** | **int**| SmartZone network ID. | [optional] |
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
