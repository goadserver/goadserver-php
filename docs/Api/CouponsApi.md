# GoAdServerApi\CouponsApi

Coupon sets and their generated codes.

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1CouponsSetsGet()**](CouponsApi.md#apiV1CouponsSetsGet) | **GET** /api/v1/coupons/sets | List coupon sets. |
| [**apiV1CouponsSetsIdDelete()**](CouponsApi.md#apiV1CouponsSetsIdDelete) | **DELETE** /api/v1/coupons/sets/{id} | Delete a set and all its codes. |
| [**apiV1CouponsSetsIdExportGet()**](CouponsApi.md#apiV1CouponsSetsIdExportGet) | **GET** /api/v1/coupons/sets/{id}/export | Export a set&#39;s codes as CSV. |
| [**apiV1CouponsSetsIdGeneratePost()**](CouponsApi.md#apiV1CouponsSetsIdGeneratePost) | **POST** /api/v1/coupons/sets/{id}/generate | Generate codes in a set. |
| [**apiV1CouponsSetsIdPut()**](CouponsApi.md#apiV1CouponsSetsIdPut) | **PUT** /api/v1/coupons/sets/{id} | Rename a set or move its end date. |
| [**apiV1CouponsSetsPost()**](CouponsApi.md#apiV1CouponsSetsPost) | **POST** /api/v1/coupons/sets | Create a coupon set. |
| [**apiV1CouponsSetsSetIdCouponsGet()**](CouponsApi.md#apiV1CouponsSetsSetIdCouponsGet) | **GET** /api/v1/coupons/sets/{setId}/coupons | List the codes in a set. |


## `apiV1CouponsSetsGet()`

```php
apiV1CouponsSetsGet($page, $per_page, $search, $sort_by)
```

List coupon sets.

Each set carries `coupon_count` (non-deleted codes). `search` matches the name.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CouponsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$page = 1; // int
$per_page = 20; // int
$search = 'search_example'; // string
$sort_by = 'sort_by_example'; // string

try {
    $apiInstance->apiV1CouponsSetsGet($page, $per_page, $search, $sort_by);
} catch (Exception $e) {
    echo 'Exception when calling CouponsApi->apiV1CouponsSetsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 20] |
| **search** | **string**|  | [optional] |
| **sort_by** | **string**|  | [optional] |

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

## `apiV1CouponsSetsIdDelete()`

```php
apiV1CouponsSetsIdDelete($id)
```

Delete a set and all its codes.

Soft delete of the set and every code in it.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CouponsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1CouponsSetsIdDelete($id);
} catch (Exception $e) {
    echo 'Exception when calling CouponsApi->apiV1CouponsSetsIdDelete: ', $e->getMessage(), PHP_EOL;
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

## `apiV1CouponsSetsIdExportGet()`

```php
apiV1CouponsSetsIdExportGet($id)
```

Export a set's codes as CSV.

`text/csv` attachment `coupons-<id>.csv`, no header row; one line per code: `code,amount,end_date`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CouponsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1CouponsSetsIdExportGet($id);
} catch (Exception $e) {
    echo 'Exception when calling CouponsApi->apiV1CouponsSetsIdExportGet: ', $e->getMessage(), PHP_EOL;
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
- **Accept**: `text/csv`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CouponsSetsIdGeneratePost()`

```php
apiV1CouponsSetsIdGeneratePost($id, $api_v1_coupons_sets_id_generate_post_request)
```

Generate codes in a set.

Adds `count` new random codes (capped at 10000 per call), each worth `amount`. `amount` is a whole number stored as given — the panel shows it raw, so use the unit your redemption flow expects.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CouponsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_coupons_sets_id_generate_post_request = {"count":100,"amount":10}; // \GoAdServerApi\Model\ApiV1CouponsSetsIdGeneratePostRequest

try {
    $apiInstance->apiV1CouponsSetsIdGeneratePost($id, $api_v1_coupons_sets_id_generate_post_request);
} catch (Exception $e) {
    echo 'Exception when calling CouponsApi->apiV1CouponsSetsIdGeneratePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_coupons_sets_id_generate_post_request** | [**\GoAdServerApi\Model\ApiV1CouponsSetsIdGeneratePostRequest**](../Model/ApiV1CouponsSetsIdGeneratePostRequest.md)|  | |

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

## `apiV1CouponsSetsIdPut()`

```php
apiV1CouponsSetsIdPut($id, $api_v1_coupons_sets_id_put_request)
```

Rename a set or move its end date.

Partial — send `name`, `end_date` or both (empty values are ignored).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CouponsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_coupons_sets_id_put_request = {"end_date":"2027-01-31"}; // \GoAdServerApi\Model\ApiV1CouponsSetsIdPutRequest

try {
    $apiInstance->apiV1CouponsSetsIdPut($id, $api_v1_coupons_sets_id_put_request);
} catch (Exception $e) {
    echo 'Exception when calling CouponsApi->apiV1CouponsSetsIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_coupons_sets_id_put_request** | [**\GoAdServerApi\Model\ApiV1CouponsSetsIdPutRequest**](../Model/ApiV1CouponsSetsIdPutRequest.md)|  | |

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

## `apiV1CouponsSetsPost()`

```php
apiV1CouponsSetsPost($api_v1_coupons_sets_post_request)
```

Create a coupon set.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CouponsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_coupons_sets_post_request = {"name":"Autumn promo","end_date":"2026-12-31"}; // \GoAdServerApi\Model\ApiV1CouponsSetsPostRequest

try {
    $apiInstance->apiV1CouponsSetsPost($api_v1_coupons_sets_post_request);
} catch (Exception $e) {
    echo 'Exception when calling CouponsApi->apiV1CouponsSetsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_coupons_sets_post_request** | [**\GoAdServerApi\Model\ApiV1CouponsSetsPostRequest**](../Model/ApiV1CouponsSetsPostRequest.md)|  | |

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

## `apiV1CouponsSetsSetIdCouponsGet()`

```php
apiV1CouponsSetsSetIdCouponsGet($set_id, $page, $per_page, $filter_is_used)
```

List the codes in a set.

`filter_is_used` = 0 / 1, `filter_code` / `search` match the code; `sort_by` = id / code / amount / is_used / date_used.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CouponsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$set_id = 56; // int
$page = 1; // int
$per_page = 20; // int
$filter_is_used = 56; // int

try {
    $apiInstance->apiV1CouponsSetsSetIdCouponsGet($set_id, $page, $per_page, $filter_is_used);
} catch (Exception $e) {
    echo 'Exception when calling CouponsApi->apiV1CouponsSetsSetIdCouponsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **set_id** | **int**|  | |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 20] |
| **filter_is_used** | **int**|  | [optional] |

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
