# GoAdServerApi\MarketplaceApi

Curated publisher inventory and flat-deal requests.

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1MarketplaceFiltersGet()**](MarketplaceApi.md#apiV1MarketplaceFiltersGet) | **GET** /api/v1/marketplace/filters | Filter vocabulary for the marketplace. |
| [**apiV1MarketplaceFlatRequestsGet()**](MarketplaceApi.md#apiV1MarketplaceFlatRequestsGet) | **GET** /api/v1/marketplace/flat-requests | List my flat-deal requests. |
| [**apiV1MarketplaceFlatRequestsIdCancelPost()**](MarketplaceApi.md#apiV1MarketplaceFlatRequestsIdCancelPost) | **POST** /api/v1/marketplace/flat-requests/{id}/cancel | Withdraw a pending flat-deal request. |
| [**apiV1MarketplaceFlatRequestsRenewPost()**](MarketplaceApi.md#apiV1MarketplaceFlatRequestsRenewPost) | **POST** /api/v1/marketplace/flat-requests/renew | Propose one more flat deal on a live flat campaign. |
| [**apiV1MarketplaceGet()**](MarketplaceApi.md#apiV1MarketplaceGet) | **GET** /api/v1/marketplace | List marketplace sites. |
| [**apiV1MarketplaceIdGet()**](MarketplaceApi.md#apiV1MarketplaceIdGet) | **GET** /api/v1/marketplace/{id} | Ad zones of one marketplace site, with starting prices. |


## `apiV1MarketplaceFiltersGet()`

```php
apiV1MarketplaceFiltersGet()
```

Filter vocabulary for the marketplace.

Ad types, ad zone types, countries, banner sizes, device types, operating systems, browsers and main categories the marketplace filters accept, plus `flat_request_min_total` (the tenant minimum for a flat-deal amount, in currency units). Same handler as the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\MarketplaceApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1MarketplaceFiltersGet();
} catch (Exception $e) {
    echo 'Exception when calling MarketplaceApi->apiV1MarketplaceFiltersGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1MarketplaceFlatRequestsGet()`

```php
apiV1MarketplaceFlatRequestsGet()
```

List my flat-deal requests.

The account's flat-deal requests, newest first (max 200). A request is created by booking a flat deal in the panel's campaign wizard from a marketplace zone — that create path (`flat_request: 1`) is not exposed on `POST /api/v1/campaigns` yet. `accepted_campaignid` is the flat campaign the request created (set from the start, not only after acceptance). `status`: pending, accepted, rejected, cancelled. Account-wide: refused (403) for campaign-pinned keys.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\MarketplaceApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1MarketplaceFlatRequestsGet();
} catch (Exception $e) {
    echo 'Exception when calling MarketplaceApi->apiV1MarketplaceFlatRequestsGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1MarketplaceFlatRequestsIdCancelPost()`

```php
apiV1MarketplaceFlatRequestsIdCancelPost($id)
```

Withdraw a pending flat-deal request.

Only a `pending` request of your own. Also soft-deletes the (never-served) flat campaign the request created, and tells the network admins the request is withdrawn. Pinned keys / own-scoped members are checked against that campaign. Same handler as the panel. No body.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\MarketplaceApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1MarketplaceFlatRequestsIdCancelPost($id);
} catch (Exception $e) {
    echo 'Exception when calling MarketplaceApi->apiV1MarketplaceFlatRequestsIdCancelPost: ', $e->getMessage(), PHP_EOL;
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
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1MarketplaceFlatRequestsRenewPost()`

```php
apiV1MarketplaceFlatRequestsRenewPost($api_v1_marketplace_flat_requests_renew_post_request)
```

Propose one more flat deal on a live flat campaign.

Appends a deal (new date window + price) to a flat campaign (`paytype` 5) that already has an accepted deal, and files it in the admin's Accept / Reject queue (`deal_state` 0 = pending). Only one renewal may be pending at a time and the window must start after the last deal's end and not in the past. `total_costs` is the deal amount in **currency units** and must be ≥ the tenant's `flat_request_min_total` (GET /api/v1/marketplace/filters). Same handler as the panel; campaign pin and member own-scope apply.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\MarketplaceApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_marketplace_flat_requests_renew_post_request = {"campaign_id":90412,"date_start":"2026-11-01","date_end":"2026-11-30","total_costs":1500}; // \GoAdServerApi\Model\ApiV1MarketplaceFlatRequestsRenewPostRequest

try {
    $apiInstance->apiV1MarketplaceFlatRequestsRenewPost($api_v1_marketplace_flat_requests_renew_post_request);
} catch (Exception $e) {
    echo 'Exception when calling MarketplaceApi->apiV1MarketplaceFlatRequestsRenewPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_marketplace_flat_requests_renew_post_request** | [**\GoAdServerApi\Model\ApiV1MarketplaceFlatRequestsRenewPostRequest**](../Model/ApiV1MarketplaceFlatRequestsRenewPostRequest.md)|  | |

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

## `apiV1MarketplaceGet()`

```php
apiV1MarketplaceGet($adtype, $format, $adzone_type, $main_category, $countries, $device_type, $search, $include_ad_shop, $per_page)
```

List marketplace sites.

Publisher sites the network admin put in the marketplace (`marketplace_websites`), each with its matching zone count and last-period views / clicks. Offer zones (ad type 21) never appear. Same handler as the panel. All matching sites are returned in one page (`last_page` is always 1).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\MarketplaceApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$adtype = 56; // int | Ad type id (GET /api/v1/marketplace/filters → ad_types).
$format = 56; // int | Banner size id; only with adtype=2.
$adzone_type = 56; // int | Ad zone type id; implies its ad type when adtype is not sent.
$main_category = 56; // int
$countries = array('countries_example'); // string[] | ISO-2 codes; narrows the traffic stats.
$device_type = 56; // int
$search = 'search_example'; // string | Site name / URL substring.
$include_ad_shop = 'include_ad_shop_example'; // string | Also list sites the Ad Shop generator found eligible traffic on.
$per_page = 40; // int

try {
    $apiInstance->apiV1MarketplaceGet($adtype, $format, $adzone_type, $main_category, $countries, $device_type, $search, $include_ad_shop, $per_page);
} catch (Exception $e) {
    echo 'Exception when calling MarketplaceApi->apiV1MarketplaceGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **adtype** | **int**| Ad type id (GET /api/v1/marketplace/filters → ad_types). | [optional] |
| **format** | **int**| Banner size id; only with adtype&#x3D;2. | [optional] |
| **adzone_type** | **int**| Ad zone type id; implies its ad type when adtype is not sent. | [optional] |
| **main_category** | **int**|  | [optional] |
| **countries** | [**string[]**](../Model/string.md)| ISO-2 codes; narrows the traffic stats. | [optional] |
| **device_type** | **int**|  | [optional] |
| **search** | **string**| Site name / URL substring. | [optional] |
| **include_ad_shop** | **string**| Also list sites the Ad Shop generator found eligible traffic on. | [optional] |
| **per_page** | **int**|  | [optional] [default to 40] |

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

## `apiV1MarketplaceIdGet()`

```php
apiV1MarketplaceIdGet($id, $adtype, $format, $adzone_type, $countries, $device_type, $os, $browser, $include_ad_shop)
```

Ad zones of one marketplace site, with starting prices.

The zones the admin exposed on the site, filtered by ad type / format, each with its traffic (`stats`: clicks for click ad types, views otherwise — see `stat_type`) and a starting price `min_price`. `min_price` is in **cents** (the panel shows `min_price / 100` in the account currency); for CPM zones it is per 1000 views. It comes from recent market rates, else the zone's own per-country floor (when `countries[]` matches), else the network floor, else a built-in default. Same handler as the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\MarketplaceApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Site id from GET /api/v1/marketplace.
$adtype = 56; // int
$format = 56; // int
$adzone_type = 56; // int
$countries = array('countries_example'); // string[]
$device_type = 56; // int
$os = array(56); // int[]
$browser = array(56); // int[]
$include_ad_shop = 'include_ad_shop_example'; // string

try {
    $apiInstance->apiV1MarketplaceIdGet($id, $adtype, $format, $adzone_type, $countries, $device_type, $os, $browser, $include_ad_shop);
} catch (Exception $e) {
    echo 'Exception when calling MarketplaceApi->apiV1MarketplaceIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Site id from GET /api/v1/marketplace. | |
| **adtype** | **int**|  | [optional] |
| **format** | **int**|  | [optional] |
| **adzone_type** | **int**|  | [optional] |
| **countries** | [**string[]**](../Model/string.md)|  | [optional] |
| **device_type** | **int**|  | [optional] |
| **os** | [**int[]**](../Model/int.md)|  | [optional] |
| **browser** | [**int[]**](../Model/int.md)|  | [optional] |
| **include_ad_shop** | **string**|  | [optional] |

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
