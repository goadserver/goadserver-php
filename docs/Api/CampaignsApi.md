# GoAdServerApi\CampaignsApi

Read campaigns + on/off toggle. Full CRUD not yet exposed.

All URIs are relative to https://up.goadserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1CampaignsGet()**](CampaignsApi.md#apiV1CampaignsGet) | **GET** /api/v1/campaigns | List the caller&#39;s campaigns across all types (or one type). |
| [**apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet()**](CampaignsApi.md#apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet) | **GET** /api/v1/campaigns/ron/{campaignId}/ads/{adId}/zone-bids | List per-zone bid overrides for a RON ad. |
| [**apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut()**](CampaignsApi.md#apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut) | **PUT** /api/v1/campaigns/ron/{campaignId}/ads/{adId}/zone-bids | Replace the per-zone bid overrides on a RON ad. |
| [**apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete()**](CampaignsApi.md#apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete) | **DELETE** /api/v1/campaigns/ron/{campaignId}/ads/{adId}/zone-bids/{zoneId} | Clear a single per-zone bid override. |
| [**apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch()**](CampaignsApi.md#apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch) | **PATCH** /api/v1/campaigns/{type}/{campaignId}/ads/{adId}/active | Pause or unpause a single ad. |
| [**apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch()**](CampaignsApi.md#apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch) | **PATCH** /api/v1/campaigns/{type}/{campaignId}/ads/{adId}/bid | Set a single ad&#39;s bid. |
| [**apiV1CampaignsTypeCampaignIdAdsAdIdClonePost()**](CampaignsApi.md#apiV1CampaignsTypeCampaignIdAdsAdIdClonePost) | **POST** /api/v1/campaigns/{type}/{campaignId}/ads/{adId}/clone | Clone a single ad within a campaign. |
| [**apiV1CampaignsTypeCampaignIdAdsAdIdDelete()**](CampaignsApi.md#apiV1CampaignsTypeCampaignIdAdsAdIdDelete) | **DELETE** /api/v1/campaigns/{type}/{campaignId}/ads/{adId} | Soft-delete an ad. |
| [**apiV1CampaignsTypeCampaignIdAdsAdIdGet()**](CampaignsApi.md#apiV1CampaignsTypeCampaignIdAdsAdIdGet) | **GET** /api/v1/campaigns/{type}/{campaignId}/ads/{adId} | Show one ad. |
| [**apiV1CampaignsTypeCampaignIdAdsAdIdPut()**](CampaignsApi.md#apiV1CampaignsTypeCampaignIdAdsAdIdPut) | **PUT** /api/v1/campaigns/{type}/{campaignId}/ads/{adId} | Update an ad. |
| [**apiV1CampaignsTypeCampaignIdAdsGet()**](CampaignsApi.md#apiV1CampaignsTypeCampaignIdAdsGet) | **GET** /api/v1/campaigns/{type}/{campaignId}/ads | List ads on a campaign. |
| [**apiV1CampaignsTypeCampaignIdAdsPost()**](CampaignsApi.md#apiV1CampaignsTypeCampaignIdAdsPost) | **POST** /api/v1/campaigns/{type}/{campaignId}/ads | Create an ad on a campaign (smart-default, JSON-only). |
| [**apiV1CampaignsTypeCampaignIdSchemaAdsGet()**](CampaignsApi.md#apiV1CampaignsTypeCampaignIdSchemaAdsGet) | **GET** /api/v1/campaigns/{type}/{campaignId}/_schema/ads | Field metadata for ad creation, scoped to a campaign. |
| [**apiV1CampaignsTypeIdActivePatch()**](CampaignsApi.md#apiV1CampaignsTypeIdActivePatch) | **PATCH** /api/v1/campaigns/{type}/{id}/active | Pause or unpause a campaign. |
| [**apiV1CampaignsTypeIdBidsPatch()**](CampaignsApi.md#apiV1CampaignsTypeIdBidsPatch) | **PATCH** /api/v1/campaigns/{type}/{id}/bids | Mass update bids on every ad in a campaign. |
| [**apiV1CampaignsTypeIdClonePost()**](CampaignsApi.md#apiV1CampaignsTypeIdClonePost) | **POST** /api/v1/campaigns/{type}/{id}/clone | Clone a campaign and all its ads. |
| [**apiV1CampaignsTypeIdDelete()**](CampaignsApi.md#apiV1CampaignsTypeIdDelete) | **DELETE** /api/v1/campaigns/{type}/{id} | Soft-delete a campaign. |
| [**apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete()**](CampaignsApi.md#apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete) | **DELETE** /api/v1/campaigns/{type}/{id}/filters/{filter_type}/{filter_id} | Detach a filter from this campaign. |
| [**apiV1CampaignsTypeIdFiltersGet()**](CampaignsApi.md#apiV1CampaignsTypeIdFiltersGet) | **GET** /api/v1/campaigns/{type}/{id}/filters | List filters attached to this campaign. |
| [**apiV1CampaignsTypeIdFiltersPost()**](CampaignsApi.md#apiV1CampaignsTypeIdFiltersPost) | **POST** /api/v1/campaigns/{type}/{id}/filters | Attach a filter to this campaign. |
| [**apiV1CampaignsTypeIdGet()**](CampaignsApi.md#apiV1CampaignsTypeIdGet) | **GET** /api/v1/campaigns/{type}/{id} | Get a single campaign. |
| [**apiV1CampaignsTypeIdMinBidGet()**](CampaignsApi.md#apiV1CampaignsTypeIdMinBidGet) | **GET** /api/v1/campaigns/{type}/{id}/min-bid | Get the campaign&#39;s minimum acceptable CPC bid. |
| [**apiV1CampaignsTypePost()**](CampaignsApi.md#apiV1CampaignsTypePost) | **POST** /api/v1/campaigns/{type} | Create a new campaign (smart-default). |
| [**apiV1CampaignsTypeSchemaAdsGet()**](CampaignsApi.md#apiV1CampaignsTypeSchemaAdsGet) | **GET** /api/v1/campaigns/{type}/_schema/ads | Field metadata for ad creation. |
| [**apiV1CampaignsTypeSchemaGet()**](CampaignsApi.md#apiV1CampaignsTypeSchemaGet) | **GET** /api/v1/campaigns/{type}/_schema | Field metadata for create/update. |


## `apiV1CampaignsGet()`

```php
apiV1CampaignsGet($type, $page, $per_page, $fields): \GoAdServerApi\Model\ApiV1CampaignsGet200Response
```

List the caller's campaigns across all types (or one type).

Returns the caller's campaigns. By default each row carries a rich-but-bounded projection: identity (`id`, `name`, `campaign_type`, `ad_type` + `ad_type_label`, `selling_type`), lifecycle (`is_paused`, `is_active`, `is_deleted`, `is_complete`, `pending_state`), budget (`daily_budget`, `daily_spent`, `total_budget`, `total_spent`, `use_campaign_budget`), bidding (`bid_default`, `min_bid`), targeting summary (`countries`, `languages`, `categories`, `device_type_ids`, …), capping, schedule, linked filter/pool ids, and timestamps.  Use `?fields=` to opt in or out of fields:    - `?fields=*` — return **every** column on the underlying     campaign row (70+ fields, type-dependent). Convenient for     AI agents that want the full picture in one call.   - `?fields=name,daily_budget,countries` — narrow to just     those fields. `id` is always included.   - (omitted) — default rich projection above.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'all'; // string
$page = 1; // int
$per_page = 50; // int
$fields = 'fields_example'; // string | CSV of fields to return; `*` returns everything available.

try {
    $result = $apiInstance->apiV1CampaignsGet($type, $page, $per_page, $fields);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | [optional] [default to &#39;all&#39;] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 50] |
| **fields** | **string**| CSV of fields to return; &#x60;*&#x60; returns everything available. | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsGet200Response**](../Model/ApiV1CampaignsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet()`

```php
apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet($campaign_id, $ad_id): \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet200Response
```

List per-zone bid overrides for a RON ad.

RON ads carry a per-adzone bid map (`bids_per_zone` JSON), letting an advertiser bid different amounts on different publisher zones. This endpoint returns each override enriched with the zone + site names, so an auto-optimiser can correlate ids ↔ human labels in one call.  Empty list if the ad has no overrides — the global ad bid applies to every zone.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$campaign_id = 56; // int
$ad_id = 56; // int

try {
    $result = $apiInstance->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet($campaign_id, $ad_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **campaign_id** | **int**|  | |
| **ad_id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet200Response**](../Model/ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut()`

```php
apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request): \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut200Response
```

Replace the per-zone bid overrides on a RON ad.

Full-replace semantics — any zone NOT in the request body has its override cleared. If you only want to update a few zones, pass the complete current set merged with your changes. Auto-optimisers usually recompute the full bid sheet per cycle so this fits the common loop.  Negative or zero `zone_id` and negative `bid` entries are silently dropped.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$campaign_id = 56; // int
$ad_id = 56; // int
$api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request = {"zone_bids":[{"zone_id":12,"bid":0.002},{"zone_id":19,"bid":8.0E-4}]}; // \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutRequest

try {
    $result = $apiInstance->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **campaign_id** | **int**|  | |
| **ad_id** | **int**|  | |
| **api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request** | [**\GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutRequest**](../Model/ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut200Response**](../Model/ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete()`

```php
apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete($campaign_id, $ad_id, $zone_id): \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete200Response
```

Clear a single per-zone bid override.

Removes the override for one zone. The global ad bid takes over again for that zone. Returns 404 if no override exists for the given zone.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$campaign_id = 56; // int
$ad_id = 56; // int
$zone_id = 56; // int

try {
    $result = $apiInstance->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete($campaign_id, $ad_id, $zone_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **campaign_id** | **int**|  | |
| **ad_id** | **int**|  | |
| **zone_id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete200Response**](../Model/ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch()`

```php
apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch($type, $campaign_id, $ad_id, $api_v1_campaigns_type_id_active_patch_request): \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatch200Response
```

Pause or unpause a single ad.

Idempotent set. `active=true` activates the ad, `active=false` pauses it. Useful for granular pause/resume from an optimiser without touching the campaign-level pause state.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$campaign_id = 56; // int
$ad_id = 56; // int
$api_v1_campaigns_type_id_active_patch_request = new \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest(); // \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest

try {
    $result = $apiInstance->apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch($type, $campaign_id, $ad_id, $api_v1_campaigns_type_id_active_patch_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **campaign_id** | **int**|  | |
| **ad_id** | **int**|  | |
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

## `apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch()`

```php
apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request): \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatch200Response
```

Set a single ad's bid.

Updates one ad's bid. Value is in main-currency units (the same scale `bid` is stored in). For the legal floor on the parent campaign, query `GET /campaigns/{type}/{id}/min-bid` first.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$campaign_id = 56; // int
$ad_id = 56; // int
$api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request = new \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatchRequest(); // \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatchRequest

try {
    $result = $apiInstance->apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **campaign_id** | **int**|  | |
| **ad_id** | **int**|  | |
| **api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request** | [**\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatchRequest**](../Model/ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatchRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatch200Response**](../Model/ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatch200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CampaignsTypeCampaignIdAdsAdIdClonePost()`

```php
apiV1CampaignsTypeCampaignIdAdsAdIdClonePost($type, $campaign_id, $ad_id): \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response
```

Clone a single ad within a campaign.

Duplicates one ad. The clone is attached to the same campaign as the source. Useful for A/B-testing creative variants without rebuilding the whole record from scratch.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$campaign_id = 56; // int
$ad_id = 56; // int

try {
    $result = $apiInstance->apiV1CampaignsTypeCampaignIdAdsAdIdClonePost($type, $campaign_id, $ad_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeCampaignIdAdsAdIdClonePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **campaign_id** | **int**|  | |
| **ad_id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response**](../Model/ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CampaignsTypeCampaignIdAdsAdIdDelete()`

```php
apiV1CampaignsTypeCampaignIdAdsAdIdDelete($type, $campaign_id, $ad_id)
```

Soft-delete an ad.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$campaign_id = 56; // int
$ad_id = 56; // int

try {
    $apiInstance->apiV1CampaignsTypeCampaignIdAdsAdIdDelete($type, $campaign_id, $ad_id);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeCampaignIdAdsAdIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **campaign_id** | **int**|  | |
| **ad_id** | **int**|  | |

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

## `apiV1CampaignsTypeCampaignIdAdsAdIdGet()`

```php
apiV1CampaignsTypeCampaignIdAdsAdIdGet($type, $campaign_id, $ad_id)
```

Show one ad.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$campaign_id = 56; // int
$ad_id = 56; // int

try {
    $apiInstance->apiV1CampaignsTypeCampaignIdAdsAdIdGet($type, $campaign_id, $ad_id);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeCampaignIdAdsAdIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **campaign_id** | **int**|  | |
| **ad_id** | **int**|  | |

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

## `apiV1CampaignsTypeCampaignIdAdsAdIdPut()`

```php
apiV1CampaignsTypeCampaignIdAdsAdIdPut($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_put_request)
```

Update an ad.

Editable fields depend on ad type. Common fields: `bid`, `isactive`, `creatives` (banner/video/native blob).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$campaign_id = 56; // int
$ad_id = 56; // int
$api_v1_campaigns_type_campaign_id_ads_ad_id_put_request = new \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdPutRequest(); // \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdPutRequest

try {
    $apiInstance->apiV1CampaignsTypeCampaignIdAdsAdIdPut($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_put_request);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeCampaignIdAdsAdIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **campaign_id** | **int**|  | |
| **ad_id** | **int**|  | |
| **api_v1_campaigns_type_campaign_id_ads_ad_id_put_request** | [**\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdPutRequest**](../Model/ApiV1CampaignsTypeCampaignIdAdsAdIdPutRequest.md)|  | |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CampaignsTypeCampaignIdAdsGet()`

```php
apiV1CampaignsTypeCampaignIdAdsGet($type, $campaign_id, $page, $per_page): \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsGet200Response
```

List ads on a campaign.

Returns every ad attached to the campaign (paginated). Each row carries `id`, `bid`, `isactive`, `bansizeid`, the underlying `creatives` JSON (banner/video/native, with file paths and creative_id refs), and lifetime stats from the campaign-side rollup.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$campaign_id = 56; // int
$page = 1; // int
$per_page = 50; // int

try {
    $result = $apiInstance->apiV1CampaignsTypeCampaignIdAdsGet($type, $campaign_id, $page, $per_page);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeCampaignIdAdsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **campaign_id** | **int**|  | |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 50] |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsGet200Response**](../Model/ApiV1CampaignsTypeCampaignIdAdsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CampaignsTypeCampaignIdAdsPost()`

```php
apiV1CampaignsTypeCampaignIdAdsPost($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_post_request, $dry_run, $strict)
```

Create an ad on a campaign (smart-default, JSON-only).

Creates a new ad attached to the campaign. JSON-only — agents reference an existing creative (banner pool, single creative, URL or HTML) instead of uploading files. The panel handler at the same path still accepts `multipart/form-data` for file uploads — this docs entry covers the JSON path only.  Required: `title` + `bid` + exactly **one** creative reference (`banner_pool_id` / `creative_id` / `banner_url` / `banner_html` / `video_pool_id`). `destination_url` is required unless the creative carries its own URL (popunder/JS) or you set `url_pool_id`.  **Modes:**   - `?dry_run=1` — return the resolved field map + computed     `creatives` JSON without inserting.   - `?strict=1` — reject unknown field names with a 422.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$campaign_id = 56; // int
$api_v1_campaigns_type_campaign_id_ads_post_request = {"title":"My banner ad","bid":0.0042,"banner_pool_id":129481,"destination_url":"https://example.com/lander"}; // \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsPostRequest
$dry_run = 'dry_run_example'; // string
$strict = 'strict_example'; // string

try {
    $apiInstance->apiV1CampaignsTypeCampaignIdAdsPost($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_post_request, $dry_run, $strict);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeCampaignIdAdsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **campaign_id** | **int**|  | |
| **api_v1_campaigns_type_campaign_id_ads_post_request** | [**\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsPostRequest**](../Model/ApiV1CampaignsTypeCampaignIdAdsPostRequest.md)|  | |
| **dry_run** | **string**|  | [optional] |
| **strict** | **string**|  | [optional] |

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

## `apiV1CampaignsTypeCampaignIdSchemaAdsGet()`

```php
apiV1CampaignsTypeCampaignIdSchemaAdsGet($type, $campaign_id)
```

Field metadata for ad creation, scoped to a campaign.

Same as `/_schema/ads` above, but uses the campaign's ad type to return only the fields that apply (e.g. video pools omitted for a banner-only campaign). Sets `campaign_ad_type` in the response.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$campaign_id = 56; // int

try {
    $apiInstance->apiV1CampaignsTypeCampaignIdSchemaAdsGet($type, $campaign_id);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeCampaignIdSchemaAdsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **campaign_id** | **int**|  | |

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

## `apiV1CampaignsTypeIdActivePatch()`

```php
apiV1CampaignsTypeIdActivePatch($type, $id, $api_v1_campaigns_type_id_active_patch_request): \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatch200Response
```

Pause or unpause a campaign.

Idempotent. `active=true` unpauses, `active=false` pauses. Reuses the same panel-side guards: flat-rate campaigns with running deals cannot be paused; offer campaigns require >= 5 ads to be unpaused.  This is the endpoint behind the **\"On / off only\"** key preset.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$id = 56; // int
$api_v1_campaigns_type_id_active_patch_request = new \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest(); // \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest

try {
    $result = $apiInstance->apiV1CampaignsTypeIdActivePatch($type, $id, $api_v1_campaigns_type_id_active_patch_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeIdActivePatch: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
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

## `apiV1CampaignsTypeIdBidsPatch()`

```php
apiV1CampaignsTypeIdBidsPatch($type, $id, $api_v1_campaigns_type_id_bids_patch_request): \GoAdServerApi\Model\ApiV1CampaignsTypeIdBidsPatch200Response
```

Mass update bids on every ad in a campaign.

Update every (or a subset of) ad's bid in one call. Three modes:    - `set`     — overwrite each target ad's bid with `value`.   - `pct_inc` — raise each target ad's bid by `pct` percent.   - `pct_dec` — lower each target ad's bid by `pct` percent.  Optional `ad_ids` narrows the update to a subset (omit or empty list = every non-deleted ad on the campaign). Bids are clamped at 0 — anything that would resolve below 0 is set to 0.  Bid values are in main-currency units (the same scale `bid` is stored in). For minimum-acceptable-bid floors call `GET /api/v1/campaigns/{type}/{id}/min-bid` first.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$id = 56; // int
$api_v1_campaigns_type_id_bids_patch_request = {"mode":"set","value":0.0042}; // \GoAdServerApi\Model\ApiV1CampaignsTypeIdBidsPatchRequest

try {
    $result = $apiInstance->apiV1CampaignsTypeIdBidsPatch($type, $id, $api_v1_campaigns_type_id_bids_patch_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeIdBidsPatch: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **id** | **int**|  | |
| **api_v1_campaigns_type_id_bids_patch_request** | [**\GoAdServerApi\Model\ApiV1CampaignsTypeIdBidsPatchRequest**](../Model/ApiV1CampaignsTypeIdBidsPatchRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsTypeIdBidsPatch200Response**](../Model/ApiV1CampaignsTypeIdBidsPatch200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CampaignsTypeIdClonePost()`

```php
apiV1CampaignsTypeIdClonePost($type, $id, $api_v1_campaigns_type_id_clone_post_request): \GoAdServerApi\Model\ApiV1CampaignsTypeIdClonePost201Response
```

Clone a campaign and all its ads.

Duplicates the campaign row and every ad attached to it. The clone lands paused (`is_active=false`) so you can review and tweak it before activating. Targeting filters, bids, daily caps and budget settings are all carried over verbatim — only `name` (suffix \" Copy\" by default, overridable via the body) and reset bookkeeping fields (`num_ads`, `pending_state`, `dateadded`, `wl_zoneids`) differ from the original.  For `ron` campaigns the clone also strips any flat-rate deals (the new campaign starts with no running placements) and copies offer screenshot files from the original so the cloned offer-mode campaign retains its preview imagery.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$id = 56; // int
$api_v1_campaigns_type_id_clone_post_request = new \GoAdServerApi\Model\ApiV1CampaignsTypeIdClonePostRequest(); // \GoAdServerApi\Model\ApiV1CampaignsTypeIdClonePostRequest

try {
    $result = $apiInstance->apiV1CampaignsTypeIdClonePost($type, $id, $api_v1_campaigns_type_id_clone_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeIdClonePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **id** | **int**|  | |
| **api_v1_campaigns_type_id_clone_post_request** | [**\GoAdServerApi\Model\ApiV1CampaignsTypeIdClonePostRequest**](../Model/ApiV1CampaignsTypeIdClonePostRequest.md)|  | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsTypeIdClonePost201Response**](../Model/ApiV1CampaignsTypeIdClonePost201Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CampaignsTypeIdDelete()`

```php
apiV1CampaignsTypeIdDelete($type, $id): \GoAdServerApi\Model\ApiV1CampaignsTypeIdDelete200Response
```

Soft-delete a campaign.

Sets `isdeleted=1`, `ispaused=1`, `dayactive=0`. The campaign and its history remain in the database for billing/stats reconciliation but stop serving immediately. Idempotent — deleting an already- deleted campaign returns 404.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$id = 56; // int

try {
    $result = $apiInstance->apiV1CampaignsTypeIdDelete($type, $id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsTypeIdDelete200Response**](../Model/ApiV1CampaignsTypeIdDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete()`

```php
apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete($type, $id, $filter_type, $filter_id)
```

Detach a filter from this campaign.

Exclusion filter: removes the campaign id from the filter's `campaignids` CSV (if the CSV becomes empty, falls back to \"0\" meaning \"applies to all campaigns\" — same legacy sentinel). Inclusion filter: clears the campaign's FK column only if it currently points to this filter; otherwise returns 404.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$id = 56; // int
$filter_type = 'filter_type_example'; // string
$filter_id = 56; // int

try {
    $apiInstance->apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete($type, $id, $filter_type, $filter_id);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **id** | **int**|  | |
| **filter_type** | **string**|  | |
| **filter_id** | **int**|  | |

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

## `apiV1CampaignsTypeIdFiltersGet()`

```php
apiV1CampaignsTypeIdFiltersGet($type, $id): \GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersGet200Response
```

List filters attached to this campaign.

Returns every filter (exclusion via `campaignids` membership, inclusion via FK columns) currently linked to this campaign. Only `ron` and `rtb` campaign types support filter linkage.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$id = 56; // int

try {
    $result = $apiInstance->apiV1CampaignsTypeIdFiltersGet($type, $id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeIdFiltersGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersGet200Response**](../Model/ApiV1CampaignsTypeIdFiltersGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CampaignsTypeIdFiltersPost()`

```php
apiV1CampaignsTypeIdFiltersPost($type, $id, $api_v1_campaigns_type_id_filters_post_request): \GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersPost200Response
```

Attach a filter to this campaign.

For exclusion filters, appends the campaign id to the filter's `campaignids` CSV. For inclusion filters, sets the campaign's FK column to this filter (replacing any previous inclusion filter of the same kind).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$id = 56; // int
$api_v1_campaigns_type_id_filters_post_request = new \GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersPostRequest(); // \GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersPostRequest

try {
    $result = $apiInstance->apiV1CampaignsTypeIdFiltersPost($type, $id, $api_v1_campaigns_type_id_filters_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeIdFiltersPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **id** | **int**|  | |
| **api_v1_campaigns_type_id_filters_post_request** | [**\GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersPostRequest**](../Model/ApiV1CampaignsTypeIdFiltersPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersPost200Response**](../Model/ApiV1CampaignsTypeIdFiltersPost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CampaignsTypeIdGet()`

```php
apiV1CampaignsTypeIdGet($type, $id): object
```

Get a single campaign.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$id = 56; // int

try {
    $result = $apiInstance->apiV1CampaignsTypeIdGet($type, $id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **id** | **int**|  | |

### Return type

**object**

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CampaignsTypeIdMinBidGet()`

```php
apiV1CampaignsTypeIdMinBidGet($type, $id): \GoAdServerApi\Model\ApiV1CampaignsTypeIdMinBidGet200Response
```

Get the campaign's minimum acceptable CPC bid.

Returns the floor the platform will accept for this campaign, derived from the same logic the panel uses (admanager → effectively zero, custom `fx_minprice`, or the `min_prices_ron` table matched against the campaign's targeting). Use this before bulk-updating bids so the optimiser doesn't push values below the floor and have them clamped or rejected.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$id = 56; // int

try {
    $result = $apiInstance->apiV1CampaignsTypeIdMinBidGet($type, $id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeIdMinBidGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsTypeIdMinBidGet200Response**](../Model/ApiV1CampaignsTypeIdMinBidGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CampaignsTypePost()`

```php
apiV1CampaignsTypePost($type, $api_v1_campaigns_type_post_request, $dry_run, $strict)
```

Create a new campaign (smart-default).

Creates a new campaign with sensible defaults applied for any field you don't send. Required: `name` + `ad_type`. Everything else (targeting, capping, schedule, …) defaults to \"all/none\" unless explicitly supplied — see `GET /api/v1/campaigns/{type}/_schema` for the full field list.  The campaign always starts paused (`is_paused=true`) so you can attach ads via `POST /api/v1/campaigns/{type}/{id}/ads` and verify before activating. Flip the active flag when ready via `PATCH /api/v1/campaigns/{type}/{id}/active`.  **Modes:**   - `?dry_run=1` — validate and return the resolved field map     without inserting. Useful for previewing what an LLM would     create before charging it.   - `?strict=1` — reject unknown field names with a 422 listing     them. Without this, unknown keys are silently ignored.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$api_v1_campaigns_type_post_request = {"name":"My first API campaign","ad_type":2}; // \GoAdServerApi\Model\ApiV1CampaignsTypePostRequest
$dry_run = 'dry_run_example'; // string
$strict = 'strict_example'; // string

try {
    $apiInstance->apiV1CampaignsTypePost($type, $api_v1_campaigns_type_post_request, $dry_run, $strict);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **api_v1_campaigns_type_post_request** | [**\GoAdServerApi\Model\ApiV1CampaignsTypePostRequest**](../Model/ApiV1CampaignsTypePostRequest.md)|  | |
| **dry_run** | **string**|  | [optional] |
| **strict** | **string**|  | [optional] |

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

## `apiV1CampaignsTypeSchemaAdsGet()`

```php
apiV1CampaignsTypeSchemaAdsGet($type): \GoAdServerApi\Model\ApiV1CampaignsTypeSchemaAdsGet200Response
```

Field metadata for ad creation.

Discovery endpoint mirroring `/_schema` for campaigns, scoped to ads. Lists every public field a `POST /campaigns/{type}/{campaignId}/ads` accepts, with type, required-flag, default, example, and description. Also returns a `creative_choice` block reminding the caller that exactly one of `banner_pool_id` / `creative_id` / `banner_url` / `banner_html` / `video_pool_id` must be set.  Use the campaign-scoped variant `/api/v1/campaigns/{type}/{campaignId}/_schema/ads` to get the same payload narrowed to fields that apply to that specific campaign's ad type (returns `campaign_ad_type` so AI agents know which creative shape is appropriate).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string

try {
    $result = $apiInstance->apiV1CampaignsTypeSchemaAdsGet($type);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeSchemaAdsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsTypeSchemaAdsGet200Response**](../Model/ApiV1CampaignsTypeSchemaAdsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CampaignsTypeSchemaGet()`

```php
apiV1CampaignsTypeSchemaGet($type): \GoAdServerApi\Model\ApiV1CampaignsTypeSchemaGet200Response
```

Field metadata for create/update.

Discovery endpoint for AI agents and integrators. Returns the canonical list of public field names a `POST /api/v1/campaigns/{type}` accepts, with type, required-flag, default value, an example, and a one-line description. `ad_type` enumerates the active ad-type ids on the caller's tenant.  Use this to drive form UIs, validate agent-generated payloads client-side, or feed into a function-calling LLM.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string

try {
    $result = $apiInstance->apiV1CampaignsTypeSchemaGet($type);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CampaignsApi->apiV1CampaignsTypeSchemaGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsTypeSchemaGet200Response**](../Model/ApiV1CampaignsTypeSchemaGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
