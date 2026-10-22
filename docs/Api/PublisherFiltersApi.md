# GoAdServerApi\PublisherFiltersApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1PublisherFiltersGet()**](PublisherFiltersApi.md#apiV1PublisherFiltersGet) | **GET** /api/v1/publisher/filters | List the publisher&#39;s URL block list. |
| [**apiV1PublisherFiltersUrlsDelete()**](PublisherFiltersApi.md#apiV1PublisherFiltersUrlsDelete) | **DELETE** /api/v1/publisher/filters/urls | Remove one or more URLs from the block list. |
| [**apiV1PublisherFiltersUrlsPost()**](PublisherFiltersApi.md#apiV1PublisherFiltersUrlsPost) | **POST** /api/v1/publisher/filters/urls | Add one or more URLs to the block list. |


## `apiV1PublisherFiltersGet()`

```php
apiV1PublisherFiltersGet(): \GoAdServerApi\Model\ApiV1PublisherFiltersGet200Response
```

List the publisher's URL block list.

**URLs** — global block list applied to every adspace this publisher owns. Stored as a CSV in `usersettings.excldomains`. Returned as a flat array of `{url, type}` entries.  **`categories` is deprecated and always empty.** Category filters were retired, along with the four `/filters/categories` endpoints that managed them. The key is still returned so existing callers keep parsing the response unchanged; do not build against it.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PublisherFiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1PublisherFiltersGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PublisherFiltersApi->apiV1PublisherFiltersGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherFiltersGet200Response**](../Model/ApiV1PublisherFiltersGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherFiltersUrlsDelete()`

```php
apiV1PublisherFiltersUrlsDelete($api_v1_publisher_filters_urls_delete_request): \GoAdServerApi\Model\ApiV1PublisherFiltersUrlsDelete200Response
```

Remove one or more URLs from the block list.

Body accepts `url` (single) or `urls` (array). Matching is case-insensitive. Unknown URLs are silently ignored.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PublisherFiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_publisher_filters_urls_delete_request = new \GoAdServerApi\Model\ApiV1PublisherFiltersUrlsDeleteRequest(); // \GoAdServerApi\Model\ApiV1PublisherFiltersUrlsDeleteRequest

try {
    $result = $apiInstance->apiV1PublisherFiltersUrlsDelete($api_v1_publisher_filters_urls_delete_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PublisherFiltersApi->apiV1PublisherFiltersUrlsDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_publisher_filters_urls_delete_request** | [**\GoAdServerApi\Model\ApiV1PublisherFiltersUrlsDeleteRequest**](../Model/ApiV1PublisherFiltersUrlsDeleteRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherFiltersUrlsDelete200Response**](../Model/ApiV1PublisherFiltersUrlsDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherFiltersUrlsPost()`

```php
apiV1PublisherFiltersUrlsPost($api_v1_publisher_filters_urls_post_request): \GoAdServerApi\Model\ApiV1PublisherFiltersUrlsPost201Response
```

Add one or more URLs to the block list.

Adds URLs to `usersettings.excldomains` (deduped). Body accepts either `url` (single) or `urls` (array). Existing entries stay.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PublisherFiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_publisher_filters_urls_post_request = new \GoAdServerApi\Model\ApiV1PublisherFiltersUrlsPostRequest(); // \GoAdServerApi\Model\ApiV1PublisherFiltersUrlsPostRequest

try {
    $result = $apiInstance->apiV1PublisherFiltersUrlsPost($api_v1_publisher_filters_urls_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PublisherFiltersApi->apiV1PublisherFiltersUrlsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_publisher_filters_urls_post_request** | [**\GoAdServerApi\Model\ApiV1PublisherFiltersUrlsPostRequest**](../Model/ApiV1PublisherFiltersUrlsPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherFiltersUrlsPost201Response**](../Model/ApiV1PublisherFiltersUrlsPost201Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
