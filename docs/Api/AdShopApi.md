# GoAdServerApi\AdShopApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1AdShopCartGet()**](AdShopApi.md#apiV1AdShopCartGet) | **GET** /api/v1/ad-shop/cart | Read the draft cart. |
| [**apiV1AdShopCartPost()**](AdShopApi.md#apiV1AdShopCartPost) | **POST** /api/v1/ad-shop/cart | Replace the draft cart. |
| [**apiV1AdShopConfirmPost()**](AdShopApi.md#apiV1AdShopConfirmPost) | **POST** /api/v1/ad-shop/confirm | Check out the cart — creates the campaigns. |
| [**apiV1AdShopFilterOptionsGet()**](AdShopApi.md#apiV1AdShopFilterOptionsGet) | **GET** /api/v1/ad-shop/filter-options | Filter values present in the current snapshot. |
| [**apiV1AdShopGroupSummaryPost()**](AdShopApi.md#apiV1AdShopGroupSummaryPost) | **POST** /api/v1/ad-shop/group-summary | Group the catalog by targeting dimensions. |
| [**apiV1AdShopOpportunitiesGet()**](AdShopApi.md#apiV1AdShopOpportunitiesGet) | **GET** /api/v1/ad-shop/opportunities | Browse Ad Shop opportunities. |
| [**apiV1AdShopPlanGet()**](AdShopApi.md#apiV1AdShopPlanGet) | **GET** /api/v1/ad-shop/plan | Preview how the cart becomes campaigns. |


## `apiV1AdShopCartGet()`

```php
apiV1AdShopCartGet()
```

Read the draft cart.

The current draft cart of the person acting (one for the account owner, and a separate one for each team member, so nobody confirms items someone else picked). Same handler as the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdShopApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1AdShopCartGet();
} catch (Exception $e) {
    echo 'Exception when calling AdShopApi->apiV1AdShopCartGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1AdShopCartPost()`

```php
apiV1AdShopCartPost($api_v1_ad_shop_cart_post_request)
```

Replace the draft cart.

Stores the full list of picked opportunity ids for a snapshot (replaces, not appends). Same handler as the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdShopApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_ad_shop_cart_post_request = {"snapshot_id":"20261012-0400","opportunity_ids":[77120,77121]}; // \GoAdServerApi\Model\ApiV1AdShopCartPostRequest

try {
    $apiInstance->apiV1AdShopCartPost($api_v1_ad_shop_cart_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AdShopApi->apiV1AdShopCartPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_ad_shop_cart_post_request** | [**\GoAdServerApi\Model\ApiV1AdShopCartPostRequest**](../Model/ApiV1AdShopCartPostRequest.md)|  | |

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

## `apiV1AdShopConfirmPost()`

```php
apiV1AdShopConfirmPost($api_v1_ad_shop_confirm_post_request)
```

Check out the cart — creates the campaigns.

Creates the RON campaigns of the plan (live and able to spend once they have ads) and records an order. `idempotency_key` is required: a retry with the same key returns the first order (`reused: true`) instead of buying twice; keys are per account. Lines that need manual sourcing go to the network's fulfilment queue and the admins are alerted. With a flat window, a zone already booked in it fails the whole order (422). Keys pinned to campaigns cannot confirm (403): the new campaigns would be outside the pin. A team member with read-only campaign access gets 403 `forbidden` (also on `POST /api/v1/ad-shop/cart`). The campaigns are recorded as created by, and managed by, whoever confirms, so a team member limited to their own campaigns can open them afterwards. Same handler as the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdShopApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_ad_shop_confirm_post_request = {"idempotency_key":"9b2f1c3e-order-1"}; // \GoAdServerApi\Model\ApiV1AdShopConfirmPostRequest

try {
    $apiInstance->apiV1AdShopConfirmPost($api_v1_ad_shop_confirm_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AdShopApi->apiV1AdShopConfirmPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_ad_shop_confirm_post_request** | [**\GoAdServerApi\Model\ApiV1AdShopConfirmPostRequest**](../Model/ApiV1AdShopConfirmPostRequest.md)|  | |

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

## `apiV1AdShopFilterOptionsGet()`

```php
apiV1AdShopFilterOptionsGet()
```

Filter values present in the current snapshot.

Formats, pay types, countries, devices and categories that occur in the latest snapshot. Same handler as the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdShopApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1AdShopFilterOptionsGet();
} catch (Exception $e) {
    echo 'Exception when calling AdShopApi->apiV1AdShopFilterOptionsGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1AdShopGroupSummaryPost()`

```php
apiV1AdShopGroupSummaryPost($api_v1_ad_shop_group_summary_post_request)
```

Group the catalog by targeting dimensions.

Read-only (POST only because the filter set is large). Nests the filtered snapshot by up to 4 of `format`, `country`, `device`, `category`, `paytype`, with available views / clicks per group and, for `selected_ids`, the selected totals. Same handler as the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdShopApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_ad_shop_group_summary_post_request = {"groups":["country","format"],"filters":{"payTypes":[1]},"selected_ids":[77120]}; // \GoAdServerApi\Model\ApiV1AdShopGroupSummaryPostRequest

try {
    $apiInstance->apiV1AdShopGroupSummaryPost($api_v1_ad_shop_group_summary_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AdShopApi->apiV1AdShopGroupSummaryPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_ad_shop_group_summary_post_request** | [**\GoAdServerApi\Model\ApiV1AdShopGroupSummaryPostRequest**](../Model/ApiV1AdShopGroupSummaryPostRequest.md)|  | |

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

## `apiV1AdShopOpportunitiesGet()`

```php
apiV1AdShopOpportunitiesGet($formats, $pay_types, $categories, $devices, $countries, $search, $sort, $direction, $page, $per_page)
```

Browse Ad Shop opportunities.

Inventory lines from the tenant's latest opportunity snapshot, shopped by targeting (format × country × device × category × pay type) — never by site or zone. `rate` is the advertiser rate in **currency units** (per click for CPC, per 1000 views for CPM); `flat_price` (currency units) applies to flat lines. `availability_status`: available, reserved, manual_confirmation, auction, unavailable, expired. Same handler as the panel. 404 when the tenant's Ad Shop module is off.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdShopApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$formats = array(56); // int[] | Ad zone type or ad type ids (GET …/filter-options → formats).
$pay_types = array(56); // int[] | 0 CPC, 1 CPM, 5 flat.
$categories = array(56); // int[]
$devices = array(56); // int[]
$countries = array('countries_example'); // string[]
$search = 'search_example'; // string
$sort = 'sort_example'; // string
$direction = 'direction_example'; // string | `hot` with sort=status puts hot lines first.
$page = 1; // int
$per_page = 50; // int

try {
    $apiInstance->apiV1AdShopOpportunitiesGet($formats, $pay_types, $categories, $devices, $countries, $search, $sort, $direction, $page, $per_page);
} catch (Exception $e) {
    echo 'Exception when calling AdShopApi->apiV1AdShopOpportunitiesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **formats** | [**int[]**](../Model/int.md)| Ad zone type or ad type ids (GET …/filter-options → formats). | [optional] |
| **pay_types** | [**int[]**](../Model/int.md)| 0 CPC, 1 CPM, 5 flat. | [optional] |
| **categories** | [**int[]**](../Model/int.md)|  | [optional] |
| **devices** | [**int[]**](../Model/int.md)|  | [optional] |
| **countries** | [**string[]**](../Model/string.md)|  | [optional] |
| **search** | **string**|  | [optional] |
| **sort** | **string**|  | [optional] |
| **direction** | **string**| &#x60;hot&#x60; with sort&#x3D;status puts hot lines first. | [optional] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 50] |

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

## `apiV1AdShopPlanGet()`

```php
apiV1AdShopPlanGet($flat_start, $flat_end)
```

Preview how the cart becomes campaigns.

No writes. Splits the cart into the campaigns confirm would create (one per format × pay type …). `bid` and `flat_price` are in currency units. With a flat window (`flat_start` + `flat_end`) the lines are priced as flat bookings and `conflicts` lists zones already booked in that window. Same handler as the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdShopApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$flat_start = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime
$flat_end = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime

try {
    $apiInstance->apiV1AdShopPlanGet($flat_start, $flat_end);
} catch (Exception $e) {
    echo 'Exception when calling AdShopApi->apiV1AdShopPlanGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **flat_start** | **\DateTime**|  | [optional] |
| **flat_end** | **\DateTime**|  | [optional] |

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
