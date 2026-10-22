# GoAdServerApi\StatsPublisherApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1PublisherStatsGet()**](StatsPublisherApi.md#apiV1PublisherStatsGet) | **GET** /api/v1/publisher/stats | Publisher earnings (sites / adzones). |


## `apiV1PublisherStatsGet()`

```php
apiV1PublisherStatsGet($from, $to, $interval, $viewtype, $group_by, $metrics, $filter_website, $filter_adzone_id, $filter_country, $filter_device, $filter_ad_type, $filter_selling_type, $filter_tag_id, $filter_adzone_type, $filter_ad_domain, $filter_bots, $min, $max, $format, $page, $per_page, $sort): \GoAdServerApi\Model\StatsResponse
```

Publisher earnings (sites / adzones).

Aggregated publisher earnings. Pick the dimensions to group by, the metrics you want returned, and any filters — the form below auto-builds the query.  **Money:** `earned` and `ecpm` are in the account currency (currency units, not cents), like the panel.  **Bot counters:** `bot_views` / `bot_clicks` and `filter[bots]` are available where the panel shows its Bot Info columns — admanager tenants, or accounts holding the `display_botinfo` right. Elsewhere the two metrics are not returned even when asked for, and `filter[bots]` is ignored.  **Conversion slots:** `conv_a` / `conv_b` / `conv_c` / `conv_d` / `conv_e` are conversion types 0 / 1 / 2 / 3 / 4. On admanager tenants they are your own slots, named on `GET /api/v1/tracking/conversion-types` (ids 0-4); the panel and its CSV exports head the columns with those names there. On an adnetwork they stay neutral letters.  **Costs and cost per conversion:** on admanager tenants (where the panel shows its Costs / CPA columns) `costs` is your traffic cost in the account currency and `cost_per_conv_a` … `cost_per_conv_e` is `costs` divided by that slot's conversions (0 when the slot has none), rounded to 4 decimals, on every row and in `totals`. Pick the slot that is your acquisition: e.g. a cost per lead is `cost_per_conv_a` when slot 0 is your \"Sign up / lead\" type, a cost per join `cost_per_conv_e` when slot 4 is \"Joins\". The panel's `cpa` column is `cost_per_conv_a`. Elsewhere these metrics are not returned even when asked for. All six also sort (`sort=-cost_per_conv_e`).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\StatsPublisherApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | ISO date — inclusive start. Defaults to 7 days ago.
$to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | ISO date — inclusive end. Defaults to today. Max range 92 days.
$interval = 'sum'; // string
$viewtype = 'viewtype_example'; // string | The panel's name for `interval` (\"Display By\"). Used only when `interval` is absent.
$group_by = ["country","device"]; // string[] | Dimensions to aggregate by, in priority order. Multi-pick up to 8. High-cardinality dims (`subid`, `clickid`, `sid*`, `domain`) explode row count — pair with `from`/`to`/pagination.  Each grouped dimension comes back as an `{id, name}` object, except `country`, `domain` and `subid`, which are plain strings. `ad_type` is the campaign ad type (name from the tenant's ad-type list); `ad_domain` is the ad-serving domain the impression was served from (`{id, name}` with the domain as name). On the free-text dims that do return an object (`clickid`, `sid1`–`sid5`) the `id` is always 0 and `name` carries the value.
$metrics = array('metrics_example'); // string[] | Metrics to return. Multi-pick — leave empty for all. Unknown names are ignored. `bot_views` / `bot_clicks` (views / clicks the ad server flagged as bot traffic, a subset of `views` / `clicks`) only where Bot Info is enabled, `costs` and `cost_per_conv_a` … `cost_per_conv_e` only on admanager tenants — see above.
$filter_website = 56; // int | Site ID — look up via `GET /api/v1/sites`.
$filter_adzone_id = 12345,12346; // string | Adspace ID, or comma-separated list. Look up via `GET /api/v1/adspaces`.
$filter_country = US; // string | ISO-3166 alpha-2 country code (e.g. `US`, `DE`, `BR`).
$filter_device = 56; // int | Device type:  | ID | Device | |----|--------| | 1  | Computer | | 2  | Tablet | | 3  | Phone | | 4  | Console | | 5  | Wearable | | 6  | TV |
$filter_ad_type = 56; // int | Ad-type ID. Available types are tenant-specific (banner, popunder, native, video, push, …); the dropdown shows the types active on your system.
$filter_selling_type = 56; // int | Campaign selling type:  | ID | Selling type | |----|--------------| | 1  | RON (CPC) | | 2  | Flatrate | | 3  | CPM | | 4  | RTB | | 5  | SmartZone |
$filter_tag_id = 'filter_tag_id_example'; // string | Tag ID, or comma-separated list. Resolves to the bound adzones.
$filter_adzone_type = 2,11; // string | Adzone type id(s), comma-separated (the `id` of `GET /api/v1/lookups/adspace-types`).
$filter_ad_domain = 14; // string | Ad-serving domain id(s), comma-separated — the `id` returned for `group_by=ad_domain`.
$filter_bots = 56; // int | `1` = only traffic flagged as bots, `2` = exclude it. Ignored unless Bot Info is enabled for the account (see above).
$min = {"cost_per_conv_a":10}; // array<string,float> | Keep only rows whose metric is at least this value, as `min[<metric>]=<number>` — e.g. `min[cost_per_conv_a]=10`. Pair with `max[<metric>]` for a range (both ends inclusive). Any metric `metrics` accepts for this caller can be bounded, whether or not it is returned; several metrics combine with AND. A row with no value for the metric is dropped. Money in main currency units, `ctr` in percent. Same as the comparisons (`>10`, `<200`, `10-200`) in the panel's statistics details column search.  With a bound set, up to 10,000 grouped rows (in `sort` order) are tested and the survivors paginated; `total_rows` counts the survivors, `totals` stay over the whole selection, and `range_scan_truncated: true` says more rows existed than were tested. Unknown metric, non-number or min > max → 422.
$max = {"cost_per_conv_a":200}; // array<string,float> | Keep only rows whose metric is at most this value, as `max[<metric>]=<number>`. See `min`.
$format = 'json'; // string
$page = 1; // int
$per_page = 100; // int
$sort = 'sort_example'; // string | Field name. Prefix with `-` for descending (e.g. `-views`).

try {
    $result = $apiInstance->apiV1PublisherStatsGet($from, $to, $interval, $viewtype, $group_by, $metrics, $filter_website, $filter_adzone_id, $filter_country, $filter_device, $filter_ad_type, $filter_selling_type, $filter_tag_id, $filter_adzone_type, $filter_ad_domain, $filter_bots, $min, $max, $format, $page, $per_page, $sort);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling StatsPublisherApi->apiV1PublisherStatsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **from** | **\DateTime**| ISO date — inclusive start. Defaults to 7 days ago. | [optional] |
| **to** | **\DateTime**| ISO date — inclusive end. Defaults to today. Max range 92 days. | [optional] |
| **interval** | **string**|  | [optional] [default to &#39;sum&#39;] |
| **viewtype** | **string**| The panel&#39;s name for &#x60;interval&#x60; (\&quot;Display By\&quot;). Used only when &#x60;interval&#x60; is absent. | [optional] |
| **group_by** | [**string[]**](../Model/string.md)| Dimensions to aggregate by, in priority order. Multi-pick up to 8. High-cardinality dims (&#x60;subid&#x60;, &#x60;clickid&#x60;, &#x60;sid*&#x60;, &#x60;domain&#x60;) explode row count — pair with &#x60;from&#x60;/&#x60;to&#x60;/pagination.  Each grouped dimension comes back as an &#x60;{id, name}&#x60; object, except &#x60;country&#x60;, &#x60;domain&#x60; and &#x60;subid&#x60;, which are plain strings. &#x60;ad_type&#x60; is the campaign ad type (name from the tenant&#39;s ad-type list); &#x60;ad_domain&#x60; is the ad-serving domain the impression was served from (&#x60;{id, name}&#x60; with the domain as name). On the free-text dims that do return an object (&#x60;clickid&#x60;, &#x60;sid1&#x60;–&#x60;sid5&#x60;) the &#x60;id&#x60; is always 0 and &#x60;name&#x60; carries the value. | [optional] |
| **metrics** | [**string[]**](../Model/string.md)| Metrics to return. Multi-pick — leave empty for all. Unknown names are ignored. &#x60;bot_views&#x60; / &#x60;bot_clicks&#x60; (views / clicks the ad server flagged as bot traffic, a subset of &#x60;views&#x60; / &#x60;clicks&#x60;) only where Bot Info is enabled, &#x60;costs&#x60; and &#x60;cost_per_conv_a&#x60; … &#x60;cost_per_conv_e&#x60; only on admanager tenants — see above. | [optional] |
| **filter_website** | **int**| Site ID — look up via &#x60;GET /api/v1/sites&#x60;. | [optional] |
| **filter_adzone_id** | **string**| Adspace ID, or comma-separated list. Look up via &#x60;GET /api/v1/adspaces&#x60;. | [optional] |
| **filter_country** | **string**| ISO-3166 alpha-2 country code (e.g. &#x60;US&#x60;, &#x60;DE&#x60;, &#x60;BR&#x60;). | [optional] |
| **filter_device** | **int**| Device type:  | ID | Device | |----|--------| | 1  | Computer | | 2  | Tablet | | 3  | Phone | | 4  | Console | | 5  | Wearable | | 6  | TV | | [optional] |
| **filter_ad_type** | **int**| Ad-type ID. Available types are tenant-specific (banner, popunder, native, video, push, …); the dropdown shows the types active on your system. | [optional] |
| **filter_selling_type** | **int**| Campaign selling type:  | ID | Selling type | |----|--------------| | 1  | RON (CPC) | | 2  | Flatrate | | 3  | CPM | | 4  | RTB | | 5  | SmartZone | | [optional] |
| **filter_tag_id** | **string**| Tag ID, or comma-separated list. Resolves to the bound adzones. | [optional] |
| **filter_adzone_type** | **string**| Adzone type id(s), comma-separated (the &#x60;id&#x60; of &#x60;GET /api/v1/lookups/adspace-types&#x60;). | [optional] |
| **filter_ad_domain** | **string**| Ad-serving domain id(s), comma-separated — the &#x60;id&#x60; returned for &#x60;group_by&#x3D;ad_domain&#x60;. | [optional] |
| **filter_bots** | **int**| &#x60;1&#x60; &#x3D; only traffic flagged as bots, &#x60;2&#x60; &#x3D; exclude it. Ignored unless Bot Info is enabled for the account (see above). | [optional] |
| **min** | [**array<string,float>**](../Model/float.md)| Keep only rows whose metric is at least this value, as &#x60;min[&lt;metric&gt;]&#x3D;&lt;number&gt;&#x60; — e.g. &#x60;min[cost_per_conv_a]&#x3D;10&#x60;. Pair with &#x60;max[&lt;metric&gt;]&#x60; for a range (both ends inclusive). Any metric &#x60;metrics&#x60; accepts for this caller can be bounded, whether or not it is returned; several metrics combine with AND. A row with no value for the metric is dropped. Money in main currency units, &#x60;ctr&#x60; in percent. Same as the comparisons (&#x60;&gt;10&#x60;, &#x60;&lt;200&#x60;, &#x60;10-200&#x60;) in the panel&#39;s statistics details column search.  With a bound set, up to 10,000 grouped rows (in &#x60;sort&#x60; order) are tested and the survivors paginated; &#x60;total_rows&#x60; counts the survivors, &#x60;totals&#x60; stay over the whole selection, and &#x60;range_scan_truncated: true&#x60; says more rows existed than were tested. Unknown metric, non-number or min &gt; max → 422. | [optional] |
| **max** | [**array<string,float>**](../Model/float.md)| Keep only rows whose metric is at most this value, as &#x60;max[&lt;metric&gt;]&#x3D;&lt;number&gt;&#x60;. See &#x60;min&#x60;. | [optional] |
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
