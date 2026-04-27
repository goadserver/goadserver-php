# GoAdServerApi\RetargetingApi

Retargeting pixels — capture visitor IDs for re-engagement campaigns.

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1RetargetingPixelsGet()**](RetargetingApi.md#apiV1RetargetingPixelsGet) | **GET** /api/v1/retargeting/pixels | List the caller&#39;s retargeting pixels. |
| [**apiV1RetargetingPixelsIdActivePatch()**](RetargetingApi.md#apiV1RetargetingPixelsIdActivePatch) | **PATCH** /api/v1/retargeting/pixels/{id}/active | Activate or deactivate a retargeting pixel. |
| [**apiV1RetargetingPixelsIdDelete()**](RetargetingApi.md#apiV1RetargetingPixelsIdDelete) | **DELETE** /api/v1/retargeting/pixels/{id} | Soft-delete a retargeting pixel and clear its captured visitors. |
| [**apiV1RetargetingPixelsIdGet()**](RetargetingApi.md#apiV1RetargetingPixelsIdGet) | **GET** /api/v1/retargeting/pixels/{id} | Get one retargeting pixel. |
| [**apiV1RetargetingPixelsIdPut()**](RetargetingApi.md#apiV1RetargetingPixelsIdPut) | **PUT** /api/v1/retargeting/pixels/{id} | Rename a retargeting pixel. |
| [**apiV1RetargetingPixelsPost()**](RetargetingApi.md#apiV1RetargetingPixelsPost) | **POST** /api/v1/retargeting/pixels | Create a retargeting pixel. |


## `apiV1RetargetingPixelsGet()`

```php
apiV1RetargetingPixelsGet(): \GoAdServerApi\Model\ApiV1RetargetingPixelsGet200Response
```

List the caller's retargeting pixels.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\RetargetingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1RetargetingPixelsGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RetargetingApi->apiV1RetargetingPixelsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1RetargetingPixelsGet200Response**](../Model/ApiV1RetargetingPixelsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1RetargetingPixelsIdActivePatch()`

```php
apiV1RetargetingPixelsIdActivePatch($id, $api_v1_campaigns_type_id_active_patch_request): \GoAdServerApi\Model\ApiV1RetargetingPixelsIdActivePatch200Response
```

Activate or deactivate a retargeting pixel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\RetargetingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_campaigns_type_id_active_patch_request = new \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest(); // \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest

try {
    $result = $apiInstance->apiV1RetargetingPixelsIdActivePatch($id, $api_v1_campaigns_type_id_active_patch_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RetargetingApi->apiV1RetargetingPixelsIdActivePatch: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_campaigns_type_id_active_patch_request** | [**\GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest**](../Model/ApiV1CampaignsTypeIdActivePatchRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1RetargetingPixelsIdActivePatch200Response**](../Model/ApiV1RetargetingPixelsIdActivePatch200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1RetargetingPixelsIdDelete()`

```php
apiV1RetargetingPixelsIdDelete($id)
```

Soft-delete a retargeting pixel and clear its captured visitors.

Sets `isdeleted=1` in MySQL and asynchronously deletes the pixel's rows from ClickHouse `rt_final`. Captured visitor IDs are gone — re-creating with the same name will start fresh.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\RetargetingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1RetargetingPixelsIdDelete($id);
} catch (Exception $e) {
    echo 'Exception when calling RetargetingApi->apiV1RetargetingPixelsIdDelete: ', $e->getMessage(), PHP_EOL;
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

## `apiV1RetargetingPixelsIdGet()`

```php
apiV1RetargetingPixelsIdGet($id): \GoAdServerApi\Model\RetargetingPixel
```

Get one retargeting pixel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\RetargetingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $result = $apiInstance->apiV1RetargetingPixelsIdGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RetargetingApi->apiV1RetargetingPixelsIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\RetargetingPixel**](../Model/RetargetingPixel.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1RetargetingPixelsIdPut()`

```php
apiV1RetargetingPixelsIdPut($id, $api_v1_retargeting_pixels_id_put_request)
```

Rename a retargeting pixel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\RetargetingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_retargeting_pixels_id_put_request = new \GoAdServerApi\Model\ApiV1RetargetingPixelsIdPutRequest(); // \GoAdServerApi\Model\ApiV1RetargetingPixelsIdPutRequest

try {
    $apiInstance->apiV1RetargetingPixelsIdPut($id, $api_v1_retargeting_pixels_id_put_request);
} catch (Exception $e) {
    echo 'Exception when calling RetargetingApi->apiV1RetargetingPixelsIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_retargeting_pixels_id_put_request** | [**\GoAdServerApi\Model\ApiV1RetargetingPixelsIdPutRequest**](../Model/ApiV1RetargetingPixelsIdPutRequest.md)|  | |

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

## `apiV1RetargetingPixelsPost()`

```php
apiV1RetargetingPixelsPost($api_v1_retargeting_pixels_post_request): \GoAdServerApi\Model\RetargetingPixel
```

Create a retargeting pixel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\RetargetingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_retargeting_pixels_post_request = new \GoAdServerApi\Model\ApiV1RetargetingPixelsPostRequest(); // \GoAdServerApi\Model\ApiV1RetargetingPixelsPostRequest

try {
    $result = $apiInstance->apiV1RetargetingPixelsPost($api_v1_retargeting_pixels_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RetargetingApi->apiV1RetargetingPixelsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_retargeting_pixels_post_request** | [**\GoAdServerApi\Model\ApiV1RetargetingPixelsPostRequest**](../Model/ApiV1RetargetingPixelsPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\RetargetingPixel**](../Model/RetargetingPixel.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
