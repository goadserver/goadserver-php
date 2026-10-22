# GoAdServerApi\XMLFeedsApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1XmlFeedsGet()**](XMLFeedsApi.md#apiV1XmlFeedsGet) | **GET** /api/v1/xml-feeds | List XML feeds. |
| [**apiV1XmlFeedsIdClonePost()**](XMLFeedsApi.md#apiV1XmlFeedsIdClonePost) | **POST** /api/v1/xml-feeds/{id}/clone | Clone an XML feed. |
| [**apiV1XmlFeedsIdDelete()**](XMLFeedsApi.md#apiV1XmlFeedsIdDelete) | **DELETE** /api/v1/xml-feeds/{id} | Delete an XML feed. |
| [**apiV1XmlFeedsIdGet()**](XMLFeedsApi.md#apiV1XmlFeedsIdGet) | **GET** /api/v1/xml-feeds/{id} | Show an XML feed. |
| [**apiV1XmlFeedsIdPut()**](XMLFeedsApi.md#apiV1XmlFeedsIdPut) | **PUT** /api/v1/xml-feeds/{id} | Update an XML feed. |
| [**apiV1XmlFeedsIdTogglePut()**](XMLFeedsApi.md#apiV1XmlFeedsIdTogglePut) | **PUT** /api/v1/xml-feeds/{id}/toggle | Flip an XML feed active / paused. |
| [**apiV1XmlFeedsPost()**](XMLFeedsApi.md#apiV1XmlFeedsPost) | **POST** /api/v1/xml-feeds | Create an XML feed. |


## `apiV1XmlFeedsGet()`

```php
apiV1XmlFeedsGet($page, $per_page, $search, $sort_by, $sort_dir)
```

List XML feeds.

The account's product feeds. `lastvisit` / `nextvisit` are the last and next fetch times (null before the first fetch). `filter_name`, `filter_url` (substring), `filter_id` and `search` narrow the list; `sort_by` = id / name / isactive, `sort_dir` = asc / desc. Same handler as the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\XMLFeedsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$page = 1; // int
$per_page = 20; // int
$search = 'search_example'; // string
$sort_by = 'sort_by_example'; // string
$sort_dir = 'sort_dir_example'; // string

try {
    $apiInstance->apiV1XmlFeedsGet($page, $per_page, $search, $sort_by, $sort_dir);
} catch (Exception $e) {
    echo 'Exception when calling XMLFeedsApi->apiV1XmlFeedsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 20] |
| **search** | **string**|  | [optional] |
| **sort_by** | **string**|  | [optional] |
| **sort_dir** | **string**|  | [optional] |

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

## `apiV1XmlFeedsIdClonePost()`

```php
apiV1XmlFeedsIdClonePost($id)
```

Clone an XML feed.

Copies the feed as \"<name> Copy\", paused. No body.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\XMLFeedsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1XmlFeedsIdClonePost($id);
} catch (Exception $e) {
    echo 'Exception when calling XMLFeedsApi->apiV1XmlFeedsIdClonePost: ', $e->getMessage(), PHP_EOL;
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

## `apiV1XmlFeedsIdDelete()`

```php
apiV1XmlFeedsIdDelete($id)
```

Delete an XML feed.

Permanent (the row is removed, as in the panel). Refused with 422 `feed_in_use` while any of your non-deleted campaigns still uses the feed (`feedid`) — the response lists them (up to 50); take the feed off those campaigns (or delete them) first. Refused for a campaign-pinned key.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\XMLFeedsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1XmlFeedsIdDelete($id);
} catch (Exception $e) {
    echo 'Exception when calling XMLFeedsApi->apiV1XmlFeedsIdDelete: ', $e->getMessage(), PHP_EOL;
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

## `apiV1XmlFeedsIdGet()`

```php
apiV1XmlFeedsIdGet($id)
```

Show an XML feed.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\XMLFeedsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1XmlFeedsIdGet($id);
} catch (Exception $e) {
    echo 'Exception when calling XMLFeedsApi->apiV1XmlFeedsIdGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1XmlFeedsIdPut()`

```php
apiV1XmlFeedsIdPut($id, $api_v1_xml_feeds_id_put_request)
```

Update an XML feed.

Partial: only the fields sent change. `revisitminutes` is raised to 5 at least; `isactive` 0/1 pauses or resumes fetching. A feed is shared by every campaign that uses it, so a key pinned to campaigns gets 403 `forbidden_resource`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\XMLFeedsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_xml_feeds_id_put_request = {"revisitminutes":30}; // \GoAdServerApi\Model\ApiV1XmlFeedsIdPutRequest

try {
    $apiInstance->apiV1XmlFeedsIdPut($id, $api_v1_xml_feeds_id_put_request);
} catch (Exception $e) {
    echo 'Exception when calling XMLFeedsApi->apiV1XmlFeedsIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_xml_feeds_id_put_request** | [**\GoAdServerApi\Model\ApiV1XmlFeedsIdPutRequest**](../Model/ApiV1XmlFeedsIdPutRequest.md)|  | |

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

## `apiV1XmlFeedsIdTogglePut()`

```php
apiV1XmlFeedsIdTogglePut($id)
```

Flip an XML feed active / paused.

No body — flips `isactive`. Refused for a campaign-pinned key.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\XMLFeedsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1XmlFeedsIdTogglePut($id);
} catch (Exception $e) {
    echo 'Exception when calling XMLFeedsApi->apiV1XmlFeedsIdTogglePut: ', $e->getMessage(), PHP_EOL;
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

## `apiV1XmlFeedsPost()`

```php
apiV1XmlFeedsPost($api_v1_xml_feeds_post_request)
```

Create an XML feed.

The paths are the XML element paths the fetcher reads per item. `revisitminutes` (how often the feed is re-fetched) is raised to the minimum of 5. The feed starts active. Unknown body fields → 422 `unknown_fields`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\XMLFeedsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_xml_feeds_post_request = {"name":"Shop feed","url":"https://shop.example.com/feed.xml","itemspath":"rss/channel/item","titlepath":"title","descriptionpath":"description","mediapath":"enclosure@url","linkpath":"link","revisitminutes":60}; // \GoAdServerApi\Model\ApiV1XmlFeedsPostRequest

try {
    $apiInstance->apiV1XmlFeedsPost($api_v1_xml_feeds_post_request);
} catch (Exception $e) {
    echo 'Exception when calling XMLFeedsApi->apiV1XmlFeedsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_xml_feeds_post_request** | [**\GoAdServerApi\Model\ApiV1XmlFeedsPostRequest**](../Model/ApiV1XmlFeedsPostRequest.md)|  | |

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
