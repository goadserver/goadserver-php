# GoAdServerApi\AdspacesApi

Read publisher ad zones + on/off toggle.

All URIs are relative to https://up.goadserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1AdspacesGet()**](AdspacesApi.md#apiV1AdspacesGet) | **GET** /api/v1/adspaces | List ad zones owned by the caller. |
| [**apiV1AdspacesIdActivePatch()**](AdspacesApi.md#apiV1AdspacesIdActivePatch) | **PATCH** /api/v1/adspaces/{id}/active | Activate or deactivate an adspace. |
| [**apiV1AdspacesIdGet()**](AdspacesApi.md#apiV1AdspacesIdGet) | **GET** /api/v1/adspaces/{id} | Get a single adspace. |


## `apiV1AdspacesGet()`

```php
apiV1AdspacesGet($site_id, $page, $per_page): \GoAdServerApi\Model\ApiV1AdspacesGet200Response
```

List ad zones owned by the caller.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdspacesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$site_id = 56; // int | Filter to one site.
$page = 1; // int
$per_page = 100; // int

try {
    $result = $apiInstance->apiV1AdspacesGet($site_id, $page, $per_page);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **site_id** | **int**| Filter to one site. | [optional] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 100] |

### Return type

[**\GoAdServerApi\Model\ApiV1AdspacesGet200Response**](../Model/ApiV1AdspacesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AdspacesIdActivePatch()`

```php
apiV1AdspacesIdActivePatch($id, $api_v1_campaigns_type_id_active_patch_request): \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatch200Response
```

Activate or deactivate an adspace.

Idempotent. `active=true` activates, `active=false` deactivates. This is the endpoint behind the **\"On / off only\"** key preset for publishers. Honors `resource_filter.adzone_ids` for per-key scoping to specific zones.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdspacesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_campaigns_type_id_active_patch_request = new \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest(); // \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest

try {
    $result = $apiInstance->apiV1AdspacesIdActivePatch($id, $api_v1_campaigns_type_id_active_patch_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdActivePatch: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_campaigns_type_id_active_patch_request** | [**\GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest**](../Model/ApiV1CampaignsTypeIdActivePatchRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatch200Response**](../Model/ApiV1CampaignsTypeIdActivePatch200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AdspacesIdGet()`

```php
apiV1AdspacesIdGet($id)
```

Get a single adspace.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdspacesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1AdspacesIdGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdGet: ', $e->getMessage(), PHP_EOL;
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
