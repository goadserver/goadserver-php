# GoAdServerApi\TagsApi

The tag tree campaigns and ad zones are filed under.

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1TagsGet()**](TagsApi.md#apiV1TagsGet) | **GET** /api/v1/tags | List tags. |
| [**apiV1TagsIdDelete()**](TagsApi.md#apiV1TagsIdDelete) | **DELETE** /api/v1/tags/{id} | Delete a tag. |
| [**apiV1TagsIdGet()**](TagsApi.md#apiV1TagsIdGet) | **GET** /api/v1/tags/{id} | Show a tag. |
| [**apiV1TagsIdPut()**](TagsApi.md#apiV1TagsIdPut) | **PUT** /api/v1/tags/{id} | Rename or move a tag. |
| [**apiV1TagsMassDeletePost()**](TagsApi.md#apiV1TagsMassDeletePost) | **POST** /api/v1/tags/mass-delete | Delete several tags. |
| [**apiV1TagsPost()**](TagsApi.md#apiV1TagsPost) | **POST** /api/v1/tags | Create a tag. |


## `apiV1TagsGet()`

```php
apiV1TagsGet($page, $per_page, $search)
```

List tags.

The account's tags with their parent (`pid`, 0 = top level) and depth (`level`). `search` / `filter_name` match the name. Assign a tag to campaigns with `groupid` on PATCH /api/v1/campaigns/{type}/{id} or the bulk PUT /api/v1/campaigns/save-tags.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TagsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$page = 1; // int
$per_page = '20'; // string | Max 100, or `all`.
$search = 'search_example'; // string

try {
    $apiInstance->apiV1TagsGet($page, $per_page, $search);
} catch (Exception $e) {
    echo 'Exception when calling TagsApi->apiV1TagsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **string**| Max 100, or &#x60;all&#x60;. | [optional] [default to &#39;20&#39;] |
| **search** | **string**|  | [optional] |

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

## `apiV1TagsIdDelete()`

```php
apiV1TagsIdDelete($id)
```

Delete a tag.

Same clean-up as mass-delete. Refused for a campaign-pinned key.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TagsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1TagsIdDelete($id);
} catch (Exception $e) {
    echo 'Exception when calling TagsApi->apiV1TagsIdDelete: ', $e->getMessage(), PHP_EOL;
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

## `apiV1TagsIdGet()`

```php
apiV1TagsIdGet($id)
```

Show a tag.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TagsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1TagsIdGet($id);
} catch (Exception $e) {
    echo 'Exception when calling TagsApi->apiV1TagsIdGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1TagsIdPut()`

```php
apiV1TagsIdPut($id, $api_v1_tags_id_put_request)
```

Rename or move a tag.

`name` is required. Omit `pid` to keep the tag where it is; send it to move the tag (and its sub-tree) under another parent, 0 = top level. A move that makes a cycle or exceeds three levels → 422.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TagsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_tags_id_put_request = {"name":"Push campaigns","pid":0}; // \GoAdServerApi\Model\ApiV1TagsIdPutRequest

try {
    $apiInstance->apiV1TagsIdPut($id, $api_v1_tags_id_put_request);
} catch (Exception $e) {
    echo 'Exception when calling TagsApi->apiV1TagsIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_tags_id_put_request** | [**\GoAdServerApi\Model\ApiV1TagsIdPutRequest**](../Model/ApiV1TagsIdPutRequest.md)|  | |

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

## `apiV1TagsMassDeletePost()`

```php
apiV1TagsMassDeletePost($api_v1_tags_mass_delete_post_request)
```

Delete several tags.

Removes the tags and strips their ids from every campaign / adzone that carried them (`refs_cleaned_up` counts the rewritten rows); sub-tags move up. Refused for a campaign-pinned key (it rewrites campaigns outside the pin).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TagsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_tags_mass_delete_post_request = {"ids":[6,7]}; // \GoAdServerApi\Model\ApiV1TagsMassDeletePostRequest

try {
    $apiInstance->apiV1TagsMassDeletePost($api_v1_tags_mass_delete_post_request);
} catch (Exception $e) {
    echo 'Exception when calling TagsApi->apiV1TagsMassDeletePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_tags_mass_delete_post_request** | [**\GoAdServerApi\Model\ApiV1TagsMassDeletePostRequest**](../Model/ApiV1TagsMassDeletePostRequest.md)|  | |

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

## `apiV1TagsPost()`

```php
apiV1TagsPost($api_v1_tags_post_request)
```

Create a tag.

A name that already exists under the same parent is not duplicated: the existing tag comes back with 200 and `existing: true`. `pid` must be one of the caller's tags and the tree may not exceed three levels (422).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TagsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_tags_post_request = {"name":"Push","pid":5}; // \GoAdServerApi\Model\ApiV1TagsPostRequest

try {
    $apiInstance->apiV1TagsPost($api_v1_tags_post_request);
} catch (Exception $e) {
    echo 'Exception when calling TagsApi->apiV1TagsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_tags_post_request** | [**\GoAdServerApi\Model\ApiV1TagsPostRequest**](../Model/ApiV1TagsPostRequest.md)|  | |

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
