# GoAdServerApi\TrackingApi

Conversion-tracking pixels — create, list, update, delete.

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1TrackingConversionTypesGet()**](TrackingApi.md#apiV1TrackingConversionTypesGet) | **GET** /api/v1/tracking/conversion-types | Available conversion types for the caller&#39;s account. |
| [**apiV1TrackingConversionTypesPut()**](TrackingApi.md#apiV1TrackingConversionTypesPut) | **PUT** /api/v1/tracking/conversion-types | Name conversion-type slots and set the click-id dedup opt-outs. |
| [**apiV1TrackingPixelsGet()**](TrackingApi.md#apiV1TrackingPixelsGet) | **GET** /api/v1/tracking/pixels | Deprecated: List the caller&#39;s conversion-tracking pixels. |
| [**apiV1TrackingPixelsIdDelete()**](TrackingApi.md#apiV1TrackingPixelsIdDelete) | **DELETE** /api/v1/tracking/pixels/{id} | Deprecated: Delete a pixel. |
| [**apiV1TrackingPixelsIdGet()**](TrackingApi.md#apiV1TrackingPixelsIdGet) | **GET** /api/v1/tracking/pixels/{id} | Deprecated: Get one pixel. |
| [**apiV1TrackingPixelsIdPut()**](TrackingApi.md#apiV1TrackingPixelsIdPut) | **PUT** /api/v1/tracking/pixels/{id} | Deprecated: Update a pixel&#39;s name or conversion type. |
| [**apiV1TrackingPixelsPost()**](TrackingApi.md#apiV1TrackingPixelsPost) | **POST** /api/v1/tracking/pixels | Deprecated: Create a new tracking pixel. |


## `apiV1TrackingConversionTypesGet()`

```php
apiV1TrackingConversionTypesGet(): \GoAdServerApi\Model\ApiV1TrackingConversionTypesGet200Response
```

Available conversion types for the caller's account.

The account's named conversion-type slots (0–25, sorted A–Z by name; unnamed slots are omitted), the slots opted out of click-id deduplication, the network's ad-domain and the configured tracking variable name (needed to construct the postback URL). `id` is the slot a postback's conversion type refers to. An account that has never saved its conversion types gets the defaults (0 = Sign ups, 1 = Conversions, 25 = Refunds) — the same names the first PUT seeds; an account that switched every slot off gets `[]`. Same handler as the panel. Requires the tenant's tracking module (`modules.advertiser.tracking`).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1TrackingConversionTypesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackingApi->apiV1TrackingConversionTypesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackingConversionTypesGet200Response**](../Model/ApiV1TrackingConversionTypesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackingConversionTypesPut()`

```php
apiV1TrackingConversionTypesPut($api_v1_tracking_conversion_types_put_request): \GoAdServerApi\Model\ApiV1TrackingConversionTypesPut200Response
```

Name conversion-type slots and set the click-id dedup opt-outs.

Same handler as the panel's Tracking page. Send either `types: [{id, name}]` or `conv_type_0`..`conv_type_25` keys to name slots (an empty name clears the slot; HTML tags are stripped), and/or `skip_dedup: [slot ids]` to replace the set of slots excluded from click-id deduplication (an empty array clears it). Slots outside 0–25 are ignored. Fields you don't send are left alone. Unknown keys are a 422 for API callers. The first save creates the account's row with the defaults Sign ups (0), Conversions (1), Refunds (25).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_tracking_conversion_types_put_request = {"types":[{"id":2,"name":"Deposit"},{"id":3,"name":"Rebill"}],"skip_dedup":[3]}; // \GoAdServerApi\Model\ApiV1TrackingConversionTypesPutRequest

try {
    $result = $apiInstance->apiV1TrackingConversionTypesPut($api_v1_tracking_conversion_types_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackingApi->apiV1TrackingConversionTypesPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_tracking_conversion_types_put_request** | [**\GoAdServerApi\Model\ApiV1TrackingConversionTypesPutRequest**](../Model/ApiV1TrackingConversionTypesPutRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackingConversionTypesPut200Response**](../Model/ApiV1TrackingConversionTypesPut200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackingPixelsGet()`

```php
apiV1TrackingPixelsGet($conversion_type_id, $name, $page, $per_page): \GoAdServerApi\Model\ApiV1TrackingPixelsGet200Response
```

Deprecated: List the caller's conversion-tracking pixels.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$conversion_type_id = 56; // int | Filter by conversion type.
$name = 'name_example'; // string | Substring search on name.
$page = 1; // int
$per_page = 100; // int

try {
    $result = $apiInstance->apiV1TrackingPixelsGet($conversion_type_id, $name, $page, $per_page);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackingApi->apiV1TrackingPixelsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **conversion_type_id** | **int**| Filter by conversion type. | [optional] |
| **name** | **string**| Substring search on name. | [optional] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 100] |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackingPixelsGet200Response**](../Model/ApiV1TrackingPixelsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackingPixelsIdDelete()`

```php
apiV1TrackingPixelsIdDelete($id)
```

Deprecated: Delete a pixel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1TrackingPixelsIdDelete($id);
} catch (Exception $e) {
    echo 'Exception when calling TrackingApi->apiV1TrackingPixelsIdDelete: ', $e->getMessage(), PHP_EOL;
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

## `apiV1TrackingPixelsIdGet()`

```php
apiV1TrackingPixelsIdGet($id): \GoAdServerApi\Model\TrackingPixel
```

Deprecated: Get one pixel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $result = $apiInstance->apiV1TrackingPixelsIdGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackingApi->apiV1TrackingPixelsIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\TrackingPixel**](../Model/TrackingPixel.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackingPixelsIdPut()`

```php
apiV1TrackingPixelsIdPut($id, $api_v1_tracking_pixels_id_put_request): \GoAdServerApi\Model\TrackingPixel
```

Deprecated: Update a pixel's name or conversion type.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_tracking_pixels_id_put_request = new \GoAdServerApi\Model\ApiV1TrackingPixelsIdPutRequest(); // \GoAdServerApi\Model\ApiV1TrackingPixelsIdPutRequest

try {
    $result = $apiInstance->apiV1TrackingPixelsIdPut($id, $api_v1_tracking_pixels_id_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackingApi->apiV1TrackingPixelsIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_tracking_pixels_id_put_request** | [**\GoAdServerApi\Model\ApiV1TrackingPixelsIdPutRequest**](../Model/ApiV1TrackingPixelsIdPutRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\TrackingPixel**](../Model/TrackingPixel.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackingPixelsPost()`

```php
apiV1TrackingPixelsPost($api_v1_tracking_pixels_post_request): \GoAdServerApi\Model\TrackingPixel
```

Deprecated: Create a new tracking pixel.

Deprecated — the row is stored but nothing reads it. `pixel_code` is generated server-side (32 random hex chars) and returned in the response; putting it in a conversion URL has no effect. To track conversions, name a slot with PUT /api/v1/tracking/conversion-types and send postbacks with the click id and that slot.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_tracking_pixels_post_request = new \GoAdServerApi\Model\ApiV1TrackingPixelsPostRequest(); // \GoAdServerApi\Model\ApiV1TrackingPixelsPostRequest

try {
    $result = $apiInstance->apiV1TrackingPixelsPost($api_v1_tracking_pixels_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackingApi->apiV1TrackingPixelsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_tracking_pixels_post_request** | [**\GoAdServerApi\Model\ApiV1TrackingPixelsPostRequest**](../Model/ApiV1TrackingPixelsPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\TrackingPixel**](../Model/TrackingPixel.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
