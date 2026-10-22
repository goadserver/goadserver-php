# GoAdServerApi\URLPoolsApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1UrlPoolsGet()**](URLPoolsApi.md#apiV1UrlPoolsGet) | **GET** /api/v1/url-pools | List URL pools. |
| [**apiV1UrlPoolsIdClonePost()**](URLPoolsApi.md#apiV1UrlPoolsIdClonePost) | **POST** /api/v1/url-pools/{id}/clone | Clone a URL pool with all its URLs. |
| [**apiV1UrlPoolsIdDelete()**](URLPoolsApi.md#apiV1UrlPoolsIdDelete) | **DELETE** /api/v1/url-pools/{id} | Soft-delete a URL pool. |
| [**apiV1UrlPoolsIdGet()**](URLPoolsApi.md#apiV1UrlPoolsIdGet) | **GET** /api/v1/url-pools/{id} | Show one URL pool. |
| [**apiV1UrlPoolsIdPut()**](URLPoolsApi.md#apiV1UrlPoolsIdPut) | **PUT** /api/v1/url-pools/{id} | Update URL pool meta. |
| [**apiV1UrlPoolsIdRecoverPost()**](URLPoolsApi.md#apiV1UrlPoolsIdRecoverPost) | **POST** /api/v1/url-pools/{id}/recover | Restore an archived url pool. |
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
| [**apiV1UrlPoolsReplaceUrlPut()**](URLPoolsApi.md#apiV1UrlPoolsReplaceUrlPut) | **PUT** /api/v1/url-pools/replace-url | Search &amp; replace text across your URL-pool URLs. |


## `apiV1UrlPoolsGet()`

```php
apiV1UrlPoolsGet($select_stats, $from, $to, $page, $per_page, $filter_id, $filter_title, $filter_name, $state, $search, $sort_by, $sort_dir): \GoAdServerApi\Model\ApiV1CreativesGet200Response
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
$select_stats = 'select_stats_example'; // string | 1 = add clicks per pool for the from / to window (default the last 7 days).
$from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | With select_stats=1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days.
$to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | With select_stats=1: last day of the stats window (YYYY-MM-DD).
$page = 1; // int
$per_page = 20; // int
$filter_id = 56; // int
$filter_title = 'filter_title_example'; // string | Title contains.
$filter_name = 'filter_name_example'; // string | Same as filter_title.
$state = 'live'; // string | deleted = the archived pools (deleted from the pool page or archived from Cleanup), to restore with POST /{id}/recover. Purged pools are in neither list.
$search = 'search_example'; // string | Title contains.
$sort_by = 'id'; // string
$sort_dir = 'desc'; // string

try {
    $result = $apiInstance->apiV1UrlPoolsGet($select_stats, $from, $to, $page, $per_page, $filter_id, $filter_title, $filter_name, $state, $search, $sort_by, $sort_dir);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling URLPoolsApi->apiV1UrlPoolsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **select_stats** | **string**| 1 &#x3D; add clicks per pool for the from / to window (default the last 7 days). | [optional] |
| **from** | **\DateTime**| With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. | [optional] |
| **to** | **\DateTime**| With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). | [optional] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 20] |
| **filter_id** | **int**|  | [optional] |
| **filter_title** | **string**| Title contains. | [optional] |
| **filter_name** | **string**| Same as filter_title. | [optional] |
| **state** | **string**| deleted &#x3D; the archived pools (deleted from the pool page or archived from Cleanup), to restore with POST /{id}/recover. Purged pools are in neither list. | [optional] [default to &#39;live&#39;] |
| **search** | **string**| Title contains. | [optional] |
| **sort_by** | **string**|  | [optional] [default to &#39;id&#39;] |
| **sort_dir** | **string**|  | [optional] [default to &#39;desc&#39;] |

### Return type

[**\GoAdServerApi\Model\ApiV1CreativesGet200Response**](../Model/ApiV1CreativesGet200Response.md)

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

## `apiV1UrlPoolsIdRecoverPost()`

```php
apiV1UrlPoolsIdRecoverPost($id)
```

Restore an archived url pool.

Reverses the soft delete of DELETE /url-pools/{id} (and of Cleanup's Archive): the pool is listed and usable again. Its URLs were never removed, so they are all there. 404 when the pool does not exist, is not archived, or was purged.

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
    $apiInstance->apiV1UrlPoolsIdRecoverPost($id);
} catch (Exception $e) {
    echo 'Exception when calling URLPoolsApi->apiV1UrlPoolsIdRecoverPost: ', $e->getMessage(), PHP_EOL;
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

## `apiV1UrlPoolsIdUrlsGet()`

```php
apiV1UrlPoolsIdUrlsGet($id, $filter_url, $filter_isactive, $search, $sort_by, $sort_dir, $page, $per_page, $select_stats, $from, $to)
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
$filter_url = 'filter_url_example'; // string | URL contains.
$filter_isactive = 56; // int
$search = 'search_example'; // string | Matches URL, title or keywords.
$sort_by = 'id'; // string
$sort_dir = 'desc'; // string
$page = 1; // int
$per_page = 20; // int
$select_stats = 'select_stats_example'; // string | 1 = add stats per URL for the from / to window (default the last 7 days).
$from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | With select_stats=1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days.
$to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | With select_stats=1: last day of the stats window (YYYY-MM-DD).

try {
    $apiInstance->apiV1UrlPoolsIdUrlsGet($id, $filter_url, $filter_isactive, $search, $sort_by, $sort_dir, $page, $per_page, $select_stats, $from, $to);
} catch (Exception $e) {
    echo 'Exception when calling URLPoolsApi->apiV1UrlPoolsIdUrlsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **filter_url** | **string**| URL contains. | [optional] |
| **filter_isactive** | **int**|  | [optional] |
| **search** | **string**| Matches URL, title or keywords. | [optional] |
| **sort_by** | **string**|  | [optional] [default to &#39;id&#39;] |
| **sort_dir** | **string**|  | [optional] [default to &#39;desc&#39;] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 20] |
| **select_stats** | **string**| 1 &#x3D; add stats per URL for the from / to window (default the last 7 days). | [optional] |
| **from** | **\DateTime**| With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. | [optional] |
| **to** | **\DateTime**| With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). | [optional] |

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

Editable fields — `url`, `title`, `weight`, `properties`, `keywords`, `is_fallback`, `start_at`, `end_at`.  **Scheduling.** `start_at` / `end_at` make the URL serve only inside `[start_at, end_at)`. Send unix seconds or an RFC 3339 time with its offset (`2026-10-20T09:00:00-04:00` = 9:00 AM New York); `null`, `0` or `\"\"` clears that side. The ad servers pick up an edit within a minute and then switch on the exact second.  **Backup** (`is_fallback: true`): the URL is used only while no other URL of the pool is live — mark the regular lander as backup and schedule the promo lander; the regular one steps aside while the promo runs and returns when it ends. Keyword targeting then applies within whichever set is live. Not to be confused with a keyword *fallback* URL (one without keywords).  A change that would leave a pool still used by campaign ads with no URL to serve at some moment is refused with 409 `SCHEDULE_LEAVES_POOL_EMPTY` (`gap_at` = first uncovered unix second). Pausing or deleting a URL runs the same check over the other URLs' schedules.

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
$api_v1_url_pools_id_urls_url_id_put_request = {"is_fallback":false,"start_at":"2026-10-20T09:00:00-04:00","end_at":"2026-10-27T09:00:00-04:00"}; // \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsUrlIdPutRequest

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
- **Accept**: `application/json`

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
- **Accept**: `application/json`

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

## `apiV1UrlPoolsReplaceUrlPut()`

```php
apiV1UrlPoolsReplaceUrlPut($api_v1_url_pools_replace_url_put_request)
```

Search & replace text across your URL-pool URLs.

Replaces the first occurrence of `search` with `replace` in every matching URL of your URL pools — e.g. swap a tracker domain or an offer id. Narrow with `poolids` and/or `urlids`; both empty = every URL of every pool you own. `dry_run: true` returns the matches (old → new) without writing — recommended first. Logged to the account's action log. A key pinned to campaigns is refused (URL pools feed ads of any campaign). Same handler as the panel.

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
$api_v1_url_pools_replace_url_put_request = {"search":"old-tracker.com","replace":"new-tracker.com","dry_run":true}; // \GoAdServerApi\Model\ApiV1UrlPoolsReplaceUrlPutRequest

try {
    $apiInstance->apiV1UrlPoolsReplaceUrlPut($api_v1_url_pools_replace_url_put_request);
} catch (Exception $e) {
    echo 'Exception when calling URLPoolsApi->apiV1UrlPoolsReplaceUrlPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_url_pools_replace_url_put_request** | [**\GoAdServerApi\Model\ApiV1UrlPoolsReplaceUrlPutRequest**](../Model/ApiV1UrlPoolsReplaceUrlPutRequest.md)|  | |

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
