# GoAdServerApi\OffersApi

Offer catalog visible to the authenticated publisher.

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1OffersGet()**](OffersApi.md#apiV1OffersGet) | **GET** /api/v1/offers | List available offers. |


## `apiV1OffersGet()`

```php
apiV1OffersGet($main_catid, $catids): \GoAdServerApi\Model\ApiV1OffersGet200Response
```

List available offers.

Returns the offer catalog visible to the authenticated publisher — offer-type campaigns the publisher is whitelisted for (and not blacklisted from), with the payout computed for this publisher. Documented in audit_20260723_1117 (was implemented but unspecified).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\OffersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$main_catid = 56; // int | Filter by main category id.
$catids = 'catids_example'; // string | Comma-separated sub-category ids.

try {
    $result = $apiInstance->apiV1OffersGet($main_catid, $catids);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OffersApi->apiV1OffersGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **main_catid** | **int**| Filter by main category id. | [optional] |
| **catids** | **string**| Comma-separated sub-category ids. | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1OffersGet200Response**](../Model/ApiV1OffersGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
