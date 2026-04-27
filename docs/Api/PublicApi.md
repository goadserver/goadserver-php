# GoAdServerApi\PublicApi

No-auth discovery endpoints.

All URIs are relative to https://up.goadserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiPublicOpenapiJsonGet()**](PublicApi.md#apiPublicOpenapiJsonGet) | **GET** /api/public/openapi.json | OpenAPI spec as JSON. |
| [**apiPublicOpenapiYamlGet()**](PublicApi.md#apiPublicOpenapiYamlGet) | **GET** /api/public/openapi.yaml | Raw OpenAPI spec (this document). |
| [**apiPublicRoutesTxtGet()**](PublicApi.md#apiPublicRoutesTxtGet) | **GET** /api/public/routes.txt | Flat list of public routes — agent-friendly. |


## `apiPublicOpenapiJsonGet()`

```php
apiPublicOpenapiJsonGet()
```

OpenAPI spec as JSON.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new GoAdServerApi\Api\PublicApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);

try {
    $apiInstance->apiPublicOpenapiJsonGet();
} catch (Exception $e) {
    echo 'Exception when calling PublicApi->apiPublicOpenapiJsonGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

void (empty response body)

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiPublicOpenapiYamlGet()`

```php
apiPublicOpenapiYamlGet()
```

Raw OpenAPI spec (this document).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new GoAdServerApi\Api\PublicApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);

try {
    $apiInstance->apiPublicOpenapiYamlGet();
} catch (Exception $e) {
    echo 'Exception when calling PublicApi->apiPublicOpenapiYamlGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

void (empty response body)

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiPublicRoutesTxtGet()`

```php
apiPublicRoutesTxtGet()
```

Flat list of public routes — agent-friendly.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new GoAdServerApi\Api\PublicApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);

try {
    $apiInstance->apiPublicRoutesTxtGet();
} catch (Exception $e) {
    echo 'Exception when calling PublicApi->apiPublicRoutesTxtGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

void (empty response body)

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
