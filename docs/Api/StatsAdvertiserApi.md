# GoAdServerApi\StatsAdvertiserApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1AdvertiserStatsGet()**](StatsAdvertiserApi.md#apiV1AdvertiserStatsGet) | **GET** /api/v1/advertiser/stats | Display, native and CPM/RON campaign stats. |


## `apiV1AdvertiserStatsGet()`

```php
apiV1AdvertiserStatsGet($from, $to, $interval, $group_by, $metrics, $filter_campaign_id, $filter_ad_id, $filter_tag_id, $filter_ad_type, $filter_selling_type, $filter_adzone_type, $filter_device, $filter_country, $filter_adzone_id, $filter_site_id, $filter_network_id, $filter_bidruleset_id, $filter_bidrule_id, $filter_creative_pool_id, $filter_creative_pool_ad_id, $format, $page, $per_page, $sort, $min, $max, $strict): \GoAdServerApi\Model\StatsResponse
```

Display, native and CPM/RON campaign stats.

Aggregated performance for the advertiser's campaigns. Pick the dimensions to group by, the metrics you want returned, and any filters — the form below auto-builds the query.  The numbers are the panel's Stats screen numbers: the same aggregate and the same formulas (revenue, profit, ROI, CPA, CR, eCPC, traffic cost and the bot breakdown included), over `userstats_daily` (or `userstats_daily_details` when a grouped dimension or filter needs it).  **Money unit:** every money metric (`paid`, `cpm_paid`, `cpc_paid`, `ecpm`, `ecpc`, `conv_*_earned`, `revenue`, `profit`, `cpa`, `traffic_cost`) is in the account currency's **main unit** (dollars/euros, not cents), rounded to 4 decimals. `ctr`, `cr`, `roi` and the `bot_*_pct` metrics are percentages.  **Dates:** `from` and `to` are used only when *both* are given and valid; otherwise the range is the last 7 days ending today (a lone `from` is ignored). Max range 92 days.  A key made by a team member whose role is limited to their own campaigns sees only those campaigns, as in the panel. A key pinned to campaigns sees only those campaigns.  **Several values:** `filter[ad_type]`, `filter[device]` and `filter[country]` also take a comma-separated list (`filter[country]=NL,DE`), matching any of them — the panel's multi-select filters send the same.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\StatsAdvertiserApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | ISO date — inclusive start. Defaults to 7 days ago.
$to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | ISO date — inclusive end. Defaults to today. Max range 92 days.
$interval = 'sum'; // string
$group_by = ["campaign","country"]; // string[] | Dimensions to aggregate by, in priority order. Multi-pick up to 8. High-cardinality dims (`subid`, `sid1`–`sid5`, `domain`) explode row count — pair with `from`/`to`/pagination. An unknown dimension is a 422.  `sid1`–`sid5` are the named custom-parameter slots the tracker records per click; their labels are whatever this account configured. On a Search Ads zone `sid1` holds the visitor's search; a network can keep those terms admin-only, and then Search Ads rows report an empty `sid1`. `creative_pool` / `url_pool` group by the pool an ad drew from, `creative_pool_ad` / `url_pool_ad` by the individual pool item. `adzone_type` is the ad placement (e.g. in-page video vs VAST pre-roll on the same video ad type). `selling_type` groups by pay type (see `filter[selling_type]` for the ids).  Each grouped dimension comes back as an `{id, name}` object, except `country`, `domain` and `subid`, which are plain strings. Without the `stats_source_names` right `name` equals the id.
$metrics = ["views","clicks","paid","revenue","profit","roi"]; // string[] | Metrics to return (comma-separated). **Empty** returns the default set `views, clicks, ctr, ecpm, paid, conv_a, conv_b, conv_c` (unchanged since launch). `all` returns every metric this caller may read. Everything else is opt-in by name:  | Metric | Meaning | |---|---| | `ecpm` | blended: `paid` / `views` × 1000 | | `ecpc` | CPC spend / CPC clicks (click-billed ad types only) | | `cpm_views`, `cpm_paid` | views / spend on impression-billed ad types | | `cpc_clicks`, `cpc_paid` | clicks / spend on click-billed ad types | | `conv_a_earned` … `conv_c_earned` | conversion revenue per conversion type (the postback value) | | `revenue` | conversion revenue over the selected conversion types | | `profit` | `revenue` − cost (cost = `traffic_cost` when the row carries one, else `paid`) | | `roi` | `profit` / cost × 100; `null` when cost < 1.00 | | `cpa` | `paid` / conversions of the selected types | | `cr` | conversions of the selected types / `clicks` × 100 | | `traffic_cost` | traffic-acquisition cost pulled from a bound ad network — **ad manager accounts only** (on an ad network it is the network's own buying cost: network admins only, an advertiser key gets a 422 and `all` leaves it out) | | `nw_views`, `nw_clicks` | the ad network's own counters for that traffic — same rule as `traffic_cost` | | `bot_views`, `bot_clicks`, `bot_views_pct`, `bot_clicks_pct`, `bot_unknown`, `bot_click`, `bot_impression`, `bot_conversion`, `bot_web`, `bot_high_frequency`, `bot_artificial_ip`, `bot_adspace_abuse`, `bot_suspicious`, `bot_datacenter`, `bot_vpn` | bot breakdown — **network admins only** (as in the panel); an advertiser key asking for one gets a 422 |  \"Selected conversion types\" are the `conv_a`/`conv_b`/`conv_c` metrics in this request; with none of them requested, `revenue`/`cpa`/`cr` cover all three (same rule as the panel).  An unknown metric name is ignored and listed in the response's `ignored_metrics`; with `strict=1` it is a 422.
$filter_campaign_id = 123,124; // string | Campaign ID, or a comma-separated list. Look up via `GET /api/v1/campaigns`.
$filter_ad_id = 'filter_ad_id_example'; // string | Ad ID, or a comma-separated list.
$filter_tag_id = 'filter_tag_id_example'; // string | Campaign group (tag) ID, or a comma-separated list — resolves to the campaigns in those groups, sub-groups included. A group with no campaigns returns nothing.
$filter_ad_type = 56; // int | Ad-type ID. Available types are tenant-specific (banner, popunder, native, video, push, …); the dropdown shows the types active on your system.
$filter_selling_type = 2; // string | Pay type (the campaign's pricing model), one id or a comma-separated list:  | ID | Pay type | |----|----------| | 1  | CPC | | 2  | CPM | | 3  | CPA | | 5  | SmartAds | | 6  | Flat rate |  (Pay-type id = the tenant's `bid_types` id + 1; the ids above are the standard set.)
$filter_adzone_type = 'filter_adzone_type_example'; // string | Adzone (placement) type id, or a comma-separated list.
$filter_device = 56; // int | Device type:  | ID | Device | |----|--------| | 1  | Computer | | 2  | Tablet | | 3  | Phone | | 4  | Console | | 5  | Wearable | | 6  | TV |
$filter_country = US; // string | ISO-3166 alpha-2 country code (e.g. `US`, `DE`, `BR`).
$filter_adzone_id = 12345,12346; // string | Adspace ID, or comma-separated list. Look up via `GET /api/v1/adspaces`.
$filter_site_id = 'filter_site_id_example'; // string | Comma-separated site IDs. Look up via `GET /api/v1/sites`.
$filter_network_id = 56; // int | RTB network ID.
$filter_bidruleset_id = 56; // int | Bid ruleset ID — only traffic bid through that ruleset (`GET /api/v1/bid-rulesets`).
$filter_bidrule_id = 56; // int | Bid rule ID inside a ruleset — only traffic that rule priced.
$filter_creative_pool_id = 56; // int | Creative pool ID.
$filter_creative_pool_ad_id = 56; // int | Creative pool item ID.
$format = 'json'; // string
$page = 1; // int
$per_page = 100; // int
$sort = -revenue; // string | Sort key, `-` prefix for descending. One of `views`, `clicks`, `ctr`, `paid`, `ecpm`, `ecpc`, `conv_a`, `conv_b`, `conv_c`, `conv_a_earned`, `conv_b_earned`, `conv_c_earned`, `revenue`, `traffic_cost`, `nw_views`, `nw_clicks` (where those metrics are available, see `metrics`), `date` (only with a non-`sum` interval); admins also `bot_views`, `bot_clicks`. Any other value falls back to the default, `-paid`.
$min = {"cpa":10}; // array<string,float> | Keep only rows whose metric is at least this value, as `min[<metric>]=<number>` — e.g. `min[cpa]=10`. Pair with `max[<metric>]` for a range (both ends inclusive). Any metric `metrics` accepts for this caller can be bounded, whether or not it is returned; several metrics combine with AND. A row with no value for the metric (`roi` below 1.00 of spend) is dropped. Money in main currency units, `ctr` in percent. Same as the comparisons (`>10`, `<200`, `10-200`) in the panel's statistics details column search.  With a bound set, up to 10,000 grouped rows (in `sort` order) are tested and the survivors paginated; `total_rows` counts the survivors, `totals` stay over the whole selection, and `range_scan_truncated: true` says more rows existed than were tested. Unknown metric, non-number or min > max → 422.
$max = {"cpa":200}; // array<string,float> | Keep only rows whose metric is at most this value, as `max[<metric>]=<number>`. See `min`.
$strict = 'strict_example'; // string | `1` turns an unknown metric name into a 422 instead of ignoring it.

try {
    $result = $apiInstance->apiV1AdvertiserStatsGet($from, $to, $interval, $group_by, $metrics, $filter_campaign_id, $filter_ad_id, $filter_tag_id, $filter_ad_type, $filter_selling_type, $filter_adzone_type, $filter_device, $filter_country, $filter_adzone_id, $filter_site_id, $filter_network_id, $filter_bidruleset_id, $filter_bidrule_id, $filter_creative_pool_id, $filter_creative_pool_ad_id, $format, $page, $per_page, $sort, $min, $max, $strict);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling StatsAdvertiserApi->apiV1AdvertiserStatsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **from** | **\DateTime**| ISO date — inclusive start. Defaults to 7 days ago. | [optional] |
| **to** | **\DateTime**| ISO date — inclusive end. Defaults to today. Max range 92 days. | [optional] |
| **interval** | **string**|  | [optional] [default to &#39;sum&#39;] |
| **group_by** | [**string[]**](../Model/string.md)| Dimensions to aggregate by, in priority order. Multi-pick up to 8. High-cardinality dims (&#x60;subid&#x60;, &#x60;sid1&#x60;–&#x60;sid5&#x60;, &#x60;domain&#x60;) explode row count — pair with &#x60;from&#x60;/&#x60;to&#x60;/pagination. An unknown dimension is a 422.  &#x60;sid1&#x60;–&#x60;sid5&#x60; are the named custom-parameter slots the tracker records per click; their labels are whatever this account configured. On a Search Ads zone &#x60;sid1&#x60; holds the visitor&#39;s search; a network can keep those terms admin-only, and then Search Ads rows report an empty &#x60;sid1&#x60;. &#x60;creative_pool&#x60; / &#x60;url_pool&#x60; group by the pool an ad drew from, &#x60;creative_pool_ad&#x60; / &#x60;url_pool_ad&#x60; by the individual pool item. &#x60;adzone_type&#x60; is the ad placement (e.g. in-page video vs VAST pre-roll on the same video ad type). &#x60;selling_type&#x60; groups by pay type (see &#x60;filter[selling_type]&#x60; for the ids).  Each grouped dimension comes back as an &#x60;{id, name}&#x60; object, except &#x60;country&#x60;, &#x60;domain&#x60; and &#x60;subid&#x60;, which are plain strings. Without the &#x60;stats_source_names&#x60; right &#x60;name&#x60; equals the id. | [optional] |
| **metrics** | [**string[]**](../Model/string.md)| Metrics to return (comma-separated). **Empty** returns the default set &#x60;views, clicks, ctr, ecpm, paid, conv_a, conv_b, conv_c&#x60; (unchanged since launch). &#x60;all&#x60; returns every metric this caller may read. Everything else is opt-in by name:  | Metric | Meaning | |---|---| | &#x60;ecpm&#x60; | blended: &#x60;paid&#x60; / &#x60;views&#x60; × 1000 | | &#x60;ecpc&#x60; | CPC spend / CPC clicks (click-billed ad types only) | | &#x60;cpm_views&#x60;, &#x60;cpm_paid&#x60; | views / spend on impression-billed ad types | | &#x60;cpc_clicks&#x60;, &#x60;cpc_paid&#x60; | clicks / spend on click-billed ad types | | &#x60;conv_a_earned&#x60; … &#x60;conv_c_earned&#x60; | conversion revenue per conversion type (the postback value) | | &#x60;revenue&#x60; | conversion revenue over the selected conversion types | | &#x60;profit&#x60; | &#x60;revenue&#x60; − cost (cost &#x3D; &#x60;traffic_cost&#x60; when the row carries one, else &#x60;paid&#x60;) | | &#x60;roi&#x60; | &#x60;profit&#x60; / cost × 100; &#x60;null&#x60; when cost &lt; 1.00 | | &#x60;cpa&#x60; | &#x60;paid&#x60; / conversions of the selected types | | &#x60;cr&#x60; | conversions of the selected types / &#x60;clicks&#x60; × 100 | | &#x60;traffic_cost&#x60; | traffic-acquisition cost pulled from a bound ad network — **ad manager accounts only** (on an ad network it is the network&#39;s own buying cost: network admins only, an advertiser key gets a 422 and &#x60;all&#x60; leaves it out) | | &#x60;nw_views&#x60;, &#x60;nw_clicks&#x60; | the ad network&#39;s own counters for that traffic — same rule as &#x60;traffic_cost&#x60; | | &#x60;bot_views&#x60;, &#x60;bot_clicks&#x60;, &#x60;bot_views_pct&#x60;, &#x60;bot_clicks_pct&#x60;, &#x60;bot_unknown&#x60;, &#x60;bot_click&#x60;, &#x60;bot_impression&#x60;, &#x60;bot_conversion&#x60;, &#x60;bot_web&#x60;, &#x60;bot_high_frequency&#x60;, &#x60;bot_artificial_ip&#x60;, &#x60;bot_adspace_abuse&#x60;, &#x60;bot_suspicious&#x60;, &#x60;bot_datacenter&#x60;, &#x60;bot_vpn&#x60; | bot breakdown — **network admins only** (as in the panel); an advertiser key asking for one gets a 422 |  \&quot;Selected conversion types\&quot; are the &#x60;conv_a&#x60;/&#x60;conv_b&#x60;/&#x60;conv_c&#x60; metrics in this request; with none of them requested, &#x60;revenue&#x60;/&#x60;cpa&#x60;/&#x60;cr&#x60; cover all three (same rule as the panel).  An unknown metric name is ignored and listed in the response&#39;s &#x60;ignored_metrics&#x60;; with &#x60;strict&#x3D;1&#x60; it is a 422. | [optional] |
| **filter_campaign_id** | **string**| Campaign ID, or a comma-separated list. Look up via &#x60;GET /api/v1/campaigns&#x60;. | [optional] |
| **filter_ad_id** | **string**| Ad ID, or a comma-separated list. | [optional] |
| **filter_tag_id** | **string**| Campaign group (tag) ID, or a comma-separated list — resolves to the campaigns in those groups, sub-groups included. A group with no campaigns returns nothing. | [optional] |
| **filter_ad_type** | **int**| Ad-type ID. Available types are tenant-specific (banner, popunder, native, video, push, …); the dropdown shows the types active on your system. | [optional] |
| **filter_selling_type** | **string**| Pay type (the campaign&#39;s pricing model), one id or a comma-separated list:  | ID | Pay type | |----|----------| | 1  | CPC | | 2  | CPM | | 3  | CPA | | 5  | SmartAds | | 6  | Flat rate |  (Pay-type id &#x3D; the tenant&#39;s &#x60;bid_types&#x60; id + 1; the ids above are the standard set.) | [optional] |
| **filter_adzone_type** | **string**| Adzone (placement) type id, or a comma-separated list. | [optional] |
| **filter_device** | **int**| Device type:  | ID | Device | |----|--------| | 1  | Computer | | 2  | Tablet | | 3  | Phone | | 4  | Console | | 5  | Wearable | | 6  | TV | | [optional] |
| **filter_country** | **string**| ISO-3166 alpha-2 country code (e.g. &#x60;US&#x60;, &#x60;DE&#x60;, &#x60;BR&#x60;). | [optional] |
| **filter_adzone_id** | **string**| Adspace ID, or comma-separated list. Look up via &#x60;GET /api/v1/adspaces&#x60;. | [optional] |
| **filter_site_id** | **string**| Comma-separated site IDs. Look up via &#x60;GET /api/v1/sites&#x60;. | [optional] |
| **filter_network_id** | **int**| RTB network ID. | [optional] |
| **filter_bidruleset_id** | **int**| Bid ruleset ID — only traffic bid through that ruleset (&#x60;GET /api/v1/bid-rulesets&#x60;). | [optional] |
| **filter_bidrule_id** | **int**| Bid rule ID inside a ruleset — only traffic that rule priced. | [optional] |
| **filter_creative_pool_id** | **int**| Creative pool ID. | [optional] |
| **filter_creative_pool_ad_id** | **int**| Creative pool item ID. | [optional] |
| **format** | **string**|  | [optional] [default to &#39;json&#39;] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 100] |
| **sort** | **string**| Sort key, &#x60;-&#x60; prefix for descending. One of &#x60;views&#x60;, &#x60;clicks&#x60;, &#x60;ctr&#x60;, &#x60;paid&#x60;, &#x60;ecpm&#x60;, &#x60;ecpc&#x60;, &#x60;conv_a&#x60;, &#x60;conv_b&#x60;, &#x60;conv_c&#x60;, &#x60;conv_a_earned&#x60;, &#x60;conv_b_earned&#x60;, &#x60;conv_c_earned&#x60;, &#x60;revenue&#x60;, &#x60;traffic_cost&#x60;, &#x60;nw_views&#x60;, &#x60;nw_clicks&#x60; (where those metrics are available, see &#x60;metrics&#x60;), &#x60;date&#x60; (only with a non-&#x60;sum&#x60; interval); admins also &#x60;bot_views&#x60;, &#x60;bot_clicks&#x60;. Any other value falls back to the default, &#x60;-paid&#x60;. | [optional] [default to &#39;-paid&#39;] |
| **min** | [**array<string,float>**](../Model/float.md)| Keep only rows whose metric is at least this value, as &#x60;min[&lt;metric&gt;]&#x3D;&lt;number&gt;&#x60; — e.g. &#x60;min[cpa]&#x3D;10&#x60;. Pair with &#x60;max[&lt;metric&gt;]&#x60; for a range (both ends inclusive). Any metric &#x60;metrics&#x60; accepts for this caller can be bounded, whether or not it is returned; several metrics combine with AND. A row with no value for the metric (&#x60;roi&#x60; below 1.00 of spend) is dropped. Money in main currency units, &#x60;ctr&#x60; in percent. Same as the comparisons (&#x60;&gt;10&#x60;, &#x60;&lt;200&#x60;, &#x60;10-200&#x60;) in the panel&#39;s statistics details column search.  With a bound set, up to 10,000 grouped rows (in &#x60;sort&#x60; order) are tested and the survivors paginated; &#x60;total_rows&#x60; counts the survivors, &#x60;totals&#x60; stay over the whole selection, and &#x60;range_scan_truncated: true&#x60; says more rows existed than were tested. Unknown metric, non-number or min &gt; max → 422. | [optional] |
| **max** | [**array<string,float>**](../Model/float.md)| Keep only rows whose metric is at most this value, as &#x60;max[&lt;metric&gt;]&#x3D;&lt;number&gt;&#x60;. See &#x60;min&#x60;. | [optional] |
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
