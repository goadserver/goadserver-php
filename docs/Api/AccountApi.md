# GoAdServerApi\AccountApi

Identity, profile, and API key management.

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1AccountGet()**](AccountApi.md#apiV1AccountGet) | **GET** /api/v1/account | Identity check — returns the authenticated user + scopes of the calling key. |


## `apiV1AccountGet()`

```php
apiV1AccountGet(): \GoAdServerApi\Model\ApiV1AccountGet200Response
```

Identity check — returns the authenticated user + scopes of the calling key.

Minimal identity payload. Use this to verify a key is alive, discover which user it belongs to, and inspect what scopes the key was granted. No PII beyond name/email/company.  Key management (create/list/revoke) is **not** exposed via the public API — it requires a browser session. Visit MyAccount → API access in the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1AccountGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountApi->apiV1AccountGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1AccountGet200Response**](../Model/ApiV1AccountGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
