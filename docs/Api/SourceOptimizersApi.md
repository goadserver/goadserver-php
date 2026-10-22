# GoAdServerApi\SourceOptimizersApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1CpaOptimizersGet()**](SourceOptimizersApi.md#apiV1CpaOptimizersGet) | **GET** /api/v1/cpa-optimizers | List CPA optimizers. |
| [**apiV1CpaOptimizersIdDelete()**](SourceOptimizersApi.md#apiV1CpaOptimizersIdDelete) | **DELETE** /api/v1/cpa-optimizers/{id} | Delete a CPA optimizer. |
| [**apiV1CpaOptimizersIdGet()**](SourceOptimizersApi.md#apiV1CpaOptimizersIdGet) | **GET** /api/v1/cpa-optimizers/{id} | Show a CPA optimizer. |
| [**apiV1CpaOptimizersIdPut()**](SourceOptimizersApi.md#apiV1CpaOptimizersIdPut) | **PUT** /api/v1/cpa-optimizers/{id} | Replace a CPA optimizer. |
| [**apiV1CpaOptimizersPost()**](SourceOptimizersApi.md#apiV1CpaOptimizersPost) | **POST** /api/v1/cpa-optimizers | Create a CPA optimizer. |
| [**apiV1CpmOptimizersActiveGet()**](SourceOptimizersApi.md#apiV1CpmOptimizersActiveGet) | **GET** /api/v1/cpm-optimizers/active | Does the account have any CPM optimizer? |
| [**apiV1CpmOptimizersGet()**](SourceOptimizersApi.md#apiV1CpmOptimizersGet) | **GET** /api/v1/cpm-optimizers | List CPM optimizers. |
| [**apiV1CpmOptimizersIdDelete()**](SourceOptimizersApi.md#apiV1CpmOptimizersIdDelete) | **DELETE** /api/v1/cpm-optimizers/{id} | Delete a CPM optimizer. |
| [**apiV1CpmOptimizersIdGet()**](SourceOptimizersApi.md#apiV1CpmOptimizersIdGet) | **GET** /api/v1/cpm-optimizers/{id} | Show a CPM optimizer. |
| [**apiV1CpmOptimizersIdPut()**](SourceOptimizersApi.md#apiV1CpmOptimizersIdPut) | **PUT** /api/v1/cpm-optimizers/{id} | Replace a CPM optimizer. |
| [**apiV1CpmOptimizersPost()**](SourceOptimizersApi.md#apiV1CpmOptimizersPost) | **POST** /api/v1/cpm-optimizers | Create a CPM optimizer. |
| [**apiV1SourceOptimizerConfigGet()**](SourceOptimizersApi.md#apiV1SourceOptimizerConfigGet) | **GET** /api/v1/source-optimizer/config | Option values for the optimizer forms. |


## `apiV1CpaOptimizersGet()`

```php
apiV1CpaOptimizersGet()
```

List CPA optimizers.

A bare array. Money (`max_cpa`, the `target_cpa` values) is in the account currency — stored ×100 as cents and divided back here. `target_cpa` / `min_margin` are keyed by conversion-type id (see GET /api/v1/tracking/conversion-types); `min_margin` is a percent.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SourceOptimizersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1CpaOptimizersGet();
} catch (Exception $e) {
    echo 'Exception when calling SourceOptimizersApi->apiV1CpaOptimizersGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

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

## `apiV1CpaOptimizersIdDelete()`

```php
apiV1CpaOptimizersIdDelete($id)
```

Delete a CPA optimizer.

Soft delete. Returns 202 with `true` when a row was deleted, `false` when none matched. Refused for a campaign-pinned key.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SourceOptimizersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1CpaOptimizersIdDelete($id);
} catch (Exception $e) {
    echo 'Exception when calling SourceOptimizersApi->apiV1CpaOptimizersIdDelete: ', $e->getMessage(), PHP_EOL;
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

## `apiV1CpaOptimizersIdGet()`

```php
apiV1CpaOptimizersIdGet($id)
```

Show a CPA optimizer.

`targets` is a one-element array (empty when the id is not the caller's — no 404); `conversion_types` are the account's conversion-type slots the `target_cpa` keys refer to.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SourceOptimizersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1CpaOptimizersIdGet($id);
} catch (Exception $e) {
    echo 'Exception when calling SourceOptimizersApi->apiV1CpaOptimizersIdGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1CpaOptimizersIdPut()`

```php
apiV1CpaOptimizersIdPut($id, $cpa_optimizer_body)
```

Replace a CPA optimizer.

Full replace with the create validation; fields left out reset to their zero value (`start_date` left out keeps the stored date). Refused for a campaign-pinned key. Returns the id.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SourceOptimizersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$cpa_optimizer_body = new \GoAdServerApi\Model\CpaOptimizerBody(); // \GoAdServerApi\Model\CpaOptimizerBody

try {
    $apiInstance->apiV1CpaOptimizersIdPut($id, $cpa_optimizer_body);
} catch (Exception $e) {
    echo 'Exception when calling SourceOptimizersApi->apiV1CpaOptimizersIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **cpa_optimizer_body** | [**\GoAdServerApi\Model\CpaOptimizerBody**](../Model/CpaOptimizerBody.md)|  | |

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

## `apiV1CpaOptimizersPost()`

```php
apiV1CpaOptimizersPost($cpa_optimizer_body)
```

Create a CPA optimizer.

`title` (non-alphanumerics become `_`) and a non-zero `max_cpa` are required (422 `NO_TITLE` / `NO_PRICE_GIVEN`); a malformed `start_date` → 422 `NO_VALID_DATE`. Money in account currency (stored as cents). `bounce_max_perc` outside 0–100 and a `min_margin` outside 0–100 are stored as 0. Returns the new id as a bare number. `id` / `type` are accepted and ignored; any other unknown key → 422 `unknown_fields`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SourceOptimizersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$cpa_optimizer_body = {"title":"CPA 2 EUR","filter_type":"spaceid","max_cpa":20,"max_days":14,"start_date":"2026-10-01","target_cpa":{"1":2.5},"min_margin":{"1":10},"cpa_action":"block","bounce_id":-1,"bounce_check_type":"auto"}; // \GoAdServerApi\Model\CpaOptimizerBody

try {
    $apiInstance->apiV1CpaOptimizersPost($cpa_optimizer_body);
} catch (Exception $e) {
    echo 'Exception when calling SourceOptimizersApi->apiV1CpaOptimizersPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **cpa_optimizer_body** | [**\GoAdServerApi\Model\CpaOptimizerBody**](../Model/CpaOptimizerBody.md)|  | |

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

## `apiV1CpmOptimizersActiveGet()`

```php
apiV1CpmOptimizersActiveGet()
```

Does the account have any CPM optimizer?

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SourceOptimizersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1CpmOptimizersActiveGet();
} catch (Exception $e) {
    echo 'Exception when calling SourceOptimizersApi->apiV1CpmOptimizersActiveGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

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

## `apiV1CpmOptimizersGet()`

```php
apiV1CpmOptimizersGet()
```

List CPM optimizers.

A bare array (no envelope), ordered by title. Each item is the stored settings merged with `id`, `userid`, `title` and `type: \"cpm\"`. Money fields (`min_spendings`, `max_cpa`) are in the account currency, as entered.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SourceOptimizersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1CpmOptimizersGet();
} catch (Exception $e) {
    echo 'Exception when calling SourceOptimizersApi->apiV1CpmOptimizersGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

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

## `apiV1CpmOptimizersIdDelete()`

```php
apiV1CpmOptimizersIdDelete($id)
```

Delete a CPM optimizer.

Soft delete. Refused for a campaign-pinned key.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SourceOptimizersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1CpmOptimizersIdDelete($id);
} catch (Exception $e) {
    echo 'Exception when calling SourceOptimizersApi->apiV1CpmOptimizersIdDelete: ', $e->getMessage(), PHP_EOL;
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

## `apiV1CpmOptimizersIdGet()`

```php
apiV1CpmOptimizersIdGet($id)
```

Show a CPM optimizer.

Returns a one-element array (panel shape).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SourceOptimizersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1CpmOptimizersIdGet($id);
} catch (Exception $e) {
    echo 'Exception when calling SourceOptimizersApi->apiV1CpmOptimizersIdGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1CpmOptimizersIdPut()`

```php
apiV1CpmOptimizersIdPut($id, $cpm_optimizer_body)
```

Replace a CPM optimizer.

Full replace — the settings become exactly the body (fields left out are cleared). Same validation as create. Every campaign using the optimizer follows the change, so a campaign-pinned key gets 403 `forbidden_resource`. Returns the id.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SourceOptimizersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$cpm_optimizer_body = new \GoAdServerApi\Model\CpmOptimizerBody(); // \GoAdServerApi\Model\CpmOptimizerBody

try {
    $apiInstance->apiV1CpmOptimizersIdPut($id, $cpm_optimizer_body);
} catch (Exception $e) {
    echo 'Exception when calling SourceOptimizersApi->apiV1CpmOptimizersIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **cpm_optimizer_body** | [**\GoAdServerApi\Model\CpmOptimizerBody**](../Model/CpmOptimizerBody.md)|  | |

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

## `apiV1CpmOptimizersPost()`

```php
apiV1CpmOptimizersPost($cpm_optimizer_body)
```

Create a CPM optimizer.

`title` and a non-zero `min_spendings` are required (422 `NO_TITLE_GIVEN` / `NO_PRICE_GIVEN`). Every other listed field is stored as sent. `id`, `type` and `userid` are accepted (and ignored) so a fetched optimizer can be sent back; any other key → 422 `unknown_fields`. Returns the new id as a bare number.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SourceOptimizersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$cpm_optimizer_body = {"title":"Zone cleaner","opt_type":"0","min_spendings":5,"max_days":30,"start_date":"2026-10-01","conv_type":"1","optimise_with":"conv_count","max_cpa":2,"use_bidoptimiser":0}; // \GoAdServerApi\Model\CpmOptimizerBody

try {
    $apiInstance->apiV1CpmOptimizersPost($cpm_optimizer_body);
} catch (Exception $e) {
    echo 'Exception when calling SourceOptimizersApi->apiV1CpmOptimizersPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **cpm_optimizer_body** | [**\GoAdServerApi\Model\CpmOptimizerBody**](../Model/CpmOptimizerBody.md)|  | |

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

## `apiV1SourceOptimizerConfigGet()`

```php
apiV1SourceOptimizerConfigGet()
```

Option values for the optimizer forms.

The values `filter_type`, `cpa_action` and `bounce_check_type` accept.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SourceOptimizersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1SourceOptimizerConfigGet();
} catch (Exception $e) {
    echo 'Exception when calling SourceOptimizersApi->apiV1SourceOptimizerConfigGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

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
