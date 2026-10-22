# GoAdServerApi\AccountBillingApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1AccountBillingAffiliatedPaymentsGet()**](AccountBillingApi.md#apiV1AccountBillingAffiliatedPaymentsGet) | **GET** /api/v1/account/billing/affiliated-payments | Payments made to affiliated accounts. |
| [**apiV1AccountBillingAveragesGet()**](AccountBillingApi.md#apiV1AccountBillingAveragesGet) | **GET** /api/v1/account/billing/averages | Average costs and overcharges. |
| [**apiV1AccountBillingInvoicesGet()**](AccountBillingApi.md#apiV1AccountBillingInvoicesGet) | **GET** /api/v1/account/billing/invoices | List invoices. |
| [**apiV1AccountBillingInvoicesIdPdfGet()**](AccountBillingApi.md#apiV1AccountBillingInvoicesIdPdfGet) | **GET** /api/v1/account/billing/invoices/{id}/pdf | Download an invoice PDF. |
| [**apiV1AccountBillingSettingsGet()**](AccountBillingApi.md#apiV1AccountBillingSettingsGet) | **GET** /api/v1/account/billing/settings | The account&#39;s billing plan. |


## `apiV1AccountBillingAffiliatedPaymentsGet()`

```php
apiV1AccountBillingAffiliatedPaymentsGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Payments made to affiliated accounts.

Payouts recorded against accounts affiliated with this one.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountBillingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1AccountBillingAffiliatedPaymentsGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountBillingApi->apiV1AccountBillingAffiliatedPaymentsGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1AccountBillingAveragesGet()`

```php
apiV1AccountBillingAveragesGet(): \GoAdServerApi\Model\ApiV1AccountBillingAveragesGet200Response
```

Average costs and overcharges.

This month's usage projected to month end and priced with the account's billing plan (the tenant default fills any rate the plan leaves out) — the same pricing the monthly invoice uses. Not wrapped in `data`. With no active plan every field is zero / empty.  `modules` lists the charges of the plan's add-on modules (CRM, Agentic Media Buying), already included in `costs`. Media buying is a percentage of the managed network spend of your ad zones with Agentic Media Buying switched on, with a monthly minimum in a month that has managed spend.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountBillingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1AccountBillingAveragesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountBillingApi->apiV1AccountBillingAveragesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1AccountBillingAveragesGet200Response**](../Model/ApiV1AccountBillingAveragesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountBillingInvoicesGet()`

```php
apiV1AccountBillingInvoicesGet(): \GoAdServerApi\Model\ApiV1AccountBillingInvoicesGet200Response
```

List invoices.

Every invoice raised against the account, paid or not, newest first, unpaginated, under `results` (not `data`). `money`, `bandwidth_costs` and `pro_adearned` are **cents** (unlike GET /api/v1/funds, which reports currency units). `paystatus_string` is Open / Processing / Paid / Prevalidated. A database error answers 200 with an empty list.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountBillingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1AccountBillingInvoicesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountBillingApi->apiV1AccountBillingInvoicesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1AccountBillingInvoicesGet200Response**](../Model/ApiV1AccountBillingInvoicesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountBillingInvoicesIdPdfGet()`

```php
apiV1AccountBillingInvoicesIdPdfGet($id): \SplFileObject
```

Download an invoice PDF.

Returns `application/pdf`, not JSON. 404 for an invoice that is not the caller's.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountBillingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Invoice ID.

try {
    $result = $apiInstance->apiV1AccountBillingInvoicesIdPdfGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountBillingApi->apiV1AccountBillingInvoicesIdPdfGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Invoice ID. | |

### Return type

**\SplFileObject**

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/pdf`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountBillingSettingsGet()`

```php
apiV1AccountBillingSettingsGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

The account's billing plan.

The plan the account is on, its fixed costs and currency, and whether this membership may manage billing at all.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountBillingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1AccountBillingSettingsGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountBillingApi->apiV1AccountBillingSettingsGet: ', $e->getMessage(), PHP_EOL;
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
