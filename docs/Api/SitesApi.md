# GoAdServerApi\SitesApi

Read publisher websites.

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1SitesGet()**](SitesApi.md#apiV1SitesGet) | **GET** /api/v1/sites | List the caller&#39;s websites. |


## `apiV1SitesGet()`

```php
apiV1SitesGet(): \GoAdServerApi\Model\ApiV1SitesGet200Response
```

List the caller's websites.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1SitesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1SitesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1SitesGet200Response**](../Model/ApiV1SitesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
