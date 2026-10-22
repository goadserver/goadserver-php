# GoAdServerApi\StatsAdvertiserOffersApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1AdvertiserOffersStatsGet()**](StatsAdvertiserOffersApi.md#apiV1AdvertiserOffersStatsGet) | **GET** /api/v1/advertiser/offers/stats | CPA / affiliate offer performance — advertiser side. |


## `apiV1AdvertiserOffersStatsGet()`

```php
apiV1AdvertiserOffersStatsGet($from, $to, $interval, $group_by, $metrics, $filter_offer_id, $filter_offer_type_id, $filter_device, $filter_country, $format, $page, $per_page, $sort): \GoAdServerApi\Model\StatsResponse
```

CPA / affiliate offer performance — advertiser side.

Performance for the advertiser's CPA / affiliate offers.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\StatsAdvertiserOffersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | ISO date — inclusive start. Defaults to 7 days ago.
$to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | ISO date — inclusive end. Defaults to today. Max range 92 days.
$interval = 'sum'; // string
$group_by = ["offer","country"]; // string[] | Dimensions to aggregate by. Multi-pick up to 8.
$metrics = array('metrics_example'); // string[] | Metrics to return. Multi-pick — leave empty for all.
$filter_offer_id = 56; // int | Offer ID. Look up via `GET /api/v1/offers`.
$filter_offer_type_id = 56; // int | Offer-type ID.
$filter_device = 56; // int | Device type:  | ID | Device | |----|--------| | 1  | Computer | | 2  | Tablet | | 3  | Phone | | 4  | Console | | 5  | Wearable | | 6  | TV |
$filter_country = US; // string | ISO-3166 alpha-2 country code (e.g. `US`, `DE`, `BR`).
$format = 'json'; // string
$page = 1; // int
$per_page = 100; // int
$sort = 'sort_example'; // string | Field name. Prefix with `-` for descending (e.g. `-views`).

try {
    $result = $apiInstance->apiV1AdvertiserOffersStatsGet($from, $to, $interval, $group_by, $metrics, $filter_offer_id, $filter_offer_type_id, $filter_device, $filter_country, $format, $page, $per_page, $sort);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling StatsAdvertiserOffersApi->apiV1AdvertiserOffersStatsGet: ', $e->getMessage(), PHP_EOL;
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
| **filter_offer_id** | **int**| Offer ID. Look up via &#x60;GET /api/v1/offers&#x60;. | [optional] |
| **filter_offer_type_id** | **int**| Offer-type ID. | [optional] |
| **filter_device** | **int**| Device type:  | ID | Device | |----|--------| | 1  | Computer | | 2  | Tablet | | 3  | Phone | | 4  | Console | | 5  | Wearable | | 6  | TV | | [optional] |
| **filter_country** | **string**| ISO-3166 alpha-2 country code (e.g. &#x60;US&#x60;, &#x60;DE&#x60;, &#x60;BR&#x60;). | [optional] |
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
