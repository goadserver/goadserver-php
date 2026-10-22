# GoAdServerApi\StatsAffiliateApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1PublisherOffersPostbackLogGet()**](StatsAffiliateApi.md#apiV1PublisherOffersPostbackLogGet) | **GET** /api/v1/publisher/offers/postback-log | Postback log — the conversion postbacks fired to your URLs. |
| [**apiV1PublisherOffersStatsGet()**](StatsAffiliateApi.md#apiV1PublisherOffersStatsGet) | **GET** /api/v1/publisher/offers/stats | Affiliate / smartlink offer performance — publisher side. |


## `apiV1PublisherOffersPostbackLogGet()`

```php
apiV1PublisherOffersPostbackLogGet($from, $to, $site_id): \GoAdServerApi\Model\ApiV1PublisherOffersPostbackLogGet200Response
```

Postback log — the conversion postbacks fired to your URLs.

The panel's Offer Reports → Postback Log. One row per postback the platform fired to the publisher's postback URL, newest first, capped at 1000 rows (no paging). `status` is `Success` for HTTP 200 (or an unrecorded state) and `Failed (<state>)` otherwise. Date params that are missing or not `YYYY-MM-DD` fall back to the last 7 days.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\StatsAffiliateApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | ISO date — inclusive start. Defaults to 7 days ago.
$to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | ISO date — inclusive end. Defaults to today. Max range 92 days.
$site_id = 56; // int | Only postbacks for this site.

try {
    $result = $apiInstance->apiV1PublisherOffersPostbackLogGet($from, $to, $site_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling StatsAffiliateApi->apiV1PublisherOffersPostbackLogGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **from** | **\DateTime**| ISO date — inclusive start. Defaults to 7 days ago. | [optional] |
| **to** | **\DateTime**| ISO date — inclusive end. Defaults to today. Max range 92 days. | [optional] |
| **site_id** | **int**| Only postbacks for this site. | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherOffersPostbackLogGet200Response**](../Model/ApiV1PublisherOffersPostbackLogGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherOffersStatsGet()`

```php
apiV1PublisherOffersStatsGet($from, $to, $interval, $group_by, $metrics, $filter_offer_id, $filter_offer_type_id, $filter_website, $filter_adzone_id, $filter_device, $filter_country, $filter_subid, $format, $page, $per_page, $sort): \GoAdServerApi\Model\StatsResponse
```

Affiliate / smartlink offer performance — publisher side.

Affiliate / smartlink offer performance for the publisher. `earned` and `epc` are in the account currency (currency units, not cents).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\StatsAffiliateApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | ISO date — inclusive start. Defaults to 7 days ago.
$to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | ISO date — inclusive end. Defaults to today. Max range 92 days.
$interval = 'sum'; // string
$group_by = ["offer","country"]; // string[] | Dimensions to aggregate by. Multi-pick up to 8. High-cardinality dims (`subid`, `clickid`, `sid*`, `domain`) explode row count. `clickid` is the advertiser-side click id passed back on the conversion (the panel's ClickID tab) and comes back as plain text.
$metrics = array('metrics_example'); // string[] | Metrics to return. Multi-pick — leave empty for all.
$filter_offer_id = 56; // int | Offer ID. Look up via `GET /api/v1/offers`.
$filter_offer_type_id = 56; // int | Offer-type ID.
$filter_website = 56; // int | Site ID — look up via `GET /api/v1/sites`.
$filter_adzone_id = 12345,12346; // string | Adspace ID, or comma-separated list. Look up via `GET /api/v1/adspaces`.
$filter_device = 56; // int | Device type:  | ID | Device | |----|--------| | 1  | Computer | | 2  | Tablet | | 3  | Phone | | 4  | Console | | 5  | Wearable | | 6  | TV |
$filter_country = US; // string | ISO-3166 alpha-2 country code (e.g. `US`, `DE`, `BR`).
$filter_subid = 'filter_subid_example'; // string | SubID (free-form publisher identifier).
$format = 'json'; // string
$page = 1; // int
$per_page = 100; // int
$sort = 'sort_example'; // string | Field name. Prefix with `-` for descending (e.g. `-views`).

try {
    $result = $apiInstance->apiV1PublisherOffersStatsGet($from, $to, $interval, $group_by, $metrics, $filter_offer_id, $filter_offer_type_id, $filter_website, $filter_adzone_id, $filter_device, $filter_country, $filter_subid, $format, $page, $per_page, $sort);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling StatsAffiliateApi->apiV1PublisherOffersStatsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **from** | **\DateTime**| ISO date — inclusive start. Defaults to 7 days ago. | [optional] |
| **to** | **\DateTime**| ISO date — inclusive end. Defaults to today. Max range 92 days. | [optional] |
| **interval** | **string**|  | [optional] [default to &#39;sum&#39;] |
| **group_by** | [**string[]**](../Model/string.md)| Dimensions to aggregate by. Multi-pick up to 8. High-cardinality dims (&#x60;subid&#x60;, &#x60;clickid&#x60;, &#x60;sid*&#x60;, &#x60;domain&#x60;) explode row count. &#x60;clickid&#x60; is the advertiser-side click id passed back on the conversion (the panel&#39;s ClickID tab) and comes back as plain text. | [optional] |
| **metrics** | [**string[]**](../Model/string.md)| Metrics to return. Multi-pick — leave empty for all. | [optional] |
| **filter_offer_id** | **int**| Offer ID. Look up via &#x60;GET /api/v1/offers&#x60;. | [optional] |
| **filter_offer_type_id** | **int**| Offer-type ID. | [optional] |
| **filter_website** | **int**| Site ID — look up via &#x60;GET /api/v1/sites&#x60;. | [optional] |
| **filter_adzone_id** | **string**| Adspace ID, or comma-separated list. Look up via &#x60;GET /api/v1/adspaces&#x60;. | [optional] |
| **filter_device** | **int**| Device type:  | ID | Device | |----|--------| | 1  | Computer | | 2  | Tablet | | 3  | Phone | | 4  | Console | | 5  | Wearable | | 6  | TV | | [optional] |
| **filter_country** | **string**| ISO-3166 alpha-2 country code (e.g. &#x60;US&#x60;, &#x60;DE&#x60;, &#x60;BR&#x60;). | [optional] |
| **filter_subid** | **string**| SubID (free-form publisher identifier). | [optional] |
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
