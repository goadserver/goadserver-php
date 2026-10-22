# GoAdServerApi\TrackerSettingsApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1TrackerCountriesGet()**](TrackerSettingsApi.md#apiV1TrackerCountriesGet) | **GET** /api/v1/tracker/countries | List countries seen in the caller&#39;s traffic. |
| [**apiV1TrackerCustomParamsGet()**](TrackerSettingsApi.md#apiV1TrackerCustomParamsGet) | **GET** /api/v1/tracker/custom-params | Get the custom-parameter slots. |
| [**apiV1TrackerCustomParamsPut()**](TrackerSettingsApi.md#apiV1TrackerCustomParamsPut) | **PUT** /api/v1/tracker/custom-params | Name the custom-parameter slots. |
| [**apiV1TrackerSettingsGet()**](TrackerSettingsApi.md#apiV1TrackerSettingsGet) | **GET** /api/v1/tracker/settings | Tracker settings for this account. |
| [**apiV1TrackerSourceTemplatesGet()**](TrackerSettingsApi.md#apiV1TrackerSourceTemplatesGet) | **GET** /api/v1/tracker/source-templates | List traffic-source templates. |
| [**apiV1TrackerTokensGet()**](TrackerSettingsApi.md#apiV1TrackerTokensGet) | **GET** /api/v1/tracker/tokens | List the macro tokens. |


## `apiV1TrackerCountriesGet()`

```php
apiV1TrackerCountriesGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

List countries seen in the caller's traffic.

Only the countries this account actually has clicks from, so a filter dropdown built from it has no dead entries.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerSettingsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1TrackerCountriesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerSettingsApi->apiV1TrackerCountriesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerCustomParamsGet()`

```php
apiV1TrackerCustomParamsGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Get the custom-parameter slots.

All 11 passthrough slots (subid plus sid1–sid10), named or not. These are the names that appear as `sid1`–`sid5` dimensions on the stats endpoints.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerSettingsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1TrackerCustomParamsGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerSettingsApi->apiV1TrackerCustomParamsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerCustomParamsPut()`

```php
apiV1TrackerCustomParamsPut($api_v1_tracker_custom_params_put_request): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Name the custom-parameter slots.

Bulk upsert of the whole grid — send every slot you want to keep.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerSettingsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_tracker_custom_params_put_request = new \GoAdServerApi\Model\ApiV1TrackerCustomParamsPutRequest(); // \GoAdServerApi\Model\ApiV1TrackerCustomParamsPutRequest

try {
    $result = $apiInstance->apiV1TrackerCustomParamsPut($api_v1_tracker_custom_params_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerSettingsApi->apiV1TrackerCustomParamsPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_tracker_custom_params_put_request** | [**\GoAdServerApi\Model\ApiV1TrackerCustomParamsPutRequest**](../Model/ApiV1TrackerCustomParamsPutRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerSettingsGet()`

```php
apiV1TrackerSettingsGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Tracker settings for this account.

The tracker-relevant slice of tenant configuration — which sub-modules are live and what the click pipeline expects.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerSettingsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1TrackerSettingsGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerSettingsApi->apiV1TrackerSettingsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerSourceTemplatesGet()`

```php
apiV1TrackerSourceTemplatesGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

List traffic-source templates.

The macro presets a source can be built from — the valid `template_slug` values.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerSettingsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1TrackerSourceTemplatesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerSettingsApi->apiV1TrackerSourceTemplatesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerTokensGet()`

```php
apiV1TrackerTokensGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

List the macro tokens.

The placeholder registry a click URL can carry — `{clickid}`, `{country}`, `{source_id}` and the rest. The valid values for a link's `tokens`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerSettingsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1TrackerTokensGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerSettingsApi->apiV1TrackerTokensGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
