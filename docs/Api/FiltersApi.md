# GoAdServerApi\FiltersApi

Domain / ISP / adzone / SubID / IP filters — read, toggle on/off, delete.

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1FiltersGet()**](FiltersApi.md#apiV1FiltersGet) | **GET** /api/v1/filters | List all filters across types. |
| [**apiV1FiltersPost()**](FiltersApi.md#apiV1FiltersPost) | **POST** /api/v1/filters | Create a filter and populate it with items. |
| [**apiV1FiltersTypeIdActivePatch()**](FiltersApi.md#apiV1FiltersTypeIdActivePatch) | **PATCH** /api/v1/filters/{type}/{id}/active | Activate or deactivate a filter (exclusion types only). |
| [**apiV1FiltersTypeIdDelete()**](FiltersApi.md#apiV1FiltersTypeIdDelete) | **DELETE** /api/v1/filters/{type}/{id} | Delete a filter (and its items, for IP pools). |
| [**apiV1FiltersTypeIdGet()**](FiltersApi.md#apiV1FiltersTypeIdGet) | **GET** /api/v1/filters/{type}/{id} | Get a single filter with its items. |
| [**apiV1FiltersTypeIdItemsItemDelete()**](FiltersApi.md#apiV1FiltersTypeIdItemsItemDelete) | **DELETE** /api/v1/filters/{type}/{id}/items/{item} | Remove a single item from a filter. |
| [**apiV1FiltersTypeIdItemsPost()**](FiltersApi.md#apiV1FiltersTypeIdItemsPost) | **POST** /api/v1/filters/{type}/{id}/items | Add items to an existing filter. |


## `apiV1FiltersGet()`

```php
apiV1FiltersGet($type): \GoAdServerApi\Model\ApiV1FiltersGet200Response
```

List all filters across types.

Each filter has a **type** and may apply globally or to specific campaigns. Types:  | type          | meaning                          | toggleable | |---------------|----------------------------------|:----------:| | `domain_excl` | Block these domains              | yes | | `domain_incl` | Only allow these domains         | no | | `adzone_excl` | Block these ad zones             | yes | | `adzone_incl` | Only allow these ad zones        | no | | `isp_excl`    | Block these ISPs                 | yes | | `subid_excl`  | Block these SubIDs               | yes | | `subid_incl`  | Only allow these SubIDs          | no | | `ip`          | IP-pool blocking                 | no |

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string | Filter to one type.

try {
    $result = $apiInstance->apiV1FiltersGet($type);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FiltersApi->apiV1FiltersGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**| Filter to one type. | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1FiltersGet200Response**](../Model/ApiV1FiltersGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FiltersPost()`

```php
apiV1FiltersPost($api_v1_filters_post_request): \GoAdServerApi\Model\FilterDetail
```

Create a filter and populate it with items.

For `domain_excl` / `domain_incl`: items can be domain URLs (`\"https://evil.com/x\"`) — automatically resolved to internal domain IDs — or numeric domain IDs as strings. For `adzone_excl` / `adzone_incl` / `isp_excl`: items must be numeric IDs. For `subid_excl` / `subid_incl`: items are raw SubID strings. IP pool creation is not yet exposed via the public API.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_filters_post_request = new \GoAdServerApi\Model\ApiV1FiltersPostRequest(); // \GoAdServerApi\Model\ApiV1FiltersPostRequest

try {
    $result = $apiInstance->apiV1FiltersPost($api_v1_filters_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FiltersApi->apiV1FiltersPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_filters_post_request** | [**\GoAdServerApi\Model\ApiV1FiltersPostRequest**](../Model/ApiV1FiltersPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\FilterDetail**](../Model/FilterDetail.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FiltersTypeIdActivePatch()`

```php
apiV1FiltersTypeIdActivePatch($type, $id, $api_v1_campaigns_type_id_active_patch_request): \GoAdServerApi\Model\ApiV1FiltersTypeIdActivePatch200Response
```

Activate or deactivate a filter (exclusion types only).

Only `*_excl` filters have an active/inactive state. Inclusion filters and IP pools always apply when a campaign references them — disable by removing the reference instead.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$id = 56; // int
$api_v1_campaigns_type_id_active_patch_request = new \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest(); // \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest

try {
    $result = $apiInstance->apiV1FiltersTypeIdActivePatch($type, $id, $api_v1_campaigns_type_id_active_patch_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FiltersApi->apiV1FiltersTypeIdActivePatch: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **id** | **int**|  | |
| **api_v1_campaigns_type_id_active_patch_request** | [**\GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest**](../Model/ApiV1CampaignsTypeIdActivePatchRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1FiltersTypeIdActivePatch200Response**](../Model/ApiV1FiltersTypeIdActivePatch200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FiltersTypeIdDelete()`

```php
apiV1FiltersTypeIdDelete($type, $id)
```

Delete a filter (and its items, for IP pools).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$id = 56; // int

try {
    $apiInstance->apiV1FiltersTypeIdDelete($type, $id);
} catch (Exception $e) {
    echo 'Exception when calling FiltersApi->apiV1FiltersTypeIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **id** | **int**|  | |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FiltersTypeIdGet()`

```php
apiV1FiltersTypeIdGet($type, $id): \GoAdServerApi\Model\FilterDetail
```

Get a single filter with its items.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$id = 56; // int

try {
    $result = $apiInstance->apiV1FiltersTypeIdGet($type, $id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FiltersApi->apiV1FiltersTypeIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\FilterDetail**](../Model/FilterDetail.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FiltersTypeIdItemsItemDelete()`

```php
apiV1FiltersTypeIdItemsItemDelete($type, $id, $item): \GoAdServerApi\Model\ApiV1FiltersTypeIdItemsItemDelete200Response
```

Remove a single item from a filter.

`{item}` is the stored value — domain ID, adzone ID, ISP ID, or SubID string. URL-encode special characters.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$id = 56; // int
$item = 'item_example'; // string

try {
    $result = $apiInstance->apiV1FiltersTypeIdItemsItemDelete($type, $id, $item);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FiltersApi->apiV1FiltersTypeIdItemsItemDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **id** | **int**|  | |
| **item** | **string**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1FiltersTypeIdItemsItemDelete200Response**](../Model/ApiV1FiltersTypeIdItemsItemDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FiltersTypeIdItemsPost()`

```php
apiV1FiltersTypeIdItemsPost($type, $id, $api_v1_filters_type_id_items_post_request): \GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPost200Response
```

Add items to an existing filter.

Same input format as creating a filter — domain URLs are auto-resolved to IDs for domain filters, numeric IDs required for adzone/ISP filters, raw strings for SubID filters. Already-present items are deduplicated silently.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$id = 56; // int
$api_v1_filters_type_id_items_post_request = new \GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPostRequest(); // \GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPostRequest

try {
    $result = $apiInstance->apiV1FiltersTypeIdItemsPost($type, $id, $api_v1_filters_type_id_items_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FiltersApi->apiV1FiltersTypeIdItemsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **id** | **int**|  | |
| **api_v1_filters_type_id_items_post_request** | [**\GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPostRequest**](../Model/ApiV1FiltersTypeIdItemsPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPost200Response**](../Model/ApiV1FiltersTypeIdItemsPost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
