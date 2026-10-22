# GoAdServerApi\StatsAdvertiserRTBApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1AdvertiserRtbStatsGet()**](StatsAdvertiserRTBApi.md#apiV1AdvertiserRtbStatsGet) | **GET** /api/v1/advertiser/rtb/stats | DSP / RTB-side stats. |


## `apiV1AdvertiserRtbStatsGet()`

```php
apiV1AdvertiserRtbStatsGet($from, $to, $interval, $group_by, $metrics, $filter_campaign_id, $filter_network_id, $filter_ad_type, $filter_device, $filter_country, $filter_spaceid, $filter_isp_id, $filter_domain_id, $filter_site_id, $filter_ad_id, $filter_os, $filter_browser, $filter_conn_type, $format, $page, $per_page, $sort, $strict): \GoAdServerApi\Model\StatsResponse
```

DSP / RTB-side stats.

Bid-stream stats for the advertiser's RTB campaigns — the panel's RTB Stats report (`dspstats_stats` / `dspstats_stats_sum`), same sums and formulas.  **Money unit:** `paid` is in the account currency's main unit (cents ÷ 100), rounded to 4 decimals. `avg_bidfloor` is the average floor per request in main units — a CPM for impression-billed ad types, a per-click price for click-billed ones. `ctr`, `wpr`, `win_pct` and `bid_rate` are percentages.  **Dates:** `from` and `to` are used only when both are given; otherwise the last 7 days. Max range 92 days. `interval=hour` buckets on the bid timestamp (`YYYY-MM-DD HH:00`).  Not on this endpoint (panel-only): the per-error-code breakdown and the merge of externally pulled network counters (`nw_views`/`nw_clicks`/`nw_earned`).

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
$group_by = ["campaign","country"]; // string[] | Dimensions to aggregate by. Multi-pick up to 8; unknown → 422. Each comes back as `{id, name}` (`country`: `{id: \"US\", name}`); `ad` and `site` carry the id as name. Without the `stats_source_names` right `name` equals the id.  | Dim | Meaning | |---|---| | `campaign` | RTB campaign | | `network` | RTB network (SSP) the request came from | | `ad_type` | ad type | | `device` | device type | | `country` | country | | `isp` | ISP / ASN | | `domain` | domain | | `spaceid` | publisher adzone id | | `site` | publisher site id | | `os` | operating system | | `browser` | browser | | `conn_type` | connection type | | `ad` | ad id |
$metrics = array('metrics_example'); // string[] | Metrics to return (comma-separated). Empty → the default set `requests, bids, wins, noresult, errors, views, clicks, ctr, wpr, paid`; `all` → every metric. Opt-in: `timeouts`, `skipnobids`, `skiptimeouts`, `win_pct` (wins / bids × 100), `bid_rate` (bids / requests × 100), `avg_bidfloor`, `avg_time` (average response time per request). `wpr` is wins / requests × 100. An unknown name is ignored and echoed in `ignored_metrics`; with `strict=1` it is a 422.
$filter_campaign_id = 'filter_campaign_id_example'; // string | RTB campaign ID, or a comma-separated list.
$filter_network_id = 56; // int | RTB network ID.
$filter_ad_type = 56; // int | Ad-type ID. Available types are tenant-specific (banner, popunder, native, video, push, …); the dropdown shows the types active on your system.
$filter_device = 56; // int | Device type:  | ID | Device | |----|--------| | 1  | Computer | | 2  | Tablet | | 3  | Phone | | 4  | Console | | 5  | Wearable | | 6  | TV |
$filter_country = US; // string | ISO-3166 alpha-2 country code (e.g. `US`, `DE`, `BR`).
$filter_spaceid = 56; // int | Adspace ID.
$filter_isp_id = 'filter_isp_id_example'; // string | ISP / ASN id, or a comma-separated list.
$filter_domain_id = 'filter_domain_id_example'; // string | Domain id, or a comma-separated list.
$filter_site_id = 'filter_site_id_example'; // string | Publisher site id, or a comma-separated list.
$filter_ad_id = 'filter_ad_id_example'; // string | Ad id, or a comma-separated list.
$filter_os = 'filter_os_example'; // string | Operating-system id (`GET /api/v1/lookups/operating-systems`), or a comma-separated list.
$filter_browser = 'filter_browser_example'; // string | Browser id (`GET /api/v1/lookups/browsers`), or a comma-separated list.
$filter_conn_type = 'filter_conn_type_example'; // string | Connection-type id, or a comma-separated list.
$format = 'json'; // string
$page = 1; // int
$per_page = 100; // int
$sort = '-paid'; // string | Sort key, `-` prefix for descending: `requests`, `bids`, `wins`, `noresult`, `errors`, `views`, `clicks`, `paid`, `timeouts`, `skipnobids`, `skiptimeouts`, `ctr`, `wpr`, `win_pct`, `bid_rate`, `date` (non-`sum` interval only). Any other value falls back to `-paid`.
$strict = 'strict_example'; // string | `1` turns an unknown metric name into a 422 instead of ignoring it.

try {
    $result = $apiInstance->apiV1AdvertiserRtbStatsGet($from, $to, $interval, $group_by, $metrics, $filter_campaign_id, $filter_network_id, $filter_ad_type, $filter_device, $filter_country, $filter_spaceid, $filter_isp_id, $filter_domain_id, $filter_site_id, $filter_ad_id, $filter_os, $filter_browser, $filter_conn_type, $format, $page, $per_page, $sort, $strict);
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
| **group_by** | [**string[]**](../Model/string.md)| Dimensions to aggregate by. Multi-pick up to 8; unknown → 422. Each comes back as &#x60;{id, name}&#x60; (&#x60;country&#x60;: &#x60;{id: \&quot;US\&quot;, name}&#x60;); &#x60;ad&#x60; and &#x60;site&#x60; carry the id as name. Without the &#x60;stats_source_names&#x60; right &#x60;name&#x60; equals the id.  | Dim | Meaning | |---|---| | &#x60;campaign&#x60; | RTB campaign | | &#x60;network&#x60; | RTB network (SSP) the request came from | | &#x60;ad_type&#x60; | ad type | | &#x60;device&#x60; | device type | | &#x60;country&#x60; | country | | &#x60;isp&#x60; | ISP / ASN | | &#x60;domain&#x60; | domain | | &#x60;spaceid&#x60; | publisher adzone id | | &#x60;site&#x60; | publisher site id | | &#x60;os&#x60; | operating system | | &#x60;browser&#x60; | browser | | &#x60;conn_type&#x60; | connection type | | &#x60;ad&#x60; | ad id | | [optional] |
| **metrics** | [**string[]**](../Model/string.md)| Metrics to return (comma-separated). Empty → the default set &#x60;requests, bids, wins, noresult, errors, views, clicks, ctr, wpr, paid&#x60;; &#x60;all&#x60; → every metric. Opt-in: &#x60;timeouts&#x60;, &#x60;skipnobids&#x60;, &#x60;skiptimeouts&#x60;, &#x60;win_pct&#x60; (wins / bids × 100), &#x60;bid_rate&#x60; (bids / requests × 100), &#x60;avg_bidfloor&#x60;, &#x60;avg_time&#x60; (average response time per request). &#x60;wpr&#x60; is wins / requests × 100. An unknown name is ignored and echoed in &#x60;ignored_metrics&#x60;; with &#x60;strict&#x3D;1&#x60; it is a 422. | [optional] |
| **filter_campaign_id** | **string**| RTB campaign ID, or a comma-separated list. | [optional] |
| **filter_network_id** | **int**| RTB network ID. | [optional] |
| **filter_ad_type** | **int**| Ad-type ID. Available types are tenant-specific (banner, popunder, native, video, push, …); the dropdown shows the types active on your system. | [optional] |
| **filter_device** | **int**| Device type:  | ID | Device | |----|--------| | 1  | Computer | | 2  | Tablet | | 3  | Phone | | 4  | Console | | 5  | Wearable | | 6  | TV | | [optional] |
| **filter_country** | **string**| ISO-3166 alpha-2 country code (e.g. &#x60;US&#x60;, &#x60;DE&#x60;, &#x60;BR&#x60;). | [optional] |
| **filter_spaceid** | **int**| Adspace ID. | [optional] |
| **filter_isp_id** | **string**| ISP / ASN id, or a comma-separated list. | [optional] |
| **filter_domain_id** | **string**| Domain id, or a comma-separated list. | [optional] |
| **filter_site_id** | **string**| Publisher site id, or a comma-separated list. | [optional] |
| **filter_ad_id** | **string**| Ad id, or a comma-separated list. | [optional] |
| **filter_os** | **string**| Operating-system id (&#x60;GET /api/v1/lookups/operating-systems&#x60;), or a comma-separated list. | [optional] |
| **filter_browser** | **string**| Browser id (&#x60;GET /api/v1/lookups/browsers&#x60;), or a comma-separated list. | [optional] |
| **filter_conn_type** | **string**| Connection-type id, or a comma-separated list. | [optional] |
| **format** | **string**|  | [optional] [default to &#39;json&#39;] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 100] |
| **sort** | **string**| Sort key, &#x60;-&#x60; prefix for descending: &#x60;requests&#x60;, &#x60;bids&#x60;, &#x60;wins&#x60;, &#x60;noresult&#x60;, &#x60;errors&#x60;, &#x60;views&#x60;, &#x60;clicks&#x60;, &#x60;paid&#x60;, &#x60;timeouts&#x60;, &#x60;skipnobids&#x60;, &#x60;skiptimeouts&#x60;, &#x60;ctr&#x60;, &#x60;wpr&#x60;, &#x60;win_pct&#x60;, &#x60;bid_rate&#x60;, &#x60;date&#x60; (non-&#x60;sum&#x60; interval only). Any other value falls back to &#x60;-paid&#x60;. | [optional] [default to &#39;-paid&#39;] |
| **strict** | **string**| &#x60;1&#x60; turns an unknown metric name into a 422 instead of ignoring it. | [optional] |

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
