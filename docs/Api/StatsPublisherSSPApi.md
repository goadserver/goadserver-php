# GoAdServerApi\StatsPublisherSSPApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1PublisherSspStatsDetailsGet()**](StatsPublisherSSPApi.md#apiV1PublisherSspStatsDetailsGet) | **GET** /api/v1/publisher/ssp/stats/details | SSP report broken down by one dimension. |
| [**apiV1PublisherSspStatsFiltersGet()**](StatsPublisherSSPApi.md#apiV1PublisherSspStatsFiltersGet) | **GET** /api/v1/publisher/ssp/stats/filters | Filter values for the SSP reports. |
| [**apiV1PublisherSspStatsFlatGet()**](StatsPublisherSSPApi.md#apiV1PublisherSspStatsFlatGet) | **GET** /api/v1/publisher/ssp/stats/flat | SSP report grouped by several dimensions. |
| [**apiV1PublisherSspStatsGet()**](StatsPublisherSSPApi.md#apiV1PublisherSspStatsGet) | **GET** /api/v1/publisher/ssp/stats | SSP / smart-campaign earnings. |


## `apiV1PublisherSspStatsDetailsGet()`

```php
apiV1PublisherSspStatsDetailsGet($from, $to, $type, $ssptype, $campaignid, $networkid, $siteid, $country, $device_type, $ad_type, $subid, $filter_bid_type)
```

SSP report broken down by one dimension.

The panel's SSP \"details\" console: one row per value of `type` (`campaign`, `country`, `website`, `adzone`, `network`, `bid_type`, `keyword`), with request/bid/win funnel, errors and timings. Defaults to every SSP campaign of yours (narrow with `ssptype` or `campaignid`). Capped rows report `truncated: true` + `row_cap`. **Money is in currency units (not cents)**: `paid`, `ecpm`, `avg_bidfloor`, `avg_cpm` (CPM) and `nw_earned`. `avg_resp_time` is seconds. For campaign/country rows, `nw_views` / `nw_clicks` / `nw_earned` carry what the partner's own API reported.

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
$from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inclusive start. Defaults to 6 days ago (send both from and to).
$to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime
$type = 'campaign'; // string
$ssptype = 'ssptype_example'; // string
$campaignid = array(56); // int[] | One or more SSP campaign ids (`campaignid=1&campaignid=2` or `campaignid[]=…`).
$networkid = array(56); // int[]
$siteid = array(56); // int[]
$country = array('country_example'); // string[] | ISO-3166 alpha-2 codes.
$device_type = array(56); // int[]
$ad_type = array(56); // int[]
$subid = 'subid_example'; // string
$filter_bid_type = 56; // int | Only this bid type.

try {
    $apiInstance->apiV1PublisherSspStatsDetailsGet($from, $to, $type, $ssptype, $campaignid, $networkid, $siteid, $country, $device_type, $ad_type, $subid, $filter_bid_type);
} catch (Exception $e) {
    echo 'Exception when calling StatsPublisherSSPApi->apiV1PublisherSspStatsDetailsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **from** | **\DateTime**| Inclusive start. Defaults to 6 days ago (send both from and to). | [optional] |
| **to** | **\DateTime**|  | [optional] |
| **type** | **string**|  | [optional] [default to &#39;campaign&#39;] |
| **ssptype** | **string**|  | [optional] |
| **campaignid** | [**int[]**](../Model/int.md)| One or more SSP campaign ids (&#x60;campaignid&#x3D;1&amp;campaignid&#x3D;2&#x60; or &#x60;campaignid[]&#x3D;…&#x60;). | [optional] |
| **networkid** | [**int[]**](../Model/int.md)|  | [optional] |
| **siteid** | [**int[]**](../Model/int.md)|  | [optional] |
| **country** | [**string[]**](../Model/string.md)| ISO-3166 alpha-2 codes. | [optional] |
| **device_type** | [**int[]**](../Model/int.md)|  | [optional] |
| **ad_type** | [**int[]**](../Model/int.md)|  | [optional] |
| **subid** | **string**|  | [optional] |
| **filter_bid_type** | **int**| Only this bid type. | [optional] |

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

## `apiV1PublisherSspStatsFiltersGet()`

```php
apiV1PublisherSspStatsFiltersGet()
```

Filter values for the SSP reports.

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

try {
    $apiInstance->apiV1PublisherSspStatsFiltersGet();
} catch (Exception $e) {
    echo 'Exception when calling StatsPublisherSSPApi->apiV1PublisherSspStatsFiltersGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1PublisherSspStatsFlatGet()`

```php
apiV1PublisherSspStatsFlatGet($from, $to, $group, $viewtype, $ssptype, $campaignid, $networkid, $siteid, $country, $device_type, $ad_type, $subid, $filter_bid_type)
```

SSP report grouped by several dimensions.

The panel's flat SSP table. `group` is a comma-separated list of `date`, `campaign`, `country`, `website`, `adzone`, `subid`, `domainid`, `bid_type`, `bid_advid`, `bid_cid`, `bid_adid`, `mxispid` (default `date`); each row carries the grouped values plus `<dim>_name` where a name exists, and `startdate`/`enddate` for the row's period. `viewtype` sets the date bucket. At most 10,000 rows. Same filters and money units as `stats/details` (**currency units, not cents**). `totals` sums the whole result.

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
$from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime
$to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime
$group = date,campaign; // string
$viewtype = 'day'; // string
$ssptype = 'ssptype_example'; // string
$campaignid = array(56); // int[]
$networkid = array(56); // int[]
$siteid = array(56); // int[]
$country = array('country_example'); // string[]
$device_type = array(56); // int[]
$ad_type = array(56); // int[]
$subid = 'subid_example'; // string
$filter_bid_type = 56; // int

try {
    $apiInstance->apiV1PublisherSspStatsFlatGet($from, $to, $group, $viewtype, $ssptype, $campaignid, $networkid, $siteid, $country, $device_type, $ad_type, $subid, $filter_bid_type);
} catch (Exception $e) {
    echo 'Exception when calling StatsPublisherSSPApi->apiV1PublisherSspStatsFlatGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **from** | **\DateTime**|  | [optional] |
| **to** | **\DateTime**|  | [optional] |
| **group** | **string**|  | [optional] [default to &#39;date&#39;] |
| **viewtype** | **string**|  | [optional] [default to &#39;day&#39;] |
| **ssptype** | **string**|  | [optional] |
| **campaignid** | [**int[]**](../Model/int.md)|  | [optional] |
| **networkid** | [**int[]**](../Model/int.md)|  | [optional] |
| **siteid** | [**int[]**](../Model/int.md)|  | [optional] |
| **country** | [**string[]**](../Model/string.md)|  | [optional] |
| **device_type** | [**int[]**](../Model/int.md)|  | [optional] |
| **ad_type** | [**int[]**](../Model/int.md)|  | [optional] |
| **subid** | **string**|  | [optional] |
| **filter_bid_type** | **int**|  | [optional] |

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

## `apiV1PublisherSspStatsGet()`

```php
apiV1PublisherSspStatsGet($from, $to, $interval, $group_by, $metrics, $filter_feed_id, $filter_campaign_id, $filter_ssptype, $filter_country, $filter_device, $filter_ad_type, $filter_network_id, $filter_adzone_id, $filter_website, $format, $page, $per_page, $sort): \GoAdServerApi\Model\StatsResponse
```

SSP / smart-campaign earnings.

SSP / smart-campaign earnings.  **Scoping (priority order):** 1. `filter[feed_id]` — the SSP feed hash from the campaign row    (the public identifier you give to ad networks). Limits results    to one campaign without exposing the internal numeric ID. 2. `filter[campaign_id]` — internal numeric ID. 3. (default) every SSP campaign owned by the caller, optionally    narrowed with `filter[ssptype]`.  **Authentication — this endpoint accepts two credentials.** Either an account API key with the `stats.read` scope in the `Authorization` header, as everywhere else on this API, or the feed hash itself in `filter[feed_id]` (`token` is accepted as an alias, matching the legacy `/apitool/ssp_stats/` URL). The feed hash authenticates nothing but this report for that one feed, which is what makes the URL safe to hand to an ad network; an account key covers every feed on the account. When an `Authorization` header is present it is always used, so an invalid key is reported as such rather than falling back to the feed hash.

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
$group_by = ["campaign","country"]; // string[] | Dimensions to aggregate by. Multi-pick up to 8.
$metrics = array('metrics_example'); // string[] | Metrics to return. Multi-pick — leave empty for all.
$filter_feed_id = 'filter_feed_id_example'; // string | SSP campaign feed hash (the public identifier given to ad networks). Pins results to one SSP campaign without exposing the internal numeric ID.
$filter_campaign_id = 56; // int | Campaign ID. Look up via `GET /api/v1/campaigns`.
$filter_ssptype = 'filter_ssptype_example'; // string | SSP feed type filter.
$filter_country = US; // string | ISO-3166 alpha-2 country code (e.g. `US`, `DE`, `BR`).
$filter_device = 56; // int | Device type:  | ID | Device | |----|--------| | 1  | Computer | | 2  | Tablet | | 3  | Phone | | 4  | Console | | 5  | Wearable | | 6  | TV |
$filter_ad_type = 56; // int | Ad-type ID. Available types are tenant-specific (banner, popunder, native, video, push, …); the dropdown shows the types active on your system.
$filter_network_id = 56; // int | RTB network ID.
$filter_adzone_id = 56; // int | One adspace id — look up via `GET /api/v1/adspaces`.
$filter_website = 56; // int | Site ID — look up via `GET /api/v1/sites`.
$format = 'json'; // string
$page = 1; // int
$per_page = 100; // int
$sort = 'sort_example'; // string | Field name. Prefix with `-` for descending (e.g. `-views`).

try {
    $result = $apiInstance->apiV1PublisherSspStatsGet($from, $to, $interval, $group_by, $metrics, $filter_feed_id, $filter_campaign_id, $filter_ssptype, $filter_country, $filter_device, $filter_ad_type, $filter_network_id, $filter_adzone_id, $filter_website, $format, $page, $per_page, $sort);
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
| **group_by** | [**string[]**](../Model/string.md)| Dimensions to aggregate by. Multi-pick up to 8. | [optional] |
| **metrics** | [**string[]**](../Model/string.md)| Metrics to return. Multi-pick — leave empty for all. | [optional] |
| **filter_feed_id** | **string**| SSP campaign feed hash (the public identifier given to ad networks). Pins results to one SSP campaign without exposing the internal numeric ID. | [optional] |
| **filter_campaign_id** | **int**| Campaign ID. Look up via &#x60;GET /api/v1/campaigns&#x60;. | [optional] |
| **filter_ssptype** | **string**| SSP feed type filter. | [optional] |
| **filter_country** | **string**| ISO-3166 alpha-2 country code (e.g. &#x60;US&#x60;, &#x60;DE&#x60;, &#x60;BR&#x60;). | [optional] |
| **filter_device** | **int**| Device type:  | ID | Device | |----|--------| | 1  | Computer | | 2  | Tablet | | 3  | Phone | | 4  | Console | | 5  | Wearable | | 6  | TV | | [optional] |
| **filter_ad_type** | **int**| Ad-type ID. Available types are tenant-specific (banner, popunder, native, video, push, …); the dropdown shows the types active on your system. | [optional] |
| **filter_network_id** | **int**| RTB network ID. | [optional] |
| **filter_adzone_id** | **int**| One adspace id — look up via &#x60;GET /api/v1/adspaces&#x60;. | [optional] |
| **filter_website** | **int**| Site ID — look up via &#x60;GET /api/v1/sites&#x60;. | [optional] |
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
