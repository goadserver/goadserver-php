# GoAdServerApi\URLPoolsApi



All URIs are relative to https://up.goadserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1UrlPoolsGet()**](URLPoolsApi.md#apiV1UrlPoolsGet) | **GET** /api/v1/url-pools | List URL pools. |
| [**apiV1UrlPoolsIdClonePost()**](URLPoolsApi.md#apiV1UrlPoolsIdClonePost) | **POST** /api/v1/url-pools/{id}/clone | Clone a URL pool with all its URLs. |
| [**apiV1UrlPoolsIdDelete()**](URLPoolsApi.md#apiV1UrlPoolsIdDelete) | **DELETE** /api/v1/url-pools/{id} | Soft-delete a URL pool. |
| [**apiV1UrlPoolsIdGet()**](URLPoolsApi.md#apiV1UrlPoolsIdGet) | **GET** /api/v1/url-pools/{id} | Show one URL pool. |
| [**apiV1UrlPoolsIdPut()**](URLPoolsApi.md#apiV1UrlPoolsIdPut) | **PUT** /api/v1/url-pools/{id} | Update URL pool meta. |
| [**apiV1UrlPoolsIdUrlsGet()**](URLPoolsApi.md#apiV1UrlPoolsIdUrlsGet) | **GET** /api/v1/url-pools/{id}/urls | List URLs in a pool. |
| [**apiV1UrlPoolsIdUrlsPost()**](URLPoolsApi.md#apiV1UrlPoolsIdUrlsPost) | **POST** /api/v1/url-pools/{id}/urls | Add a URL to a pool. |
| [**apiV1UrlPoolsIdUrlsUrlIdClonePost()**](URLPoolsApi.md#apiV1UrlPoolsIdUrlsUrlIdClonePost) | **POST** /api/v1/url-pools/{id}/urls/{urlId}/clone | Clone a single URL within the same pool. |
| [**apiV1UrlPoolsIdUrlsUrlIdDelete()**](URLPoolsApi.md#apiV1UrlPoolsIdUrlsUrlIdDelete) | **DELETE** /api/v1/url-pools/{id}/urls/{urlId} | Soft-delete a single URL. |
| [**apiV1UrlPoolsIdUrlsUrlIdGet()**](URLPoolsApi.md#apiV1UrlPoolsIdUrlsUrlIdGet) | **GET** /api/v1/url-pools/{id}/urls/{urlId} | Show a single URL. |
| [**apiV1UrlPoolsIdUrlsUrlIdPut()**](URLPoolsApi.md#apiV1UrlPoolsIdUrlsUrlIdPut) | **PUT** /api/v1/url-pools/{id}/urls/{urlId} | Update a single URL. |
| [**apiV1UrlPoolsIdUrlsUrlIdTogglePut()**](URLPoolsApi.md#apiV1UrlPoolsIdUrlsUrlIdTogglePut) | **PUT** /api/v1/url-pools/{id}/urls/{urlId}/toggle | Toggle a URL on / off. |
| [**apiV1UrlPoolsIdUrlsUrlIdWeightPut()**](URLPoolsApi.md#apiV1UrlPoolsIdUrlsUrlIdWeightPut) | **PUT** /api/v1/url-pools/{id}/urls/{urlId}/weight | Set a URL&#39;s rotation weight. |
| [**apiV1UrlPoolsIdUsedInGet()**](URLPoolsApi.md#apiV1UrlPoolsIdUsedInGet) | **GET** /api/v1/url-pools/{id}/used-in | List campaigns / ads that reference this URL pool. |
| [**apiV1UrlPoolsPost()**](URLPoolsApi.md#apiV1UrlPoolsPost) | **POST** /api/v1/url-pools | Create a URL pool. |


## `apiV1UrlPoolsGet()`

```php
apiV1UrlPoolsGet($page, $per_page): \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsGet200Response
```

List URL pools.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\URLPoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$page = 1; // int
$per_page = 50; // int

try {
    $result = $apiInstance->apiV1UrlPoolsGet($page, $per_page);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling URLPoolsApi->apiV1UrlPoolsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
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

## `apiV1UrlPoolsIdClonePost()`

```php
apiV1UrlPoolsIdClonePost($id): \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response
```

Clone a URL pool with all its URLs.

Duplicates the pool row and every URL in it (`isdeleted=0`). New pool title is suffixed with \" (Copy)\".

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\URLPoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $result = $apiInstance->apiV1UrlPoolsIdClonePost($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling URLPoolsApi->apiV1UrlPoolsIdClonePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response**](../Model/ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1UrlPoolsIdDelete()`

```php
apiV1UrlPoolsIdDelete($id)
```

Soft-delete a URL pool.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\URLPoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1UrlPoolsIdDelete($id);
} catch (Exception $e) {
    echo 'Exception when calling URLPoolsApi->apiV1UrlPoolsIdDelete: ', $e->getMessage(), PHP_EOL;
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
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1UrlPoolsIdGet()`

```php
apiV1UrlPoolsIdGet($id)
```

Show one URL pool.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\URLPoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1UrlPoolsIdGet($id);
} catch (Exception $e) {
    echo 'Exception when calling URLPoolsApi->apiV1UrlPoolsIdGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1UrlPoolsIdPut()`

```php
apiV1UrlPoolsIdPut($id, $api_v1_url_pools_id_put_request)
```

Update URL pool meta.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\URLPoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_url_pools_id_put_request = new \GoAdServerApi\Model\ApiV1UrlPoolsIdPutRequest(); // \GoAdServerApi\Model\ApiV1UrlPoolsIdPutRequest

try {
    $apiInstance->apiV1UrlPoolsIdPut($id, $api_v1_url_pools_id_put_request);
} catch (Exception $e) {
    echo 'Exception when calling URLPoolsApi->apiV1UrlPoolsIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_url_pools_id_put_request** | [**\GoAdServerApi\Model\ApiV1UrlPoolsIdPutRequest**](../Model/ApiV1UrlPoolsIdPutRequest.md)|  | |

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

## `apiV1UrlPoolsIdUrlsGet()`

```php
apiV1UrlPoolsIdUrlsGet($id)
```

List URLs in a pool.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\URLPoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1UrlPoolsIdUrlsGet($id);
} catch (Exception $e) {
    echo 'Exception when calling URLPoolsApi->apiV1UrlPoolsIdUrlsGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1UrlPoolsIdUrlsPost()`

```php
apiV1UrlPoolsIdUrlsPost($id, $api_v1_url_pools_id_urls_post_request): \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response
```

Add a URL to a pool.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\URLPoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_url_pools_id_urls_post_request = new \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsPostRequest(); // \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsPostRequest

try {
    $result = $apiInstance->apiV1UrlPoolsIdUrlsPost($id, $api_v1_url_pools_id_urls_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling URLPoolsApi->apiV1UrlPoolsIdUrlsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_url_pools_id_urls_post_request** | [**\GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsPostRequest**](../Model/ApiV1UrlPoolsIdUrlsPostRequest.md)|  | |

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

## `apiV1UrlPoolsIdUrlsUrlIdClonePost()`

```php
apiV1UrlPoolsIdUrlsUrlIdClonePost($id, $url_id): \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response
```

Clone a single URL within the same pool.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\URLPoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$url_id = 56; // int

try {
    $result = $apiInstance->apiV1UrlPoolsIdUrlsUrlIdClonePost($id, $url_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling URLPoolsApi->apiV1UrlPoolsIdUrlsUrlIdClonePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **url_id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response**](../Model/ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1UrlPoolsIdUrlsUrlIdDelete()`

```php
apiV1UrlPoolsIdUrlsUrlIdDelete($id, $url_id)
```

Soft-delete a single URL.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\URLPoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$url_id = 56; // int

try {
    $apiInstance->apiV1UrlPoolsIdUrlsUrlIdDelete($id, $url_id);
} catch (Exception $e) {
    echo 'Exception when calling URLPoolsApi->apiV1UrlPoolsIdUrlsUrlIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **url_id** | **int**|  | |

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

## `apiV1UrlPoolsIdUrlsUrlIdGet()`

```php
apiV1UrlPoolsIdUrlsUrlIdGet($id, $url_id)
```

Show a single URL.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\URLPoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$url_id = 56; // int

try {
    $apiInstance->apiV1UrlPoolsIdUrlsUrlIdGet($id, $url_id);
} catch (Exception $e) {
    echo 'Exception when calling URLPoolsApi->apiV1UrlPoolsIdUrlsUrlIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **url_id** | **int**|  | |

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

## `apiV1UrlPoolsIdUrlsUrlIdPut()`

```php
apiV1UrlPoolsIdUrlsUrlIdPut($id, $url_id, $api_v1_url_pools_id_urls_url_id_put_request)
```

Update a single URL.

Editable fields — `url`, `title`, `weight`, `properties`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\URLPoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$url_id = 56; // int
$api_v1_url_pools_id_urls_url_id_put_request = new \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsUrlIdPutRequest(); // \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsUrlIdPutRequest

try {
    $apiInstance->apiV1UrlPoolsIdUrlsUrlIdPut($id, $url_id, $api_v1_url_pools_id_urls_url_id_put_request);
} catch (Exception $e) {
    echo 'Exception when calling URLPoolsApi->apiV1UrlPoolsIdUrlsUrlIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **url_id** | **int**|  | |
| **api_v1_url_pools_id_urls_url_id_put_request** | [**\GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsUrlIdPutRequest**](../Model/ApiV1UrlPoolsIdUrlsUrlIdPutRequest.md)|  | |

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

## `apiV1UrlPoolsIdUrlsUrlIdTogglePut()`

```php
apiV1UrlPoolsIdUrlsUrlIdTogglePut($id, $url_id)
```

Toggle a URL on / off.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\URLPoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$url_id = 56; // int

try {
    $apiInstance->apiV1UrlPoolsIdUrlsUrlIdTogglePut($id, $url_id);
} catch (Exception $e) {
    echo 'Exception when calling URLPoolsApi->apiV1UrlPoolsIdUrlsUrlIdTogglePut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **url_id** | **int**|  | |

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

## `apiV1UrlPoolsIdUrlsUrlIdWeightPut()`

```php
apiV1UrlPoolsIdUrlsUrlIdWeightPut($id, $url_id, $api_v1_url_pools_id_urls_url_id_weight_put_request)
```

Set a URL's rotation weight.

Higher weight = more traffic share when `sort_type=wr`. Has no effect for `rnd`, `ctr` and `cr` sort modes.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\URLPoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$url_id = 56; // int
$api_v1_url_pools_id_urls_url_id_weight_put_request = new \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsUrlIdWeightPutRequest(); // \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsUrlIdWeightPutRequest

try {
    $apiInstance->apiV1UrlPoolsIdUrlsUrlIdWeightPut($id, $url_id, $api_v1_url_pools_id_urls_url_id_weight_put_request);
} catch (Exception $e) {
    echo 'Exception when calling URLPoolsApi->apiV1UrlPoolsIdUrlsUrlIdWeightPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **url_id** | **int**|  | |
| **api_v1_url_pools_id_urls_url_id_weight_put_request** | [**\GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsUrlIdWeightPutRequest**](../Model/ApiV1UrlPoolsIdUrlsUrlIdWeightPutRequest.md)|  | |

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

## `apiV1UrlPoolsIdUsedInGet()`

```php
apiV1UrlPoolsIdUsedInGet($id)
```

List campaigns / ads that reference this URL pool.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\URLPoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1UrlPoolsIdUsedInGet($id);
} catch (Exception $e) {
    echo 'Exception when calling URLPoolsApi->apiV1UrlPoolsIdUsedInGet: ', $e->getMessage(), PHP_EOL;
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
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1UrlPoolsPost()`

```php
apiV1UrlPoolsPost($api_v1_url_pools_post_request): \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response
```

Create a URL pool.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\URLPoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_url_pools_post_request = new \GoAdServerApi\Model\ApiV1UrlPoolsPostRequest(); // \GoAdServerApi\Model\ApiV1UrlPoolsPostRequest

try {
    $result = $apiInstance->apiV1UrlPoolsPost($api_v1_url_pools_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling URLPoolsApi->apiV1UrlPoolsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_url_pools_post_request** | [**\GoAdServerApi\Model\ApiV1UrlPoolsPostRequest**](../Model/ApiV1UrlPoolsPostRequest.md)|  | |

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
