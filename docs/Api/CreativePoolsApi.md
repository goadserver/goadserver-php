# GoAdServerApi\CreativePoolsApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1CreativePoolsGet()**](CreativePoolsApi.md#apiV1CreativePoolsGet) | **GET** /api/v1/creative-pools | List creative pools. |
| [**apiV1CreativePoolsIdClonePost()**](CreativePoolsApi.md#apiV1CreativePoolsIdClonePost) | **POST** /api/v1/creative-pools/{id}/clone | Clone a creative pool with all its items. |
| [**apiV1CreativePoolsIdDelete()**](CreativePoolsApi.md#apiV1CreativePoolsIdDelete) | **DELETE** /api/v1/creative-pools/{id} | Soft-delete a creative pool. |
| [**apiV1CreativePoolsIdGet()**](CreativePoolsApi.md#apiV1CreativePoolsIdGet) | **GET** /api/v1/creative-pools/{id} | Show a single creative pool. |
| [**apiV1CreativePoolsIdItemsGet()**](CreativePoolsApi.md#apiV1CreativePoolsIdItemsGet) | **GET** /api/v1/creative-pools/{id}/items | List items in a creative pool. |
| [**apiV1CreativePoolsIdItemsItemIdDelete()**](CreativePoolsApi.md#apiV1CreativePoolsIdItemsItemIdDelete) | **DELETE** /api/v1/creative-pools/{id}/items/{itemId} | Soft-delete a single pool item. |
| [**apiV1CreativePoolsIdItemsItemIdPut()**](CreativePoolsApi.md#apiV1CreativePoolsIdItemsItemIdPut) | **PUT** /api/v1/creative-pools/{id}/items/{itemId} | Update a single pool item. |
| [**apiV1CreativePoolsIdItemsItemIdTogglePut()**](CreativePoolsApi.md#apiV1CreativePoolsIdItemsItemIdTogglePut) | **PUT** /api/v1/creative-pools/{id}/items/{itemId}/toggle | Toggle a pool item on / off. |
| [**apiV1CreativePoolsIdPut()**](CreativePoolsApi.md#apiV1CreativePoolsIdPut) | **PUT** /api/v1/creative-pools/{id} | Update creative pool meta. |
| [**apiV1CreativePoolsIdUsedInGet()**](CreativePoolsApi.md#apiV1CreativePoolsIdUsedInGet) | **GET** /api/v1/creative-pools/{id}/used-in | List campaigns / ads that reference this pool. |
| [**apiV1CreativePoolsPost()**](CreativePoolsApi.md#apiV1CreativePoolsPost) | **POST** /api/v1/creative-pools | Create a creative pool. |


## `apiV1CreativePoolsGet()`

```php
apiV1CreativePoolsGet($type, $title, $page, $per_page, $sort): \GoAdServerApi\Model\ApiV1CreativePoolsGet200Response
```

List creative pools.

Returns every creative pool owned by the caller. Each row carries `items_count` (live items, `is_deleted=0`) and `total_sizes` (count of distinct banner sizes). Tags are returned both as the raw CSV string (`tags`) and as an array (`tagids`).  Supports `name`/`title`/`type`/`id` filter params plus the standard `page`, `per_page`, `sort`, `direction`, `q` search.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$title = 'title_example'; // string
$page = 1; // int
$per_page = 50; // int
$sort = 'id'; // string

try {
    $result = $apiInstance->apiV1CreativePoolsGet($type, $title, $page, $per_page, $sort);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | [optional] |
| **title** | **string**|  | [optional] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 50] |
| **sort** | **string**|  | [optional] [default to &#39;id&#39;] |

### Return type

[**\GoAdServerApi\Model\ApiV1CreativePoolsGet200Response**](../Model/ApiV1CreativePoolsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CreativePoolsIdClonePost()`

```php
apiV1CreativePoolsIdClonePost($id): \GoAdServerApi\Model\ApiV1CreativePoolsIdClonePost201Response
```

Clone a creative pool with all its items.

Duplicates the pool row and every live item (`is_deleted=0`). Cloned items share the same underlying `creative_id`, so the binary creative file is not re-uploaded — both pools reference the same row in the `creatives` table. The new pool's title is suffixed with \" (Copy)\".

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $result = $apiInstance->apiV1CreativePoolsIdClonePost($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdClonePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CreativePoolsIdClonePost201Response**](../Model/ApiV1CreativePoolsIdClonePost201Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CreativePoolsIdDelete()`

```php
apiV1CreativePoolsIdDelete($id): \GoAdServerApi\Model\ApiV1CreativePoolsIdDelete200Response
```

Soft-delete a creative pool.

Deletes the pool **and** every item that is not currently used by an ad. If any items are still referenced by live ads the call returns 409 with the per-item count and the pool itself is left intact — unhook the ads first, then retry.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $result = $apiInstance->apiV1CreativePoolsIdDelete($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CreativePoolsIdDelete200Response**](../Model/ApiV1CreativePoolsIdDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CreativePoolsIdGet()`

```php
apiV1CreativePoolsIdGet($id): \GoAdServerApi\Model\ApiV1CreativePoolsIdGet200Response
```

Show a single creative pool.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $result = $apiInstance->apiV1CreativePoolsIdGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CreativePoolsIdGet200Response**](../Model/ApiV1CreativePoolsIdGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CreativePoolsIdItemsGet()`

```php
apiV1CreativePoolsIdItemsGet($id, $page, $per_page): \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsGet200Response
```

List items in a creative pool.

Returns every banner / video / native item in the pool with size, creative metadata and lifetime stats (views, clicks, ctr, conv).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$page = 1; // int
$per_page = 50; // int

try {
    $result = $apiInstance->apiV1CreativePoolsIdItemsGet($id, $page, $per_page);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdItemsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 50] |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsGet200Response**](../Model/ApiV1CampaignsTypeCampaignIdAdsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CreativePoolsIdItemsItemIdDelete()`

```php
apiV1CreativePoolsIdItemsItemIdDelete($id, $item_id)
```

Soft-delete a single pool item.

Refuses if the item is still referenced by a live ad — unhook the ad first.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$item_id = 56; // int

try {
    $apiInstance->apiV1CreativePoolsIdItemsItemIdDelete($id, $item_id);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdItemsItemIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **item_id** | **int**|  | |

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

## `apiV1CreativePoolsIdItemsItemIdPut()`

```php
apiV1CreativePoolsIdItemsItemIdPut($id, $item_id, $api_v1_creative_pools_id_items_item_id_put_request)
```

Update a single pool item.

Editable fields: `title`, `lang`, `properties`, `ratingid`, `url_prefix`. Active state is toggled via the `/toggle` endpoint below.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$item_id = 56; // int
$api_v1_creative_pools_id_items_item_id_put_request = new \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsItemIdPutRequest(); // \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsItemIdPutRequest

try {
    $apiInstance->apiV1CreativePoolsIdItemsItemIdPut($id, $item_id, $api_v1_creative_pools_id_items_item_id_put_request);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdItemsItemIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **item_id** | **int**|  | |
| **api_v1_creative_pools_id_items_item_id_put_request** | [**\GoAdServerApi\Model\ApiV1CreativePoolsIdItemsItemIdPutRequest**](../Model/ApiV1CreativePoolsIdItemsItemIdPutRequest.md)|  | |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CreativePoolsIdItemsItemIdTogglePut()`

```php
apiV1CreativePoolsIdItemsItemIdTogglePut($id, $item_id)
```

Toggle a pool item on / off.

Flips `isactive` between 0 and 1. Inactive items are skipped by the ad-server when picking a creative from the pool.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$item_id = 56; // int

try {
    $apiInstance->apiV1CreativePoolsIdItemsItemIdTogglePut($id, $item_id);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdItemsItemIdTogglePut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **item_id** | **int**|  | |

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

## `apiV1CreativePoolsIdPut()`

```php
apiV1CreativePoolsIdPut($id, $api_v1_creative_pools_id_put_request)
```

Update creative pool meta.

Updates `title`, `sort_type`, `conv_type`, `tags`, or `type`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_creative_pools_id_put_request = new \GoAdServerApi\Model\ApiV1CreativePoolsIdPutRequest(); // \GoAdServerApi\Model\ApiV1CreativePoolsIdPutRequest

try {
    $apiInstance->apiV1CreativePoolsIdPut($id, $api_v1_creative_pools_id_put_request);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_creative_pools_id_put_request** | [**\GoAdServerApi\Model\ApiV1CreativePoolsIdPutRequest**](../Model/ApiV1CreativePoolsIdPutRequest.md)|  | |

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

## `apiV1CreativePoolsIdUsedInGet()`

```php
apiV1CreativePoolsIdUsedInGet($id): \GoAdServerApi\Model\ApiV1CreativePoolsIdUsedInGet200Response
```

List campaigns / ads that reference this pool.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $result = $apiInstance->apiV1CreativePoolsIdUsedInGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdUsedInGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CreativePoolsIdUsedInGet200Response**](../Model/ApiV1CreativePoolsIdUsedInGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CreativePoolsPost()`

```php
apiV1CreativePoolsPost($api_v1_creative_pools_post_request): \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response
```

Create a creative pool.

Creates an empty pool. Add items via the panel uploader (not yet exposed via API) or import-by-id endpoints once available. Title collisions are auto-suffixed with `_<unix>`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_creative_pools_post_request = new \GoAdServerApi\Model\ApiV1CreativePoolsPostRequest(); // \GoAdServerApi\Model\ApiV1CreativePoolsPostRequest

try {
    $result = $apiInstance->apiV1CreativePoolsPost($api_v1_creative_pools_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_creative_pools_post_request** | [**\GoAdServerApi\Model\ApiV1CreativePoolsPostRequest**](../Model/ApiV1CreativePoolsPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response**](../Model/ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
