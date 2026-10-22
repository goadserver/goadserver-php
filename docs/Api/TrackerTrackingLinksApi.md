# GoAdServerApi\TrackerTrackingLinksApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1TrackerTrackingLinksBulkPost()**](TrackerTrackingLinksApi.md#apiV1TrackerTrackingLinksBulkPost) | **POST** /api/v1/tracker/tracking-links/bulk | Delete many tracking links. |
| [**apiV1TrackerTrackingLinksGet()**](TrackerTrackingLinksApi.md#apiV1TrackerTrackingLinksGet) | **GET** /api/v1/tracker/tracking-links | List tracking links. |
| [**apiV1TrackerTrackingLinksIdBlocklistGet()**](TrackerTrackingLinksApi.md#apiV1TrackerTrackingLinksIdBlocklistGet) | **GET** /api/v1/tracker/tracking-links/{id}/blocklist | List zones blocked on one link. |
| [**apiV1TrackerTrackingLinksIdBlocklistPost()**](TrackerTrackingLinksApi.md#apiV1TrackerTrackingLinksIdBlocklistPost) | **POST** /api/v1/tracker/tracking-links/{id}/blocklist | Block a zone on one link. |
| [**apiV1TrackerTrackingLinksIdBlocklistZoneIdDelete()**](TrackerTrackingLinksApi.md#apiV1TrackerTrackingLinksIdBlocklistZoneIdDelete) | **DELETE** /api/v1/tracker/tracking-links/{id}/blocklist/{zoneId} | Unblock a zone on one link. |
| [**apiV1TrackerTrackingLinksIdDelete()**](TrackerTrackingLinksApi.md#apiV1TrackerTrackingLinksIdDelete) | **DELETE** /api/v1/tracker/tracking-links/{id} | Delete a tracking link. |
| [**apiV1TrackerTrackingLinksIdNetworkBindingDelete()**](TrackerTrackingLinksApi.md#apiV1TrackerTrackingLinksIdNetworkBindingDelete) | **DELETE** /api/v1/tracker/tracking-links/{id}/network-binding | Unbind a tracking link from its ad-network campaign. |
| [**apiV1TrackerTrackingLinksIdNetworkBindingPut()**](TrackerTrackingLinksApi.md#apiV1TrackerTrackingLinksIdNetworkBindingPut) | **PUT** /api/v1/tracker/tracking-links/{id}/network-binding | Bind a tracking link to an ad-network campaign. |
| [**apiV1TrackerTrackingLinksIdPut()**](TrackerTrackingLinksApi.md#apiV1TrackerTrackingLinksIdPut) | **PUT** /api/v1/tracker/tracking-links/{id} | Update a tracking link. |
| [**apiV1TrackerTrackingLinksIdToggleActivePost()**](TrackerTrackingLinksApi.md#apiV1TrackerTrackingLinksIdToggleActivePost) | **POST** /api/v1/tracker/tracking-links/{id}/toggle-active | Pause or resume the bound network campaign. |
| [**apiV1TrackerTrackingLinksIdZoneCostsGet()**](TrackerTrackingLinksApi.md#apiV1TrackerTrackingLinksIdZoneCostsGet) | **GET** /api/v1/tracker/tracking-links/{id}/zone-costs | Per-zone cost and performance for one link. |
| [**apiV1TrackerTrackingLinksPost()**](TrackerTrackingLinksApi.md#apiV1TrackerTrackingLinksPost) | **POST** /api/v1/tracker/tracking-links | Create a tracking link. |


## `apiV1TrackerTrackingLinksBulkPost()`

```php
apiV1TrackerTrackingLinksBulkPost($api_v1_tracker_tracking_links_bulk_post_request): \GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPost200Response
```

Delete many tracking links.

The tracking-link twin of `/offers/bulk`, but delete-only. A link's on/off state is set one at a time through `POST /tracking-links/{id}/toggle-active` — pausing a link a live traffic source is still sending to is a decision worth making per link, not per batch.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerTrackingLinksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_tracker_tracking_links_bulk_post_request = new \GoAdServerApi\Model\ApiV1TrackerTrackingLinksBulkPostRequest(); // \GoAdServerApi\Model\ApiV1TrackerTrackingLinksBulkPostRequest

try {
    $result = $apiInstance->apiV1TrackerTrackingLinksBulkPost($api_v1_tracker_tracking_links_bulk_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerTrackingLinksApi->apiV1TrackerTrackingLinksBulkPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_tracker_tracking_links_bulk_post_request** | [**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksBulkPostRequest**](../Model/ApiV1TrackerTrackingLinksBulkPostRequest.md)|  | |

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

## `apiV1TrackerTrackingLinksGet()`

```php
apiV1TrackerTrackingLinksGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksGet200Response
```

List tracking links.

Each link is a `publisher_adzones` row under a hidden source site. A link bound to one offer redirects straight to it; a smartlink carries several `offer_ids` and lets targeting and eCPM pick the winner per click.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerTrackingLinksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1TrackerTrackingLinksGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerTrackingLinksApi->apiV1TrackerTrackingLinksGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksGet200Response**](../Model/ApiV1TrackerTrackingLinksGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerTrackingLinksIdBlocklistGet()`

```php
apiV1TrackerTrackingLinksIdBlocklistGet($id): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

List zones blocked on one link.

The placements this link refuses to buy. 404 while the link is not bound to an ad network — there are no placements to block until it is.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerTrackingLinksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Tracking link ID.

try {
    $result = $apiInstance->apiV1TrackerTrackingLinksIdBlocklistGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerTrackingLinksApi->apiV1TrackerTrackingLinksIdBlocklistGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Tracking link ID. | |

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

## `apiV1TrackerTrackingLinksIdBlocklistPost()`

```php
apiV1TrackerTrackingLinksIdBlocklistPost($id, $api_v1_tracker_tracking_links_id_blocklist_post_request): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Block a zone on one link.

Adds a placement to the link's block list; the traffic source stops being bought on that zone. 404 while the link is not bound to an ad network.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerTrackingLinksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Tracking link ID.
$api_v1_tracker_tracking_links_id_blocklist_post_request = new \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdBlocklistPostRequest(); // \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdBlocklistPostRequest

try {
    $result = $apiInstance->apiV1TrackerTrackingLinksIdBlocklistPost($id, $api_v1_tracker_tracking_links_id_blocklist_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerTrackingLinksApi->apiV1TrackerTrackingLinksIdBlocklistPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Tracking link ID. | |
| **api_v1_tracker_tracking_links_id_blocklist_post_request** | [**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdBlocklistPostRequest**](../Model/ApiV1TrackerTrackingLinksIdBlocklistPostRequest.md)|  | |

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

## `apiV1TrackerTrackingLinksIdBlocklistZoneIdDelete()`

```php
apiV1TrackerTrackingLinksIdBlocklistZoneIdDelete($id, $zone_id): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
```

Unblock a zone on one link.

Removes the placement from the block list. 404 while the link is not bound to an ad network.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerTrackingLinksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Tracking link ID.
$zone_id = 'zone_id_example'; // string | Zone / placement id.

try {
    $result = $apiInstance->apiV1TrackerTrackingLinksIdBlocklistZoneIdDelete($id, $zone_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerTrackingLinksApi->apiV1TrackerTrackingLinksIdBlocklistZoneIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Tracking link ID. | |
| **zone_id** | **string**| Zone / placement id. | |

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

## `apiV1TrackerTrackingLinksIdDelete()`

```php
apiV1TrackerTrackingLinksIdDelete($id): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
```

Delete a tracking link.

Soft delete — the link stops serving, its click history stays.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerTrackingLinksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Tracking link ID.

try {
    $result = $apiInstance->apiV1TrackerTrackingLinksIdDelete($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerTrackingLinksApi->apiV1TrackerTrackingLinksIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Tracking link ID. | |

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

## `apiV1TrackerTrackingLinksIdNetworkBindingDelete()`

```php
apiV1TrackerTrackingLinksIdNetworkBindingDelete($id): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
```

Unbind a tracking link from its ad-network campaign.

Cost stops being attributed to the link.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerTrackingLinksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Tracking link ID.

try {
    $result = $apiInstance->apiV1TrackerTrackingLinksIdNetworkBindingDelete($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerTrackingLinksApi->apiV1TrackerTrackingLinksIdNetworkBindingDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Tracking link ID. | |

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

## `apiV1TrackerTrackingLinksIdNetworkBindingPut()`

```php
apiV1TrackerTrackingLinksIdNetworkBindingPut($id, $api_v1_tracker_tracking_links_id_network_binding_put_request): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Bind a tracking link to an ad-network campaign.

Ties the link to a campaign on the buying side, so spend pulled from that network lands against this link's revenue. `auto_api` lets rules push pause / bid changes back to the network.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerTrackingLinksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Tracking link ID.
$api_v1_tracker_tracking_links_id_network_binding_put_request = new \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdNetworkBindingPutRequest(); // \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdNetworkBindingPutRequest

try {
    $result = $apiInstance->apiV1TrackerTrackingLinksIdNetworkBindingPut($id, $api_v1_tracker_tracking_links_id_network_binding_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerTrackingLinksApi->apiV1TrackerTrackingLinksIdNetworkBindingPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Tracking link ID. | |
| **api_v1_tracker_tracking_links_id_network_binding_put_request** | [**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdNetworkBindingPutRequest**](../Model/ApiV1TrackerTrackingLinksIdNetworkBindingPutRequest.md)|  | |

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

## `apiV1TrackerTrackingLinksIdPut()`

```php
apiV1TrackerTrackingLinksIdPut($id, $api_v1_tracker_tracking_links_id_put_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Update a tracking link.

Partial update — omitted fields keep their current value.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerTrackingLinksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Tracking link (adzone) ID.
$api_v1_tracker_tracking_links_id_put_request = new \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdPutRequest(); // \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdPutRequest

try {
    $result = $apiInstance->apiV1TrackerTrackingLinksIdPut($id, $api_v1_tracker_tracking_links_id_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerTrackingLinksApi->apiV1TrackerTrackingLinksIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Tracking link (adzone) ID. | |
| **api_v1_tracker_tracking_links_id_put_request** | [**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdPutRequest**](../Model/ApiV1TrackerTrackingLinksIdPutRequest.md)|  | |

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

## `apiV1TrackerTrackingLinksIdToggleActivePost()`

```php
apiV1TrackerTrackingLinksIdToggleActivePost($id, $api_v1_campaigns_type_id_active_patch_request): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdToggleActivePost200Response
```

Pause or resume the bound network campaign.

**This reaches out to the traffic source.** It does not flip a local flag: it calls the bound ad network's API to pause or resume the campaign this link is bound to, and writes the outcome back to `adnetwork_state`.  The binding is required — an unbound link answers 400 `no_binding`.  Day to day you do not need this: goadserver's `UpdateFromAdzones` cron already mirrors local offer state to the remote campaign every tick. It exists for \"pause the source right now\" without waiting for the next tick, and for reporting-only bindings (`auto_api` false) where the cron never acts.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerTrackingLinksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Tracking link ID.
$api_v1_campaigns_type_id_active_patch_request = new \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest(); // \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest

try {
    $result = $apiInstance->apiV1TrackerTrackingLinksIdToggleActivePost($id, $api_v1_campaigns_type_id_active_patch_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerTrackingLinksApi->apiV1TrackerTrackingLinksIdToggleActivePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Tracking link ID. | |
| **api_v1_campaigns_type_id_active_patch_request** | [**\GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest**](../Model/ApiV1CampaignsTypeIdActivePatchRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdToggleActivePost200Response**](../Model/ApiV1TrackerTrackingLinksIdToggleActivePost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerTrackingLinksIdZoneCostsGet()`

```php
apiV1TrackerTrackingLinksIdZoneCostsGet($id, $days): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Per-zone cost and performance for one link.

Placement-level spend and result for the link's bound traffic source, the input a placement rule blocks on. See docs/TRACKER_ZONE_BLOCKING.md.  Placement data comes from the bound ad network, so a link with no binding answers 404 — bind it with `PUT /tracking-links/{id}/network-binding` first.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerTrackingLinksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Tracking link ID.
$days = 7; // int | Trailing window in days.

try {
    $result = $apiInstance->apiV1TrackerTrackingLinksIdZoneCostsGet($id, $days);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerTrackingLinksApi->apiV1TrackerTrackingLinksIdZoneCostsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Tracking link ID. | |
| **days** | **int**| Trailing window in days. | [optional] [default to 7] |

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

## `apiV1TrackerTrackingLinksPost()`

```php
apiV1TrackerTrackingLinksPost($api_v1_tracker_tracking_links_post_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Create a tracking link.

Pass `offer_id` for a single-offer link, or `offer_ids` (optionally with matching `weights`) for a smartlink. `tokens` names the macro slots the click URL should carry.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerTrackingLinksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_tracker_tracking_links_post_request = new \GoAdServerApi\Model\ApiV1TrackerTrackingLinksPostRequest(); // \GoAdServerApi\Model\ApiV1TrackerTrackingLinksPostRequest

try {
    $result = $apiInstance->apiV1TrackerTrackingLinksPost($api_v1_tracker_tracking_links_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerTrackingLinksApi->apiV1TrackerTrackingLinksPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_tracker_tracking_links_post_request** | [**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksPostRequest**](../Model/ApiV1TrackerTrackingLinksPostRequest.md)|  | |

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
