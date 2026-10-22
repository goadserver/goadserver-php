# GoAdServerApi\AccountNotificationsApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1AccountNotificationDestinationsGet()**](AccountNotificationsApi.md#apiV1AccountNotificationDestinationsGet) | **GET** /api/v1/account/notification-destinations | The account&#39;s notification destinations. |
| [**apiV1AccountNotificationDestinationsIdDelete()**](AccountNotificationsApi.md#apiV1AccountNotificationDestinationsIdDelete) | **DELETE** /api/v1/account/notification-destinations/{id} | Delete a notification destination. |
| [**apiV1AccountNotificationDestinationsIdStatusGet()**](AccountNotificationsApi.md#apiV1AccountNotificationDestinationsIdStatusGet) | **GET** /api/v1/account/notification-destinations/{id}/status | Whether a destination is linked. |
| [**apiV1AccountNotificationDestinationsIdTestPost()**](AccountNotificationsApi.md#apiV1AccountNotificationDestinationsIdTestPost) | **POST** /api/v1/account/notification-destinations/{id}/test | Send a test message to a destination. |
| [**apiV1AccountNotificationDestinationsPost()**](AccountNotificationsApi.md#apiV1AccountNotificationDestinationsPost) | **POST** /api/v1/account/notification-destinations | Add a notification destination. |
| [**apiV1AccountNotificationPreferencesGet()**](AccountNotificationsApi.md#apiV1AccountNotificationPreferencesGet) | **GET** /api/v1/account/notification-preferences | Per-event channel preferences. |
| [**apiV1AccountNotificationPreferencesPut()**](AccountNotificationsApi.md#apiV1AccountNotificationPreferencesPut) | **PUT** /api/v1/account/notification-preferences | Save channel preferences. |
| [**apiV1AccountNotificationsGet()**](AccountNotificationsApi.md#apiV1AccountNotificationsGet) | **GET** /api/v1/account/notifications | The in-app notification feed. |
| [**apiV1AccountNotificationsReadPost()**](AccountNotificationsApi.md#apiV1AccountNotificationsReadPost) | **POST** /api/v1/account/notifications/read | Mark notifications read. |
| [**apiV1AccountNotificationsUnreadCountGet()**](AccountNotificationsApi.md#apiV1AccountNotificationsUnreadCountGet) | **GET** /api/v1/account/notifications/unread-count | Unread notification count. |


## `apiV1AccountNotificationDestinationsGet()`

```php
apiV1AccountNotificationDestinationsGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

The account's notification destinations.

User-created Telegram bots and Slack webhooks notifications can route to. Secrets are never returned — only the kind, label and whether the channel is linked.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountNotificationsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1AccountNotificationDestinationsGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountNotificationsApi->apiV1AccountNotificationDestinationsGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1AccountNotificationDestinationsIdDelete()`

```php
apiV1AccountNotificationDestinationsIdDelete($id): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Delete a notification destination.

Events routed to it fall back to email.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountNotificationsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Destination ID.

try {
    $result = $apiInstance->apiV1AccountNotificationDestinationsIdDelete($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountNotificationsApi->apiV1AccountNotificationDestinationsIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Destination ID. | |

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

## `apiV1AccountNotificationDestinationsIdStatusGet()`

```php
apiV1AccountNotificationDestinationsIdStatusGet($id): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Whether a destination is linked.

A Telegram bot is only usable once the user has started a chat with it; this reports whether that has happened.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountNotificationsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Destination ID.

try {
    $result = $apiInstance->apiV1AccountNotificationDestinationsIdStatusGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountNotificationsApi->apiV1AccountNotificationDestinationsIdStatusGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Destination ID. | |

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

## `apiV1AccountNotificationDestinationsIdTestPost()`

```php
apiV1AccountNotificationDestinationsIdTestPost($id): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Send a test message to a destination.

**Sends a real message** to the linked chat or channel. Use it to prove a destination works; it is not a dry run.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountNotificationsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Destination ID.

try {
    $result = $apiInstance->apiV1AccountNotificationDestinationsIdTestPost($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountNotificationsApi->apiV1AccountNotificationDestinationsIdTestPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Destination ID. | |

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

## `apiV1AccountNotificationDestinationsPost()`

```php
apiV1AccountNotificationDestinationsPost($api_v1_account_notification_destinations_post_request): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Add a notification destination.

A Telegram bot (`token`) or a Slack incoming webhook (`url`). The secret is stored write-only and never read back.  **Creating a destination contacts it.** A Slack webhook is posted a \"destination connected\" message and a Telegram token is checked against Telegram's API, both before the row is written — so a destination that exists is one that has been proven to work. A URL that is well-formed but dead answers 400 `unreachable`; one that is not webhook-shaped answers 400 `invalid_url`; a rejected bot token answers 400 `invalid_token`.  There is no way to register a destination without that round-trip, which is deliberate: a silently broken notification channel is worse than a create that fails loudly.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountNotificationsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_account_notification_destinations_post_request = new \GoAdServerApi\Model\ApiV1AccountNotificationDestinationsPostRequest(); // \GoAdServerApi\Model\ApiV1AccountNotificationDestinationsPostRequest

try {
    $result = $apiInstance->apiV1AccountNotificationDestinationsPost($api_v1_account_notification_destinations_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountNotificationsApi->apiV1AccountNotificationDestinationsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_account_notification_destinations_post_request** | [**\GoAdServerApi\Model\ApiV1AccountNotificationDestinationsPostRequest**](../Model/ApiV1AccountNotificationDestinationsPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountNotificationPreferencesGet()`

```php
apiV1AccountNotificationPreferencesGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Per-event channel preferences.

The full catalogue of notifiable events with, for each, whether email and in-app are on, which destination it routes to, and whether it is mandatory (mandatory = a security notice: on by default and always sent immediately, never folded into a digest; it can still be switched off).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountNotificationsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1AccountNotificationPreferencesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountNotificationsApi->apiV1AccountNotificationPreferencesGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1AccountNotificationPreferencesPut()`

```php
apiV1AccountNotificationPreferencesPut($api_v1_account_notification_preferences_put_request): \GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response
```

Save channel preferences.

Bulk save of the per-event grid. Every visible event, mandatory ones included, takes the sent value; unknown or hidden keys are ignored, so a client can send the whole catalogue back unchanged.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountNotificationsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_account_notification_preferences_put_request = new \GoAdServerApi\Model\ApiV1AccountNotificationPreferencesPutRequest(); // \GoAdServerApi\Model\ApiV1AccountNotificationPreferencesPutRequest

try {
    $result = $apiInstance->apiV1AccountNotificationPreferencesPut($api_v1_account_notification_preferences_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountNotificationsApi->apiV1AccountNotificationPreferencesPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_account_notification_preferences_put_request** | [**\GoAdServerApi\Model\ApiV1AccountNotificationPreferencesPutRequest**](../Model/ApiV1AccountNotificationPreferencesPutRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response**](../Model/ApiV1WebhooksIdDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountNotificationsGet()`

```php
apiV1AccountNotificationsGet($limit, $unread): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

The in-app notification feed.

Newest first, each item carrying the event key, its rendered title and body, a deep link into the panel, and whether it has been read.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountNotificationsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$limit = 56; // int | Rows to return.
$unread = 'unread_example'; // string | Pass 1 to return only unread items.

try {
    $result = $apiInstance->apiV1AccountNotificationsGet($limit, $unread);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountNotificationsApi->apiV1AccountNotificationsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **limit** | **int**| Rows to return. | [optional] |
| **unread** | **string**| Pass 1 to return only unread items. | [optional] |

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

## `apiV1AccountNotificationsReadPost()`

```php
apiV1AccountNotificationsReadPost($api_v1_account_notifications_read_post_request): \GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response
```

Mark notifications read.

Pass `ids` for specific items, or `all` to clear the feed.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountNotificationsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_account_notifications_read_post_request = new \GoAdServerApi\Model\ApiV1AccountNotificationsReadPostRequest(); // \GoAdServerApi\Model\ApiV1AccountNotificationsReadPostRequest

try {
    $result = $apiInstance->apiV1AccountNotificationsReadPost($api_v1_account_notifications_read_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountNotificationsApi->apiV1AccountNotificationsReadPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_account_notifications_read_post_request** | [**\GoAdServerApi\Model\ApiV1AccountNotificationsReadPostRequest**](../Model/ApiV1AccountNotificationsReadPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response**](../Model/ApiV1WebhooksIdDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountNotificationsUnreadCountGet()`

```php
apiV1AccountNotificationsUnreadCountGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Unread notification count.

Just the number — the cheap poll behind the bell badge.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountNotificationsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1AccountNotificationsUnreadCountGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountNotificationsApi->apiV1AccountNotificationsUnreadCountGet: ', $e->getMessage(), PHP_EOL;
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
