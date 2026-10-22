# GoAdServerApi\PushAudienceApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1PublisherPushDeliveryRulesGet()**](PushAudienceApi.md#apiV1PublisherPushDeliveryRulesGet) | **GET** /api/v1/publisher/push-delivery-rules | List standing delivery rules. |
| [**apiV1PublisherPushDeliveryRulesIdDelete()**](PushAudienceApi.md#apiV1PublisherPushDeliveryRulesIdDelete) | **DELETE** /api/v1/publisher/push-delivery-rules/{id} | Delete a delivery rule. |
| [**apiV1PublisherPushDeliveryRulesIdPut()**](PushAudienceApi.md#apiV1PublisherPushDeliveryRulesIdPut) | **PUT** /api/v1/publisher/push-delivery-rules/{id} | Update a delivery rule. |
| [**apiV1PublisherPushDeliveryRulesPost()**](PushAudienceApi.md#apiV1PublisherPushDeliveryRulesPost) | **POST** /api/v1/publisher/push-delivery-rules | Create a delivery rule. |
| [**apiV1PublisherPushSegmentsGet()**](PushAudienceApi.md#apiV1PublisherPushSegmentsGet) | **GET** /api/v1/publisher/push-segments | List saved audience segments. |
| [**apiV1PublisherPushSegmentsIdDelete()**](PushAudienceApi.md#apiV1PublisherPushSegmentsIdDelete) | **DELETE** /api/v1/publisher/push-segments/{id} | Delete an audience segment. |
| [**apiV1PublisherPushSegmentsIdPut()**](PushAudienceApi.md#apiV1PublisherPushSegmentsIdPut) | **PUT** /api/v1/publisher/push-segments/{id} | Update an audience segment. |
| [**apiV1PublisherPushSegmentsPost()**](PushAudienceApi.md#apiV1PublisherPushSegmentsPost) | **POST** /api/v1/publisher/push-segments | Create an audience segment. |
| [**apiV1PublisherPushSetupGet()**](PushAudienceApi.md#apiV1PublisherPushSetupGet) | **GET** /api/v1/publisher/push-setup | Collector setup facts for the opt-in script. |
| [**apiV1PublisherPushStatsGet()**](PushAudienceApi.md#apiV1PublisherPushStatsGet) | **GET** /api/v1/publisher/push-stats | Push delivery performance. |
| [**apiV1PublisherPushSubscribersActionPost()**](PushAudienceApi.md#apiV1PublisherPushSubscribersActionPost) | **POST** /api/v1/publisher/push-subscribers/action | Activate, deactivate or delete subscribers in bulk. |
| [**apiV1PublisherPushSubscribersGet()**](PushAudienceApi.md#apiV1PublisherPushSubscribersGet) | **GET** /api/v1/publisher/push-subscribers | List captured push subscribers. |


## `apiV1PublisherPushDeliveryRulesGet()`

```php
apiV1PublisherPushDeliveryRulesGet(): \GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesGet200Response
```

List standing delivery rules.

Returns up to 200 rules, newest first, each with the outcome of its last run (sent / failed / no-ad counts).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PushAudienceApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1PublisherPushDeliveryRulesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PushAudienceApi->apiV1PublisherPushDeliveryRulesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesGet200Response**](../Model/ApiV1PublisherPushDeliveryRulesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherPushDeliveryRulesIdDelete()`

```php
apiV1PublisherPushDeliveryRulesIdDelete($id): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
```

Delete a delivery rule.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PushAudienceApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Rule ID.

try {
    $result = $apiInstance->apiV1PublisherPushDeliveryRulesIdDelete($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PushAudienceApi->apiV1PublisherPushDeliveryRulesIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Rule ID. | |

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

## `apiV1PublisherPushDeliveryRulesIdPut()`

```php
apiV1PublisherPushDeliveryRulesIdPut($id, $api_v1_publisher_push_delivery_rules_id_put_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Update a delivery rule.

Full replace, including `is_active` — this is also how a rule is paused and resumed.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PushAudienceApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Rule ID.
$api_v1_publisher_push_delivery_rules_id_put_request = new \GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesIdPutRequest(); // \GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesIdPutRequest

try {
    $result = $apiInstance->apiV1PublisherPushDeliveryRulesIdPut($id, $api_v1_publisher_push_delivery_rules_id_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PushAudienceApi->apiV1PublisherPushDeliveryRulesIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Rule ID. | |
| **api_v1_publisher_push_delivery_rules_id_put_request** | [**\GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesIdPutRequest**](../Model/ApiV1PublisherPushDeliveryRulesIdPutRequest.md)|  | |

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

## `apiV1PublisherPushDeliveryRulesPost()`

```php
apiV1PublisherPushDeliveryRulesPost($api_v1_publisher_push_delivery_rules_post_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Create a delivery rule.

`segment_id` must be a segment you own, or 0 for the whole active audience. `cadence_hours` below 1 is rejected into the 24h default; the other numeric knobs clamp to >= 0.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PushAudienceApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_publisher_push_delivery_rules_post_request = new \GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesPostRequest(); // \GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesPostRequest

try {
    $result = $apiInstance->apiV1PublisherPushDeliveryRulesPost($api_v1_publisher_push_delivery_rules_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PushAudienceApi->apiV1PublisherPushDeliveryRulesPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_publisher_push_delivery_rules_post_request** | [**\GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesPostRequest**](../Model/ApiV1PublisherPushDeliveryRulesPostRequest.md)|  | |

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

## `apiV1PublisherPushSegmentsGet()`

```php
apiV1PublisherPushSegmentsGet(): \GoAdServerApi\Model\ApiV1PublisherPushSegmentsGet200Response
```

List saved audience segments.

Returns up to 200 segments, newest first. Each carries `subscriber_count` — how many of the caller's subscribers match the segment right now, counted live.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PushAudienceApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1PublisherPushSegmentsGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PushAudienceApi->apiV1PublisherPushSegmentsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushSegmentsGet200Response**](../Model/ApiV1PublisherPushSegmentsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherPushSegmentsIdDelete()`

```php
apiV1PublisherPushSegmentsIdDelete($id): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
```

Delete an audience segment.

Deletes the segment. Delivery rules that referenced it keep running against the whole active audience.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PushAudienceApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Segment ID.

try {
    $result = $apiInstance->apiV1PublisherPushSegmentsIdDelete($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PushAudienceApi->apiV1PublisherPushSegmentsIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Segment ID. | |

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

## `apiV1PublisherPushSegmentsIdPut()`

```php
apiV1PublisherPushSegmentsIdPut($id, $api_v1_publisher_push_segments_id_put_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Update an audience segment.

Full replace — every omitted criterion resets to its zero value. Saving identical values is a no-op, not a 404.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PushAudienceApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Segment ID.
$api_v1_publisher_push_segments_id_put_request = new \GoAdServerApi\Model\ApiV1PublisherPushSegmentsIdPutRequest(); // \GoAdServerApi\Model\ApiV1PublisherPushSegmentsIdPutRequest

try {
    $result = $apiInstance->apiV1PublisherPushSegmentsIdPut($id, $api_v1_publisher_push_segments_id_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PushAudienceApi->apiV1PublisherPushSegmentsIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Segment ID. | |
| **api_v1_publisher_push_segments_id_put_request** | [**\GoAdServerApi\Model\ApiV1PublisherPushSegmentsIdPutRequest**](../Model/ApiV1PublisherPushSegmentsIdPutRequest.md)|  | |

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

## `apiV1PublisherPushSegmentsPost()`

```php
apiV1PublisherPushSegmentsPost($api_v1_publisher_push_segments_post_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Create an audience segment.

Every criterion is optional and they AND together — a segment with no criteria set matches the whole audience. Only `name` is required. Numeric criteria are clamped to >= 0.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PushAudienceApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_publisher_push_segments_post_request = new \GoAdServerApi\Model\ApiV1PublisherPushSegmentsPostRequest(); // \GoAdServerApi\Model\ApiV1PublisherPushSegmentsPostRequest

try {
    $result = $apiInstance->apiV1PublisherPushSegmentsPost($api_v1_publisher_push_segments_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PushAudienceApi->apiV1PublisherPushSegmentsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_publisher_push_segments_post_request** | [**\GoAdServerApi\Model\ApiV1PublisherPushSegmentsPostRequest**](../Model/ApiV1PublisherPushSegmentsPostRequest.md)|  | |

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

## `apiV1PublisherPushSetupGet()`

```php
apiV1PublisherPushSetupGet(): \GoAdServerApi\Model\ApiV1PublisherPushSetupGet200Response
```

Collector setup facts for the opt-in script.

Everything needed to wire the web-push opt-in collector onto a landing page: the ad host the collector and service worker are served from, the service-worker path this network expects, and every pushads adzone you own with its ad domain, captured subscriber count and most recent opt-in. Read-only — nothing is generated or registered by calling it.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PushAudienceApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1PublisherPushSetupGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PushAudienceApi->apiV1PublisherPushSetupGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushSetupGet200Response**](../Model/ApiV1PublisherPushSetupGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherPushStatsGet()`

```php
apiV1PublisherPushStatsGet($days): \GoAdServerApi\Model\ApiV1PublisherPushStatsGet200Response
```

Push delivery performance.

Delivery, clicks and revenue for auctioned pushes served on the caller's pushads adzones, paired with subscriber growth over the same window. Revenue is in the account currency.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PushAudienceApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$days = 30; // int | Trailing window in days. Default 30, capped at 365.

try {
    $result = $apiInstance->apiV1PublisherPushStatsGet($days);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PushAudienceApi->apiV1PublisherPushStatsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **days** | **int**| Trailing window in days. Default 30, capped at 365. | [optional] [default to 30] |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushStatsGet200Response**](../Model/ApiV1PublisherPushStatsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherPushSubscribersActionPost()`

```php
apiV1PublisherPushSubscribersActionPost($api_v1_publisher_push_subscribers_action_post_request): \GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPost200Response
```

Activate, deactivate or delete subscribers in bulk.

One action over up to 5000 subscriber ids. Ids that are not the caller's are silently skipped — `affected` reports how many rows actually changed.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PushAudienceApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_publisher_push_subscribers_action_post_request = new \GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPostRequest(); // \GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPostRequest

try {
    $result = $apiInstance->apiV1PublisherPushSubscribersActionPost($api_v1_publisher_push_subscribers_action_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PushAudienceApi->apiV1PublisherPushSubscribersActionPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_publisher_push_subscribers_action_post_request** | [**\GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPostRequest**](../Model/ApiV1PublisherPushSubscribersActionPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPost200Response**](../Model/ApiV1PublisherPushSubscribersActionPost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherPushSubscribersGet()`

```php
apiV1PublisherPushSubscribersGet($days): \GoAdServerApi\Model\ApiV1PublisherPushSubscribersGet200Response
```

List captured push subscribers.

The caller's opt-ins within the trailing window, newest first, capped at 1000 rows — alongside lifetime totals so you can size the audience without paging. Subscriber IPs are masked to a /24 (IPv4) or /48 (IPv6) prefix on the way out.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PushAudienceApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$days = 30; // int | Trailing window in days. Default 30, capped at 365.

try {
    $result = $apiInstance->apiV1PublisherPushSubscribersGet($days);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PushAudienceApi->apiV1PublisherPushSubscribersGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **days** | **int**| Trailing window in days. Default 30, capped at 365. | [optional] [default to 30] |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushSubscribersGet200Response**](../Model/ApiV1PublisherPushSubscribersGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
