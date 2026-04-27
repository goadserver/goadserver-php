# GoAdServerApi\FundsApi

Account balance and deposit status.

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1FundsGet()**](FundsApi.md#apiV1FundsGet) | **GET** /api/v1/funds | Current advertiser balance + pending deposits. |


## `apiV1FundsGet()`

```php
apiV1FundsGet(): \GoAdServerApi\Model\ApiV1FundsGet200Response
```

Current advertiser balance + pending deposits.

Use this before deciding to launch or pause a campaign — `low_balance` is true when current balance is below the user-configured minimum.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FundsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1FundsGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FundsApi->apiV1FundsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1FundsGet200Response**](../Model/ApiV1FundsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
