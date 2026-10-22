# GoAdServerApi\PushCampaignsApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1PublisherPushCampaignsGet()**](PushCampaignsApi.md#apiV1PublisherPushCampaignsGet) | **GET** /api/v1/publisher/push-campaigns | List the caller&#39;s push campaigns. |
| [**apiV1PublisherPushCampaignsIdDelete()**](PushCampaignsApi.md#apiV1PublisherPushCampaignsIdDelete) | **DELETE** /api/v1/publisher/push-campaigns/{id} | Delete a push campaign. |
| [**apiV1PublisherPushCampaignsIdPut()**](PushCampaignsApi.md#apiV1PublisherPushCampaignsIdPut) | **PUT** /api/v1/publisher/push-campaigns/{id} | Update a push campaign&#39;s notification content. |
| [**apiV1PublisherPushCampaignsIdSendPost()**](PushCampaignsApi.md#apiV1PublisherPushCampaignsIdSendPost) | **POST** /api/v1/publisher/push-campaigns/{id}/send | Queue a push campaign for delivery. |
| [**apiV1PublisherPushCampaignsPost()**](PushCampaignsApi.md#apiV1PublisherPushCampaignsPost) | **POST** /api/v1/publisher/push-campaigns | Create a push campaign. |


## `apiV1PublisherPushCampaignsGet()`

```php
apiV1PublisherPushCampaignsGet(): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsGet200Response
```

List the caller's push campaigns.

Returns the most recent 200 push campaigns owned by the caller, newest first, with their delivery counts.  Web-push is a publisher capability, so all push endpoints live under `/api/v1/publisher/push-*`. The former `/api/v1/tracker/push-*` paths remain mounted as **deprecated aliases** (same handlers, same scopes) for back-compat — prefer the `publisher` prefix in new integrations.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PushCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1PublisherPushCampaignsGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PushCampaignsApi->apiV1PublisherPushCampaignsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushCampaignsGet200Response**](../Model/ApiV1PublisherPushCampaignsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherPushCampaignsIdDelete()`

```php
apiV1PublisherPushCampaignsIdDelete($id): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
```

Delete a push campaign.

Deletes the campaign. A campaign currently `sending` cannot be deleted (it would vanish under the sender) and returns 404.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PushCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Campaign ID.

try {
    $result = $apiInstance->apiV1PublisherPushCampaignsIdDelete($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PushCampaignsApi->apiV1PublisherPushCampaignsIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Campaign ID. | |

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

## `apiV1PublisherPushCampaignsIdPut()`

```php
apiV1PublisherPushCampaignsIdPut($id, $api_v1_publisher_push_campaigns_post_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Update a push campaign's notification content.

Replaces the notification fields. Counters, status, audience and timestamps belong to the sender and are not writable here. Only a campaign at rest (`draft`, `sent`, `failed`) is editable — one that is `queued` or `sending` returns 409.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PushCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Campaign ID.
$api_v1_publisher_push_campaigns_post_request = new \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPostRequest(); // \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPostRequest

try {
    $result = $apiInstance->apiV1PublisherPushCampaignsIdPut($id, $api_v1_publisher_push_campaigns_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PushCampaignsApi->apiV1PublisherPushCampaignsIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Campaign ID. | |
| **api_v1_publisher_push_campaigns_post_request** | [**\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPostRequest**](../Model/ApiV1PublisherPushCampaignsPostRequest.md)|  | |

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

## `apiV1PublisherPushCampaignsIdSendPost()`

```php
apiV1PublisherPushCampaignsIdSendPost($id): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdSendPost200Response
```

Queue a push campaign for delivery.

Flips the campaign to `queued`; the push sender picks it up within ~60s and delivers it to every active web-push subscriber of the network. Only campaigns at rest (`draft`, `sent`, `failed`) can be queued — a campaign already `queued` or `sending` returns 409.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PushCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Campaign ID.

try {
    $result = $apiInstance->apiV1PublisherPushCampaignsIdSendPost($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PushCampaignsApi->apiV1PublisherPushCampaignsIdSendPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Campaign ID. | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdSendPost200Response**](../Model/ApiV1PublisherPushCampaignsIdSendPost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherPushCampaignsPost()`

```php
apiV1PublisherPushCampaignsPost($api_v1_publisher_push_campaigns_post_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Create a push campaign.

Creates the campaign in `draft` status — it is not delivered until you queue it with `POST /api/v1/publisher/push-campaigns/{id}/send`. Only `title` is required.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PushCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_publisher_push_campaigns_post_request = new \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPostRequest(); // \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPostRequest

try {
    $result = $apiInstance->apiV1PublisherPushCampaignsPost($api_v1_publisher_push_campaigns_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PushCampaignsApi->apiV1PublisherPushCampaignsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_publisher_push_campaigns_post_request** | [**\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPostRequest**](../Model/ApiV1PublisherPushCampaignsPostRequest.md)|  | |

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
