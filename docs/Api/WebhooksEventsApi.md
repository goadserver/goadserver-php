# GoAdServerApi\WebhooksEventsApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1EventsGet()**](WebhooksEventsApi.md#apiV1EventsGet) | **GET** /api/v1/events | Read the account&#39;s event feed. |
| [**apiV1WebhooksGet()**](WebhooksEventsApi.md#apiV1WebhooksGet) | **GET** /api/v1/webhooks | List webhooks. |
| [**apiV1WebhooksIdDelete()**](WebhooksEventsApi.md#apiV1WebhooksIdDelete) | **DELETE** /api/v1/webhooks/{id} | Remove a webhook. |
| [**apiV1WebhooksIdDeliveriesDeliveryRedeliverPost()**](WebhooksEventsApi.md#apiV1WebhooksIdDeliveriesDeliveryRedeliverPost) | **POST** /api/v1/webhooks/{id}/deliveries/{delivery}/redeliver | Send a delivery again. |
| [**apiV1WebhooksIdDeliveriesGet()**](WebhooksEventsApi.md#apiV1WebhooksIdDeliveriesGet) | **GET** /api/v1/webhooks/{id}/deliveries | List a webhook&#39;s deliveries. |
| [**apiV1WebhooksIdGet()**](WebhooksEventsApi.md#apiV1WebhooksIdGet) | **GET** /api/v1/webhooks/{id} | Get one webhook. |
| [**apiV1WebhooksIdPatch()**](WebhooksEventsApi.md#apiV1WebhooksIdPatch) | **PATCH** /api/v1/webhooks/{id} | Edit, pause or resume a webhook. |
| [**apiV1WebhooksIdRotateSecretPost()**](WebhooksEventsApi.md#apiV1WebhooksIdRotateSecretPost) | **POST** /api/v1/webhooks/{id}/rotate-secret | Issue a new signing secret. |
| [**apiV1WebhooksIdTestPost()**](WebhooksEventsApi.md#apiV1WebhooksIdTestPost) | **POST** /api/v1/webhooks/{id}/test | Send a test ping. |
| [**apiV1WebhooksPost()**](WebhooksEventsApi.md#apiV1WebhooksPost) | **POST** /api/v1/webhooks | Create a webhook. |


## `apiV1EventsGet()`

```php
apiV1EventsGet($since, $types, $limit): \GoAdServerApi\Model\ApiV1EventsGet200Response
```

Read the account's event feed.

The same events the webhooks carry, for agents that cannot receive webhooks. Oldest first, kept **7 days**. Page with the cursor: pass `next_cursor` from one answer as `since` in the next; `has_more` says whether to call again at once. `since=now` returns no events, only the current cursor — the way to start following from now.  Only the events this credential may see are returned: each event needs the scope of what it is about (see the tag description), and a key pinned to campaigns, ad spaces or sites only sees events about those (and never the account-wide `conversion.summary`). The cursor moves past events you may not see, so a reader never stalls.  Events are recorded only for accounts that have a live API key, a connected app or a webhook. `conversion.received` is never in the feed (webhook opt-in only).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\WebhooksEventsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$since = 'since_example'; // string | Cursor from `next_cursor`, `now`, or omit for the whole 7 days.
$types = 'types_example'; // string | Comma-separated event types to keep, e.g. `campaign.paused,ad.rejected`.
$limit = 100; // int

try {
    $result = $apiInstance->apiV1EventsGet($since, $types, $limit);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling WebhooksEventsApi->apiV1EventsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **since** | **string**| Cursor from &#x60;next_cursor&#x60;, &#x60;now&#x60;, or omit for the whole 7 days. | [optional] |
| **types** | **string**| Comma-separated event types to keep, e.g. &#x60;campaign.paused,ad.rejected&#x60;. | [optional] |
| **limit** | **int**|  | [optional] [default to 100] |

### Return type

[**\GoAdServerApi\Model\ApiV1EventsGet200Response**](../Model/ApiV1EventsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1WebhooksGet()`

```php
apiV1WebhooksGet(): \GoAdServerApi\Model\ApiV1WebhooksGet200Response
```

List webhooks.

An API key or app sees the webhooks **it** created (the panel shows all of the account's). `available_events` lists the events this credential may subscribe to; `limit` is the per-account cap (20).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\WebhooksEventsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1WebhooksGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling WebhooksEventsApi->apiV1WebhooksGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1WebhooksGet200Response**](../Model/ApiV1WebhooksGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1WebhooksIdDelete()`

```php
apiV1WebhooksIdDelete($id): \GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response
```

Remove a webhook.

Deliveries still waiting for it are dropped and never sent.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\WebhooksEventsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $result = $apiInstance->apiV1WebhooksIdDelete($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling WebhooksEventsApi->apiV1WebhooksIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response**](../Model/ApiV1WebhooksIdDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1WebhooksIdDeliveriesDeliveryRedeliverPost()`

```php
apiV1WebhooksIdDeliveriesDeliveryRedeliverPost($id, $delivery): \GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response
```

Send a delivery again.

Delivered, failed or waiting — it is queued again with its original event id. The content comes from the 7-day event feed when the delivery itself no longer holds it (410 after that).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\WebhooksEventsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$delivery = 56; // int

try {
    $result = $apiInstance->apiV1WebhooksIdDeliveriesDeliveryRedeliverPost($id, $delivery);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling WebhooksEventsApi->apiV1WebhooksIdDeliveriesDeliveryRedeliverPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **delivery** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response**](../Model/ApiV1WebhooksIdDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1WebhooksIdDeliveriesGet()`

```php
apiV1WebhooksIdDeliveriesGet($id, $status, $limit): \GoAdServerApi\Model\ApiV1WebhooksIdDeliveriesGet200Response
```

List a webhook's deliveries.

The last 30 days, newest first.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\WebhooksEventsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$status = 'status_example'; // string
$limit = 50; // int

try {
    $result = $apiInstance->apiV1WebhooksIdDeliveriesGet($id, $status, $limit);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling WebhooksEventsApi->apiV1WebhooksIdDeliveriesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **status** | **string**|  | [optional] |
| **limit** | **int**|  | [optional] [default to 50] |

### Return type

[**\GoAdServerApi\Model\ApiV1WebhooksIdDeliveriesGet200Response**](../Model/ApiV1WebhooksIdDeliveriesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1WebhooksIdGet()`

```php
apiV1WebhooksIdGet($id): \GoAdServerApi\Model\ApiV1WebhooksPost201Response
```

Get one webhook.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\WebhooksEventsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $result = $apiInstance->apiV1WebhooksIdGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling WebhooksEventsApi->apiV1WebhooksIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1WebhooksPost201Response**](../Model/ApiV1WebhooksPost201Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1WebhooksIdPatch()`

```php
apiV1WebhooksIdPatch($id, $api_v1_webhooks_id_patch_request): \GoAdServerApi\Model\ApiV1WebhooksPost201Response
```

Edit, pause or resume a webhook.

Omitted fields keep their value; the result is validated like a create. `status: paused` stops new events (read them from `/api/v1/events` meanwhile — kept 7 days) and holds what was already queued; `active` resumes and sends what was held. An event taken off the webhook drops its waiting deliveries. The secret stays.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\WebhooksEventsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_webhooks_id_patch_request = new \GoAdServerApi\Model\ApiV1WebhooksIdPatchRequest(); // \GoAdServerApi\Model\ApiV1WebhooksIdPatchRequest

try {
    $result = $apiInstance->apiV1WebhooksIdPatch($id, $api_v1_webhooks_id_patch_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling WebhooksEventsApi->apiV1WebhooksIdPatch: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_webhooks_id_patch_request** | [**\GoAdServerApi\Model\ApiV1WebhooksIdPatchRequest**](../Model/ApiV1WebhooksIdPatchRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1WebhooksPost201Response**](../Model/ApiV1WebhooksPost201Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1WebhooksIdRotateSecretPost()`

```php
apiV1WebhooksIdRotateSecretPost($id, $api_v1_webhooks_id_rotate_secret_post_request): \GoAdServerApi\Model\ApiV1WebhooksPost201Response
```

Issue a new signing secret.

For `overlap_hours` (0..168, default 24) the previous secret keeps signing too — two `v1=` values — so you can switch whenever you like. `0` ends the old secret at once. The new `secret` is in the answer only.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\WebhooksEventsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_webhooks_id_rotate_secret_post_request = new \GoAdServerApi\Model\ApiV1WebhooksIdRotateSecretPostRequest(); // \GoAdServerApi\Model\ApiV1WebhooksIdRotateSecretPostRequest

try {
    $result = $apiInstance->apiV1WebhooksIdRotateSecretPost($id, $api_v1_webhooks_id_rotate_secret_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling WebhooksEventsApi->apiV1WebhooksIdRotateSecretPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_webhooks_id_rotate_secret_post_request** | [**\GoAdServerApi\Model\ApiV1WebhooksIdRotateSecretPostRequest**](../Model/ApiV1WebhooksIdRotateSecretPostRequest.md)|  | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1WebhooksPost201Response**](../Model/ApiV1WebhooksPost201Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1WebhooksIdTestPost()`

```php
apiV1WebhooksIdTestPost($id): \GoAdServerApi\Model\ApiV1WebhooksIdTestPost200Response
```

Send a test ping.

Sends a signed `webhook.test` event now and answers what the receiver said (`status` delivered / pending / dead, `http_status`, `error`). A paused webhook only queues it.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\WebhooksEventsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $result = $apiInstance->apiV1WebhooksIdTestPost($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling WebhooksEventsApi->apiV1WebhooksIdTestPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1WebhooksIdTestPost200Response**](../Model/ApiV1WebhooksIdTestPost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1WebhooksPost()`

```php
apiV1WebhooksPost($api_v1_webhooks_post_request): \GoAdServerApi\Model\ApiV1WebhooksPost201Response
```

Create a webhook.

Returns the signing `secret` (`whsec_…`) **once**. Every delivery is a POST of the event envelope with `X-Integration-Event`, `X-Integration-Delivery` (the event id — dedupe on it, delivery is at-least-once), `X-Integration-Key-Id` and `X-Integration-Signature: t=<unix>,v1=<hex HMAC-SHA256(secret, \"<t>.<body>\")>` (check every `v1`; during a secret rotation there are two). Answer 2xx within 10 s. Failures retry after 1 m, 5 m, 15 m, 1 h, 3 h, 6 h, 12 h, 24 h; a 4xx other than 408/429 is final; redirects are not followed. 20 failures in a row over a day pause the webhook (`paused_reason: auto_failing`). Events are queued within about a minute (state changes detected by the server's checks within about two to five).  The URL must be a public `https://` address — private, loopback, link-local, metadata and reserved addresses are refused, when saved and again when sent. Each event must be one this credential may see (422 names the missing scope). Unknown body fields are refused.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\WebhooksEventsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_webhooks_post_request = new \GoAdServerApi\Model\ApiV1WebhooksPostRequest(); // \GoAdServerApi\Model\ApiV1WebhooksPostRequest

try {
    $result = $apiInstance->apiV1WebhooksPost($api_v1_webhooks_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling WebhooksEventsApi->apiV1WebhooksPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_webhooks_post_request** | [**\GoAdServerApi\Model\ApiV1WebhooksPostRequest**](../Model/ApiV1WebhooksPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1WebhooksPost201Response**](../Model/ApiV1WebhooksPost201Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
