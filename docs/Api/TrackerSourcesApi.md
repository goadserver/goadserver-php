# GoAdServerApi\TrackerSourcesApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1TrackerCostDriversGet()**](TrackerSourcesApi.md#apiV1TrackerCostDriversGet) | **GET** /api/v1/tracker/cost-drivers | List available cost drivers. |
| [**apiV1TrackerSourceAccountsGet()**](TrackerSourcesApi.md#apiV1TrackerSourceAccountsGet) | **GET** /api/v1/tracker/source-accounts | List connected ad accounts. |
| [**apiV1TrackerSourceAccountsIdCampaignsGet()**](TrackerSourcesApi.md#apiV1TrackerSourceAccountsIdCampaignsGet) | **GET** /api/v1/tracker/source-accounts/{id}/campaigns | Campaigns visible in a connected ad account. |
| [**apiV1TrackerSourceAccountsIdDelete()**](TrackerSourcesApi.md#apiV1TrackerSourceAccountsIdDelete) | **DELETE** /api/v1/tracker/source-accounts/{id} | Disconnect an ad account. |
| [**apiV1TrackerSourceAccountsIdGet()**](TrackerSourcesApi.md#apiV1TrackerSourceAccountsIdGet) | **GET** /api/v1/tracker/source-accounts/{id} | Get one connected ad account. |
| [**apiV1TrackerSourceAccountsIdPut()**](TrackerSourcesApi.md#apiV1TrackerSourceAccountsIdPut) | **PUT** /api/v1/tracker/source-accounts/{id} | Update a connected ad account. |
| [**apiV1TrackerSourceAccountsIdSyncPost()**](TrackerSourcesApi.md#apiV1TrackerSourceAccountsIdSyncPost) | **POST** /api/v1/tracker/source-accounts/{id}/sync | Pull spend from a connected ad account now. |
| [**apiV1TrackerSourceAccountsPost()**](TrackerSourcesApi.md#apiV1TrackerSourceAccountsPost) | **POST** /api/v1/tracker/source-accounts | Connect an ad account. |
| [**apiV1TrackerSourcesGet()**](TrackerSourcesApi.md#apiV1TrackerSourcesGet) | **GET** /api/v1/tracker/sources | List traffic sources. |
| [**apiV1TrackerSourcesIdDelete()**](TrackerSourcesApi.md#apiV1TrackerSourcesIdDelete) | **DELETE** /api/v1/tracker/sources/{id} | Delete a traffic source. |
| [**apiV1TrackerSourcesIdPut()**](TrackerSourcesApi.md#apiV1TrackerSourcesIdPut) | **PUT** /api/v1/tracker/sources/{id} | Update a traffic source. |
| [**apiV1TrackerSourcesPost()**](TrackerSourcesApi.md#apiV1TrackerSourcesPost) | **POST** /api/v1/tracker/sources | Create a traffic source. |
| [**apiV1TrackerSourcesSiteidCampaignsGet()**](TrackerSourcesApi.md#apiV1TrackerSourcesSiteidCampaignsGet) | **GET** /api/v1/tracker/sources/{siteid}/campaigns | Campaigns reported by a source&#39;s connected ad account. |


## `apiV1TrackerCostDriversGet()`

```php
apiV1TrackerCostDriversGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

List available cost drivers.

The traffic-source APIs this build can pull spend from, and which are actually implemented — the valid values for `driver` above.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerSourcesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1TrackerCostDriversGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerSourcesApi->apiV1TrackerCostDriversGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1TrackerSourceAccountsGet()`

```php
apiV1TrackerSourceAccountsGet(): \GoAdServerApi\Model\ApiV1TrackerSourceAccountsGet200Response
```

List connected ad accounts.

The credentialed ad accounts a scheduler pulls spend from, each with the outcome of its last sync. Credentials are never returned.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerSourcesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1TrackerSourceAccountsGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerSourcesApi->apiV1TrackerSourceAccountsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerSourceAccountsGet200Response**](../Model/ApiV1TrackerSourceAccountsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerSourceAccountsIdCampaignsGet()`

```php
apiV1TrackerSourceAccountsIdCampaignsGet($id): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Campaigns visible in a connected ad account.

Used to bind a tracking link to a campaign on the buying side.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerSourcesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Source account ID.

try {
    $result = $apiInstance->apiV1TrackerSourceAccountsIdCampaignsGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerSourcesApi->apiV1TrackerSourceAccountsIdCampaignsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Source account ID. | |

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

## `apiV1TrackerSourceAccountsIdDelete()`

```php
apiV1TrackerSourceAccountsIdDelete($id): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
```

Disconnect an ad account.

Stops the spend pull. Already-imported cost stays.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerSourcesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Source account ID.

try {
    $result = $apiInstance->apiV1TrackerSourceAccountsIdDelete($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerSourcesApi->apiV1TrackerSourceAccountsIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Source account ID. | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response**](../Model/ApiV1PublisherPushCampaignsIdDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerSourceAccountsIdGet()`

```php
apiV1TrackerSourceAccountsIdGet($id): \GoAdServerApi\Model\ApiV1TrackerSourceAccountsIdGet200Response
```

Get one connected ad account.

Credentials are omitted.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerSourcesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Source account ID.

try {
    $result = $apiInstance->apiV1TrackerSourceAccountsIdGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerSourcesApi->apiV1TrackerSourceAccountsIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Source account ID. | |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerSourceAccountsIdGet200Response**](../Model/ApiV1TrackerSourceAccountsIdGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerSourceAccountsIdPut()`

```php
apiV1TrackerSourceAccountsIdPut($id, $api_v1_tracker_source_accounts_id_put_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Update a connected ad account.

Omit `credentials` to leave the stored secrets untouched.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerSourcesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Source account ID.
$api_v1_tracker_source_accounts_id_put_request = new \GoAdServerApi\Model\ApiV1TrackerSourceAccountsIdPutRequest(); // \GoAdServerApi\Model\ApiV1TrackerSourceAccountsIdPutRequest

try {
    $result = $apiInstance->apiV1TrackerSourceAccountsIdPut($id, $api_v1_tracker_source_accounts_id_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerSourcesApi->apiV1TrackerSourceAccountsIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Source account ID. | |
| **api_v1_tracker_source_accounts_id_put_request** | [**\GoAdServerApi\Model\ApiV1TrackerSourceAccountsIdPutRequest**](../Model/ApiV1TrackerSourceAccountsIdPutRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response**](../Model/ApiV1PublisherPushCampaignsPost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerSourceAccountsIdSyncPost()`

```php
apiV1TrackerSourceAccountsIdSyncPost($id): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Pull spend from a connected ad account now.

Runs the driver immediately instead of waiting for the daily schedule. Returns what the run imported, or the driver's error.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerSourcesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Source account ID.

try {
    $result = $apiInstance->apiV1TrackerSourceAccountsIdSyncPost($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerSourcesApi->apiV1TrackerSourceAccountsIdSyncPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Source account ID. | |

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

## `apiV1TrackerSourceAccountsPost()`

```php
apiV1TrackerSourceAccountsPost($api_v1_tracker_source_accounts_post_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Connect an ad account.

`driver` names the traffic source's API (see /cost-drivers). `credentials` is encrypted at rest and never read back.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerSourcesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_tracker_source_accounts_post_request = new \GoAdServerApi\Model\ApiV1TrackerSourceAccountsPostRequest(); // \GoAdServerApi\Model\ApiV1TrackerSourceAccountsPostRequest

try {
    $result = $apiInstance->apiV1TrackerSourceAccountsPost($api_v1_tracker_source_accounts_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerSourcesApi->apiV1TrackerSourceAccountsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_tracker_source_accounts_post_request** | [**\GoAdServerApi\Model\ApiV1TrackerSourceAccountsPostRequest**](../Model/ApiV1TrackerSourceAccountsPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response**](../Model/ApiV1PublisherPushCampaignsPost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerSourcesGet()`

```php
apiV1TrackerSourcesGet(): \GoAdServerApi\Model\ApiV1TrackerSourcesGet200Response
```

List traffic sources.

One row per traffic origin (an ad account, a network). A source carries the macro template its click URLs are built from and its own outbound postback configuration, and is the unit member access is delegated by.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerSourcesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1TrackerSourcesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerSourcesApi->apiV1TrackerSourcesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerSourcesGet200Response**](../Model/ApiV1TrackerSourcesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerSourcesIdDelete()`

```php
apiV1TrackerSourcesIdDelete($id): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
```

Delete a traffic source.

Refused while live tracking links still hang off the source — they would orphan. Delete or move the links first.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerSourcesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Source ID.

try {
    $result = $apiInstance->apiV1TrackerSourcesIdDelete($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerSourcesApi->apiV1TrackerSourcesIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Source ID. | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response**](../Model/ApiV1PublisherPushCampaignsIdDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerSourcesIdPut()`

```php
apiV1TrackerSourcesIdPut($id, $api_v1_tracker_sources_id_put_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Update a traffic source.

Partial update.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerSourcesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Source ID.
$api_v1_tracker_sources_id_put_request = new \GoAdServerApi\Model\ApiV1TrackerSourcesIdPutRequest(); // \GoAdServerApi\Model\ApiV1TrackerSourcesIdPutRequest

try {
    $result = $apiInstance->apiV1TrackerSourcesIdPut($id, $api_v1_tracker_sources_id_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerSourcesApi->apiV1TrackerSourcesIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Source ID. | |
| **api_v1_tracker_sources_id_put_request** | [**\GoAdServerApi\Model\ApiV1TrackerSourcesIdPutRequest**](../Model/ApiV1TrackerSourcesIdPutRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response**](../Model/ApiV1PublisherPushCampaignsPost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerSourcesPost()`

```php
apiV1TrackerSourcesPost($api_v1_tracker_sources_post_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Create a traffic source.

`template_slug` picks the macro set (see /source-templates); it decides which placeholders the generated click URLs carry.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerSourcesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_tracker_sources_post_request = new \GoAdServerApi\Model\ApiV1TrackerSourcesPostRequest(); // \GoAdServerApi\Model\ApiV1TrackerSourcesPostRequest

try {
    $result = $apiInstance->apiV1TrackerSourcesPost($api_v1_tracker_sources_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerSourcesApi->apiV1TrackerSourcesPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_tracker_sources_post_request** | [**\GoAdServerApi\Model\ApiV1TrackerSourcesPostRequest**](../Model/ApiV1TrackerSourcesPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response**](../Model/ApiV1PublisherPushCampaignsPost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerSourcesSiteidCampaignsGet()`

```php
apiV1TrackerSourcesSiteidCampaignsGet($siteid): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Campaigns reported by a source's connected ad account.

What the buying side calls its campaigns, for binding tracking links to.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerSourcesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$siteid = 56; // int | Source ID.

try {
    $result = $apiInstance->apiV1TrackerSourcesSiteidCampaignsGet($siteid);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerSourcesApi->apiV1TrackerSourcesSiteidCampaignsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **siteid** | **int**| Source ID. | |

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
