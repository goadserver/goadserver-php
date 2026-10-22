# GoAdServerApi\AdspacesApi

Ad zones and every tab of the panel&#39;s ad zone editor: whitelisted campaigns, floor prices, own (house) ads, the external-network binding, clone, bulk create, mass delete/recover, templates and export codes.

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1AdspacesAddMultiplePost()**](AdspacesApi.md#apiV1AdspacesAddMultiplePost) | **POST** /api/v1/adspaces/add-multiple | Create several ad zones of one type on one site. |
| [**apiV1AdspacesExportCodesGet()**](AdspacesApi.md#apiV1AdspacesExportCodesGet) | **GET** /api/v1/adspaces/export-codes | Embed codes for many ad zones at once, grouped by site. |
| [**apiV1AdspacesGet()**](AdspacesApi.md#apiV1AdspacesGet) | **GET** /api/v1/adspaces | List ad zones owned by the caller. |
| [**apiV1AdspacesIdBlockedAdvertisersGet()**](AdspacesApi.md#apiV1AdspacesIdBlockedAdvertisersGet) | **GET** /api/v1/adspaces/{id}/blocked-advertisers | Advertiser block filters that exclude this ad zone. |
| [**apiV1AdspacesIdChildrenGet()**](AdspacesApi.md#apiV1AdspacesIdChildrenGet) | **GET** /api/v1/adspaces/{id}/children | List an ad zone&#39;s child zones. |
| [**apiV1AdspacesIdClonePost()**](AdspacesApi.md#apiV1AdspacesIdClonePost) | **POST** /api/v1/adspaces/{id}/clone | Duplicate an ad zone. |
| [**apiV1AdspacesIdCodeGet()**](AdspacesApi.md#apiV1AdspacesIdCodeGet) | **GET** /api/v1/adspaces/{id}/code | Embed-code snippets for an adspace. |
| [**apiV1AdspacesIdDelete()**](AdspacesApi.md#apiV1AdspacesIdDelete) | **DELETE** /api/v1/adspaces/{id} | Soft-delete an adspace. |
| [**apiV1AdspacesIdGet()**](AdspacesApi.md#apiV1AdspacesIdGet) | **GET** /api/v1/adspaces/{id} | Get a single adspace. |
| [**apiV1AdspacesIdNetworkBindingDelete()**](AdspacesApi.md#apiV1AdspacesIdNetworkBindingDelete) | **DELETE** /api/v1/adspaces/{id}/network-binding | Remove the zone&#39;s network binding. |
| [**apiV1AdspacesIdNetworkBindingPut()**](AdspacesApi.md#apiV1AdspacesIdNetworkBindingPut) | **PUT** /api/v1/adspaces/{id}/network-binding | Bind the zone to a campaign on an external ad network (the Source tab). |
| [**apiV1AdspacesIdOwnAdsAdIdDelete()**](AdspacesApi.md#apiV1AdspacesIdOwnAdsAdIdDelete) | **DELETE** /api/v1/adspaces/{id}/own-ads/{adId} | Delete an own ad. |
| [**apiV1AdspacesIdOwnAdsAdIdGet()**](AdspacesApi.md#apiV1AdspacesIdOwnAdsAdIdGet) | **GET** /api/v1/adspaces/{id}/own-ads/{adId} | Get one own ad. |
| [**apiV1AdspacesIdOwnAdsAdIdPut()**](AdspacesApi.md#apiV1AdspacesIdOwnAdsAdIdPut) | **PUT** /api/v1/adspaces/{id}/own-ads/{adId} | Update an own ad. |
| [**apiV1AdspacesIdOwnAdsAdIdTogglePut()**](AdspacesApi.md#apiV1AdspacesIdOwnAdsAdIdTogglePut) | **PUT** /api/v1/adspaces/{id}/own-ads/{adId}/toggle | Switch an own ad on/off. |
| [**apiV1AdspacesIdOwnAdsGet()**](AdspacesApi.md#apiV1AdspacesIdOwnAdsGet) | **GET** /api/v1/adspaces/{id}/own-ads | List the zone&#39;s own (house / fallback) ads. |
| [**apiV1AdspacesIdOwnAdsMassActionPost()**](AdspacesApi.md#apiV1AdspacesIdOwnAdsMassActionPost) | **POST** /api/v1/adspaces/{id}/own-ads/mass-action | Activate, deactivate or delete several own ads. |
| [**apiV1AdspacesIdOwnAdsPost()**](AdspacesApi.md#apiV1AdspacesIdOwnAdsPost) | **POST** /api/v1/adspaces/{id}/own-ads | Add an own ad. |
| [**apiV1AdspacesIdPatch()**](AdspacesApi.md#apiV1AdspacesIdPatch) | **PATCH** /api/v1/adspaces/{id} | Update an existing adspace. |
| [**apiV1AdspacesIdPricesTypeGet()**](AdspacesApi.md#apiV1AdspacesIdPricesTypeGet) | **GET** /api/v1/adspaces/{id}/prices/{type} | Minimum (floor) prices per country for one device type. |
| [**apiV1AdspacesIdPricesTypePost()**](AdspacesApi.md#apiV1AdspacesIdPricesTypePost) | **POST** /api/v1/adspaces/{id}/prices/{type} | Replace the floor prices for one device type. |
| [**apiV1AdspacesIdRecoverPost()**](AdspacesApi.md#apiV1AdspacesIdRecoverPost) | **POST** /api/v1/adspaces/{id}/recover | Reverse a soft-delete on an adspace. |
| [**apiV1AdspacesIdWlCampaignsGet()**](AdspacesApi.md#apiV1AdspacesIdWlCampaignsGet) | **GET** /api/v1/adspaces/{id}/wl-campaigns | Campaigns that can be whitelisted on this zone, and the current whitelist. |
| [**apiV1AdspacesIdWlCampaignsPut()**](AdspacesApi.md#apiV1AdspacesIdWlCampaignsPut) | **PUT** /api/v1/adspaces/{id}/wl-campaigns | Replace the zone&#39;s whitelisted campaigns. |
| [**apiV1AdspacesMassDeletePost()**](AdspacesApi.md#apiV1AdspacesMassDeletePost) | **POST** /api/v1/adspaces/mass-delete | Soft-delete several ad zones. |
| [**apiV1AdspacesMassRecoverPost()**](AdspacesApi.md#apiV1AdspacesMassRecoverPost) | **POST** /api/v1/adspaces/mass-recover | Recover several soft-deleted ad zones. |
| [**apiV1AdspacesTemplatesGet()**](AdspacesApi.md#apiV1AdspacesTemplatesGet) | **GET** /api/v1/adspaces/templates | List your saved ad zone templates. |
| [**apiV1AdspacesTemplatesIdDelete()**](AdspacesApi.md#apiV1AdspacesTemplatesIdDelete) | **DELETE** /api/v1/adspaces/templates/{id} | Delete a template. |
| [**apiV1AdspacesTemplatesIdPut()**](AdspacesApi.md#apiV1AdspacesTemplatesIdPut) | **PUT** /api/v1/adspaces/templates/{id} | Replace a template&#39;s data. |
| [**apiV1AdspacesTemplatesPost()**](AdspacesApi.md#apiV1AdspacesTemplatesPost) | **POST** /api/v1/adspaces/templates | Save an ad zone template. |
| [**apiV1AdspacesTypePost()**](AdspacesApi.md#apiV1AdspacesTypePost) | **POST** /api/v1/adspaces/{type} | Create a new adspace. |
| [**apiV1AdspacesTypeSchemaGet()**](AdspacesApi.md#apiV1AdspacesTypeSchemaGet) | **GET** /api/v1/adspaces/{type}/_schema | Field metadata for create/update of one adspace type. |


## `apiV1AdspacesAddMultiplePost()`

```php
apiV1AdspacesAddMultiplePost($api_v1_adspaces_add_multiple_post_request): int[]
```

Create several ad zones of one type on one site.

The panel's \"Add multiple\". Each item is created exactly as `POST /api/v1/adspaces/{type}` would create it (same validation, same defaults), with the type's default settings block and, for banner types, `sizeid` as the banner size. Up to 100 items.  Items are created in order. The first refusal stops the run: the response is that item's error (e.g. 422 `validation_error` for a duplicate name) plus `failed_item` (index into `items`) and `created_ids` (what was created before it). Items with an empty name are skipped.  The type must be active, its module on and its right held; for an API key it must also be exposed to the API (`in_api`) — same gate and errors as `POST /api/v1/adspaces/{type}` (404 `unknown_adspace_type`, 403 `module_disabled` / `missing_right`). Keys pinned to `adzone_ids` / `site_ids` are refused.

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
$api_v1_adspaces_add_multiple_post_request = {"siteid":30067,"adzone_typeid":2,"sizeid":1,"items":[{"name":"Header 728x90"},{"name":"Footer 728x90"}]}; // \GoAdServerApi\Model\ApiV1AdspacesAddMultiplePostRequest

try {
    $result = $apiInstance->apiV1AdspacesAddMultiplePost($api_v1_adspaces_add_multiple_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesAddMultiplePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_adspaces_add_multiple_post_request** | [**\GoAdServerApi\Model\ApiV1AdspacesAddMultiplePostRequest**](../Model/ApiV1AdspacesAddMultiplePostRequest.md)|  | |

### Return type

**int[]**

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AdspacesExportCodesGet()`

```php
apiV1AdspacesExportCodesGet($siteid, $all, $sids): \GoAdServerApi\Model\ApiV1AdspacesExportCodesGet200ResponseInner[]
```

Embed codes for many ad zones at once, grouped by site.

The panel's bulk \"Export codes\". Returns one entry per site with the site's controller tag (`ctrl_js`, paste once per page) and the per-zone snippets. Without `sids` (or with `all=1`) every live zone of the account is included; narrow with `siteid` or a `sids` list. For one zone use `GET /api/v1/adspaces/{id}/code`.  Keys pinned to `adzone_ids` / `site_ids` are refused (`403 forbidden_resource`) — the export spans the whole account.

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
$siteid = 56; // int | Only this site's zones.
$all = 1; // int | 1 = every zone; 0 = only the zones in `sids`.
$sids = 11865993,11865986; // string | Comma-separated ad zone ids (used with all=0).

try {
    $result = $apiInstance->apiV1AdspacesExportCodesGet($siteid, $all, $sids);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesExportCodesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **siteid** | **int**| Only this site&#39;s zones. | [optional] |
| **all** | **int**| 1 &#x3D; every zone; 0 &#x3D; only the zones in &#x60;sids&#x60;. | [optional] [default to 1] |
| **sids** | **string**| Comma-separated ad zone ids (used with all&#x3D;0). | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1AdspacesExportCodesGet200ResponseInner[]**](../Model/ApiV1AdspacesExportCodesGet200ResponseInner.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AdspacesGet()`

```php
apiV1AdspacesGet($site_id, $state, $type, $parent_id, $search, $sort, $page, $per_page): \GoAdServerApi\Model\ApiV1AdspacesGet200Response
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
$state = 'live'; // string | `deleted` lists soft-deleted zones (for recover / mass-recover).
$type = 'type_example'; // string | Adspace type key (e.g. `popads`) or numeric adzone_typeid. Unknown key → 422.
$parent_id = 56; // int | Children of this zone; 0 = top-level zones only.
$search = 'search_example'; // string | Substring of the name, or an exact zone id.
$sort = '-id'; // string
$page = 1; // int
$per_page = 100; // int

try {
    $result = $apiInstance->apiV1AdspacesGet($site_id, $state, $type, $parent_id, $search, $sort, $page, $per_page);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **site_id** | **int**| Filter to one site. | [optional] |
| **state** | **string**| &#x60;deleted&#x60; lists soft-deleted zones (for recover / mass-recover). | [optional] [default to &#39;live&#39;] |
| **type** | **string**| Adspace type key (e.g. &#x60;popads&#x60;) or numeric adzone_typeid. Unknown key → 422. | [optional] |
| **parent_id** | **int**| Children of this zone; 0 &#x3D; top-level zones only. | [optional] |
| **search** | **string**| Substring of the name, or an exact zone id. | [optional] |
| **sort** | **string**|  | [optional] [default to &#39;-id&#39;] |
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

## `apiV1AdspacesIdBlockedAdvertisersGet()`

```php
apiV1AdspacesIdBlockedAdvertisersGet($id)
```

Advertiser block filters that exclude this ad zone.

Read-only. The advertiser-side block lists (not hidden ones) that name this zone. `campaignids` is the comma-separated campaigns the filter applies to.

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
    $apiInstance->apiV1AdspacesIdBlockedAdvertisersGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdBlockedAdvertisersGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1AdspacesIdChildrenGet()`

```php
apiV1AdspacesIdChildrenGet($id)
```

List an ad zone's child zones.

Live zones whose `parentid` is this zone — the in-video sub-zones under a player zone. Rows are the stored zone records (all `publisher_adzones` columns) plus `website_name`. Same data as `GET /api/v1/adspaces?parent_id={id}` in the list shape.

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
    $apiInstance->apiV1AdspacesIdChildrenGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdChildrenGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1AdspacesIdClonePost()`

```php
apiV1AdspacesIdClonePost($id, $api_v1_adspaces_id_clone_post_request)
```

Duplicate an ad zone.

Copies the zone (type, site, settings) under a new name. Options choose what comes along. `copy_campaigns` copies the whitelisted campaigns only where the account may manage them (see `PUT /api/v1/adspaces/{id}/wl-campaigns`); otherwise the zone is still duplicated and `campaigns_skipped` is true.

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
$api_v1_adspaces_id_clone_post_request = {"name":"Homepage 300x250 (copy)","copy_campaigns":true,"copy_own_ads":true,"copy_settings":true}; // \GoAdServerApi\Model\ApiV1AdspacesIdClonePostRequest

try {
    $apiInstance->apiV1AdspacesIdClonePost($id, $api_v1_adspaces_id_clone_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdClonePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_adspaces_id_clone_post_request** | [**\GoAdServerApi\Model\ApiV1AdspacesIdClonePostRequest**](../Model/ApiV1AdspacesIdClonePostRequest.md)|  | |

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

## `apiV1AdspacesIdCodeGet()`

```php
apiV1AdspacesIdCodeGet($id, $layout): \GoAdServerApi\Model\ApiV1AdspacesIdCodeGet200Response
```

Embed-code snippets for an adspace.

Returns the JS embed snippets a publisher pastes into their site — the natural last step of the create flow.  Three pieces, all server-rendered with the tenant's ad domain, the publisher's user id, EaCtrl name, banner dimensions, and per-type plugin call resolved:  - `site.ctrl_js` — controller `<script>` tag. Paste it once per   page, before the per-zone snippets (typically in `<head>`). - `site.ctrl_js_failover` — an alternative to `ctrl_js`, pasted   instead of it: an inline loader that tries several ad domains in   turn (the zone's own, the publisher's own, then the network's   that are not on a block list) and uses the first one an ad   blocker lets through; it queues the zones' `add()` calls until   the controller is there. Empty when fewer than two usable ad   domains are available. - `js_code` — per-zone JavaScript call (wrap in `<script>` tags).   This is what goes at each ad slot. - `ad_tag` — `<iframe src=\"…\">` alternative for callers that   can't run JS. - `alternative` — no-JS fallback `<a><img></a>` snippet.  Native zones (nativeads, type 3) take `?layout=<key>`: a ready-made design, so a publisher does not have to write the templates. The response then carries the layout's `tpl_body` / `tpl_row` / `tpl_item` in `js_code` and a `<style>` block scoped to the zone (`#id_<adspace_id>`) in front of the placement in `ad_tag`. Keys: `default` (the stock template), `cards`, `list` (sidebar list), `tiles` (image tiles), `feed`, `links` (text-only sponsored links); the response of a native zone lists them in `layouts`. The same layouts are the picker in the panel's Code tab. Creator widget zones (creator_widget, type 34) take their own keys: `default` (the example template), `caption` (avatar and text under the clip), `overlay` (text on the clip), `card`, `clip` (clip only); the template is `tpl_item` (the plugin has no body/row templates) and the `<style>` block is scoped the same way. An unknown key for the zone's type is refused with 422 (`layout`); other zone types ignore the parameter.  Banner, native, reel and creator widget zones (types 2, 3, 31, 34) also carry `amp`: the code for AMP pages and Web Stories, which cannot run the controller. AMP fetches the ad itself through `amp-list` + `amp-mustache` from goadserver's `/ampad.go?spaceid=<id>` (JSON, AMP CORS; AMP removed `amp-ad type=\"custom\"` in 2025). The story code is a sponsored `<amp-story-page>` to paste between the story's own pages (image ads; a video ad shows its poster). Image and video ads only — HTML / iframe creatives are skipped. Impressions are counted by a 1×1 pixel the template loads when the ad renders.  Resource-pinned keys (`resource_filter.adzone_ids` / `resource_filter.site_ids`) reject reads outside the allow-list with `403 forbidden_resource`.

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
$layout = 'layout_example'; // string | Native (cards…links) and creator widget (caption…clip) zones: ready-made layout to render the code with (see above).

try {
    $result = $apiInstance->apiV1AdspacesIdCodeGet($id, $layout);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdCodeGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **layout** | **string**| Native (cards…links) and creator widget (caption…clip) zones: ready-made layout to render the code with (see above). | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1AdspacesIdCodeGet200Response**](../Model/ApiV1AdspacesIdCodeGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AdspacesIdDelete()`

```php
apiV1AdspacesIdDelete($id, $confirm_smart_zones)
```

Soft-delete an adspace.

Sets `is_deleted = 1` — the row is hidden from listings but kept for reporting joins. Undo with `POST /api/v1/adspaces/{id}/recover`.  Refused with **409 `has_flatrate_campaigns`** while a running or pending flat-rate deal is booked on the zone. A zone with smart zones attached answers **409 `has_smart_zones`** (with their count) until you repeat the call with `?confirm_smart_zones=1`; those smart zones are then closed with the zone and come back on recover.

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
$confirm_smart_zones = 56; // int | 1 = also close the zone's smart zones.

try {
    $apiInstance->apiV1AdspacesIdDelete($id, $confirm_smart_zones);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **confirm_smart_zones** | **int**| 1 &#x3D; also close the zone&#39;s smart zones. | [optional] |

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

## `apiV1AdspacesIdGet()`

```php
apiV1AdspacesIdGet($id): \GoAdServerApi\Model\Adspace
```

Get a single adspace.

Returns the full adspace record. Body shape matches what `POST /api/v1/adspaces/{type}` accepts, so a create-then-fetch loop or an update round-trip works without translation:  - Common top-level fields (`name`, `siteid`, `autoapprove`,   `isvisible`, …) — matching the create body - `adspace_type` — the type config key (e.g. `bannerads`) - `adzone_typeid` / `adtype_id` / `adsizeid` — numeric ids - The type-specific settings block hoisted to top level   (e.g. `banner_settings`, `pop_settings`, `native_settings`) - `json_settings` — the raw assembled blob for inspection - The Campaigns-tab switches (`only_wl_campaigns`,   `only_tagged_campaigns`, `force_wl_targeting`,   `skip_rtb_when_wl`), `useflatbuys`, `parentid`, `new_zoneid`,   and the Source tab's `network_binding`  Keys pinned to `adzone_ids`, or to `site_ids` (checked against the zone's site), get `403 forbidden_resource` for other zones.

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
    $result = $apiInstance->apiV1AdspacesIdGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\Adspace**](../Model/Adspace.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AdspacesIdNetworkBindingDelete()`

```php
apiV1AdspacesIdNetworkBindingDelete($id)
```

Remove the zone's network binding.

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
    $apiInstance->apiV1AdspacesIdNetworkBindingDelete($id);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdNetworkBindingDelete: ', $e->getMessage(), PHP_EOL;
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

## `apiV1AdspacesIdNetworkBindingPut()`

```php
apiV1AdspacesIdNetworkBindingPut($id, $api_v1_adspaces_id_network_binding_put_request)
```

Bind the zone to a campaign on an external ad network (the Source tab).

Records that this zone's traffic is bought from a campaign on one of your ad networks (`GET /api/v1/tracker/ad-networks`). The network's cost import then attributes cost to the zone and, with `auto_api`, the campaign is paused/activated through the network's API. The current binding is `network_binding` on `GET /api/v1/adspaces/{id}`.

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
$api_v1_adspaces_id_network_binding_put_request = {"adnetwork_id":77,"adnetwork_campaignid":5512309,"auto_api":true}; // \GoAdServerApi\Model\ApiV1AdspacesIdNetworkBindingPutRequest

try {
    $apiInstance->apiV1AdspacesIdNetworkBindingPut($id, $api_v1_adspaces_id_network_binding_put_request);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdNetworkBindingPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_adspaces_id_network_binding_put_request** | [**\GoAdServerApi\Model\ApiV1AdspacesIdNetworkBindingPutRequest**](../Model/ApiV1AdspacesIdNetworkBindingPutRequest.md)|  | |

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

## `apiV1AdspacesIdOwnAdsAdIdDelete()`

```php
apiV1AdspacesIdOwnAdsAdIdDelete($id, $ad_id)
```

Delete an own ad.

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
$ad_id = 56; // int

try {
    $apiInstance->apiV1AdspacesIdOwnAdsAdIdDelete($id, $ad_id);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdOwnAdsAdIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **ad_id** | **int**|  | |

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

## `apiV1AdspacesIdOwnAdsAdIdGet()`

```php
apiV1AdspacesIdOwnAdsAdIdGet($id, $ad_id)
```

Get one own ad.

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
$ad_id = 56; // int

try {
    $apiInstance->apiV1AdspacesIdOwnAdsAdIdGet($id, $ad_id);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdOwnAdsAdIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **ad_id** | **int**|  | |

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

## `apiV1AdspacesIdOwnAdsAdIdPut()`

```php
apiV1AdspacesIdOwnAdsAdIdPut($id, $ad_id, $own_ad_write)
```

Update an own ad.

Same fields as create; send the ones to change.

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
$ad_id = 56; // int
$own_ad_write = new \GoAdServerApi\Model\OwnAdWrite(); // \GoAdServerApi\Model\OwnAdWrite

try {
    $apiInstance->apiV1AdspacesIdOwnAdsAdIdPut($id, $ad_id, $own_ad_write);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdOwnAdsAdIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **ad_id** | **int**|  | |
| **own_ad_write** | [**\GoAdServerApi\Model\OwnAdWrite**](../Model/OwnAdWrite.md)|  | |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`, `multipart/form-data`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AdspacesIdOwnAdsAdIdTogglePut()`

```php
apiV1AdspacesIdOwnAdsAdIdTogglePut($id, $ad_id)
```

Switch an own ad on/off.

Flips `isactive`. No body.

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
$ad_id = 56; // int

try {
    $apiInstance->apiV1AdspacesIdOwnAdsAdIdTogglePut($id, $ad_id);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdOwnAdsAdIdTogglePut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **ad_id** | **int**|  | |

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

## `apiV1AdspacesIdOwnAdsGet()`

```php
apiV1AdspacesIdOwnAdsGet($id)
```

List the zone's own (house / fallback) ads.

Ads the publisher runs on the zone itself (the panel's Own ads tab), served when `useownbanners` is on. Rows are the stored records.

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
    $apiInstance->apiV1AdspacesIdOwnAdsGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdOwnAdsGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1AdspacesIdOwnAdsMassActionPost()`

```php
apiV1AdspacesIdOwnAdsMassActionPost($id, $api_v1_adspaces_id_own_ads_mass_action_post_request)
```

Activate, deactivate or delete several own ads.

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
$api_v1_adspaces_id_own_ads_mass_action_post_request = {"action":"deactivate","ids":[3301,3302]}; // \GoAdServerApi\Model\ApiV1AdspacesIdOwnAdsMassActionPostRequest

try {
    $apiInstance->apiV1AdspacesIdOwnAdsMassActionPost($id, $api_v1_adspaces_id_own_ads_mass_action_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdOwnAdsMassActionPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_adspaces_id_own_ads_mass_action_post_request** | [**\GoAdServerApi\Model\ApiV1AdspacesIdOwnAdsMassActionPostRequest**](../Model/ApiV1AdspacesIdOwnAdsMassActionPostRequest.md)|  | |

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

## `apiV1AdspacesIdOwnAdsPost()`

```php
apiV1AdspacesIdOwnAdsPost($id, $own_ad_write)
```

Add an own ad.

JSON body, or `multipart/form-data` with the same fields plus an image file (max 32 MB form). Unknown-field checking applies to JSON bodies only.

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
$own_ad_write = {"title":"House promo","bannertype":1,"bannerurl":"https://example.com/promo","weight":10,"isactive":1,"width":300,"height":250}; // \GoAdServerApi\Model\OwnAdWrite

try {
    $apiInstance->apiV1AdspacesIdOwnAdsPost($id, $own_ad_write);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdOwnAdsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **own_ad_write** | [**\GoAdServerApi\Model\OwnAdWrite**](../Model/OwnAdWrite.md)|  | |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`, `multipart/form-data`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AdspacesIdPatch()`

```php
apiV1AdspacesIdPatch($id, $request_body): \GoAdServerApi\Model\ApiV1AdspacesIdPatch200Response
```

Update an existing adspace.

Update fields on an existing adspace. Send only the keys you want to change — omitted fields keep their current value. The `adzone_typeid` is immutable; once an adspace is created its type cannot be switched.  Use `GET /api/v1/adspaces/{type}/_schema` to enumerate the legal field set for the type. The same body shape that creates an adspace works for update.  **Settings blocks merge.** A `*_settings` block is merged into the stored one: other blocks are kept, and keys of the block you do not send are kept (one level deep — nested objects such as `closepop` or `css` are replaced whole). Send a block as `null` to remove it. So `{\"banner_settings\": {\"sizeid\": 7}}` changes only the size.  `parentid` must be another live zone of yours (0 detaches); `siteid` moves the zone to another of your sites (historical stats stay on the old site; a site-pinned key may only move it within its pin). Whitelisted campaigns are set with `PUT /api/v1/adspaces/{id}/wl-campaigns`, floor prices with `POST /api/v1/adspaces/{id}/prices/{type}`.

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
$request_body = {"name":"Homepage 300x250 (renamed)","autoapprove":true,"banner_settings":{"sizeid":7}}; // array<string,mixed>

try {
    $result = $apiInstance->apiV1AdspacesIdPatch($id, $request_body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdPatch: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **request_body** | [**array<string,mixed>**](../Model/mixed.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1AdspacesIdPatch200Response**](../Model/ApiV1AdspacesIdPatch200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AdspacesIdPricesTypeGet()`

```php
apiV1AdspacesIdPricesTypeGet($id, $type, $device_typeid)
```

Minimum (floor) prices per country for one device type.

The panel's Prices tab. `{type}` = `cpm` (CPM buys) or `flatrate` (flat-rate bookings). Prices are **currency units** (the account's currency) per 1000 impressions; country `\"0\"` = all countries.  Shapes differ by type, as in the panel: `cpm` returns an array of `{country, cpm}`, `flatrate` an object `{country: price}`.

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
$type = 'type_example'; // string
$device_typeid = 0; // int | Device type (see /api/v1/lookups/device-types); 0 = all devices.

try {
    $apiInstance->apiV1AdspacesIdPricesTypeGet($id, $type, $device_typeid);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdPricesTypeGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **type** | **string**|  | |
| **device_typeid** | **int**| Device type (see /api/v1/lookups/device-types); 0 &#x3D; all devices. | [optional] [default to 0] |

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

## `apiV1AdspacesIdPricesTypePost()`

```php
apiV1AdspacesIdPricesTypePost($id, $type, $api_v1_adspaces_id_prices_type_post_request)
```

Replace the floor prices for one device type.

Replaces every price of this zone + `{type}` + `device_typeid` with `cpm_price` (send `[]` to clear). Prices in **currency units** per 1000 impressions (flat-rate prices are stored in cents internally; the API takes and returns currency units). Country `\"\"` or `\"0\"` = all countries.

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
$type = 'type_example'; // string
$api_v1_adspaces_id_prices_type_post_request = {"device_typeid":0,"cpm_price":[{"country":"0","cpm":0.5},{"country":"US","cpm":1.2}]}; // \GoAdServerApi\Model\ApiV1AdspacesIdPricesTypePostRequest

try {
    $apiInstance->apiV1AdspacesIdPricesTypePost($id, $type, $api_v1_adspaces_id_prices_type_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdPricesTypePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **type** | **string**|  | |
| **api_v1_adspaces_id_prices_type_post_request** | [**\GoAdServerApi\Model\ApiV1AdspacesIdPricesTypePostRequest**](../Model/ApiV1AdspacesIdPricesTypePostRequest.md)|  | |

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

## `apiV1AdspacesIdRecoverPost()`

```php
apiV1AdspacesIdRecoverPost($id)
```

Reverse a soft-delete on an adspace.

Sets `is_deleted = 0` so the adspace appears in listings again, and restores the smart zones that were closed with it. Returns 404 if the adspace doesn't exist or isn't currently deleted. Find deleted zones with `GET /api/v1/adspaces?state=deleted`.

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
    $apiInstance->apiV1AdspacesIdRecoverPost($id);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdRecoverPost: ', $e->getMessage(), PHP_EOL;
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

## `apiV1AdspacesIdWlCampaignsGet()`

```php
apiV1AdspacesIdWlCampaignsGet($id)
```

Campaigns that can be whitelisted on this zone, and the current whitelist.

The panel's Campaigns tab. `data` lists the RON campaigns of this zone's ad type (`connected` true for the whitelisted ones, with their `weight`); `connected` is the stored whitelist `{campaign_id: weight}`. `force_wl_targeting` and `skip_rtb_when_wl` are on `GET /api/v1/adspaces/{id}`.

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
    $apiInstance->apiV1AdspacesIdWlCampaignsGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdWlCampaignsGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1AdspacesIdWlCampaignsPut()`

```php
apiV1AdspacesIdWlCampaignsPut($id, $api_v1_adspaces_id_wl_campaigns_put_request)
```

Replace the zone's whitelisted campaigns.

Replaces the whitelist with `campaigns` (send `[]` to clear it) and sets the two related switches. Weights are clamped to 0-100. Campaign ids outside your network are dropped. `force_wl_targeting` is only stored when at least one campaign remains.  Gated like the panel: the tenant's `connect_campaigns` module must be on (else **403 `feature_disabled`**), and on a non-Ad-Manager tenant only an administrator may manage it (**403 `forbidden`**).

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
$api_v1_adspaces_id_wl_campaigns_put_request = {"campaigns":[{"id":1201,"weight":60},{"id":1207,"weight":40}],"force_wl_targeting":false,"skip_rtb_when_wl":true}; // \GoAdServerApi\Model\ApiV1AdspacesIdWlCampaignsPutRequest

try {
    $apiInstance->apiV1AdspacesIdWlCampaignsPut($id, $api_v1_adspaces_id_wl_campaigns_put_request);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesIdWlCampaignsPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_adspaces_id_wl_campaigns_put_request** | [**\GoAdServerApi\Model\ApiV1AdspacesIdWlCampaignsPutRequest**](../Model/ApiV1AdspacesIdWlCampaignsPutRequest.md)|  | |

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

## `apiV1AdspacesMassDeletePost()`

```php
apiV1AdspacesMassDeletePost($api_v1_adspaces_mass_delete_post_request)
```

Soft-delete several ad zones.

Soft-deletes every listed zone you own (`is_deleted = 1`); ids that are not yours or already deleted are ignored.  Refused with **409 `has_flatrate_campaigns`** when any of them carries a running or pending flat-rate deal. Zones with smart zones attached answer **409 `has_smart_zones`** (with the count) until you repeat the call with `confirm_smart_zones: true` — the smart zones are then closed with their zone and come back on recover. Recover with `POST /api/v1/adspaces/mass-recover` (list deleted zones with `GET /api/v1/adspaces?state=deleted`).  Keys pinned to `adzone_ids` / `site_ids` are refused.

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
$api_v1_adspaces_mass_delete_post_request = {"ids":[11865993,11865986],"confirm_smart_zones":true}; // \GoAdServerApi\Model\ApiV1AdspacesMassDeletePostRequest

try {
    $apiInstance->apiV1AdspacesMassDeletePost($api_v1_adspaces_mass_delete_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesMassDeletePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_adspaces_mass_delete_post_request** | [**\GoAdServerApi\Model\ApiV1AdspacesMassDeletePostRequest**](../Model/ApiV1AdspacesMassDeletePostRequest.md)|  | |

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

## `apiV1AdspacesMassRecoverPost()`

```php
apiV1AdspacesMassRecoverPost($api_v1_adspaces_mass_recover_post_request)
```

Recover several soft-deleted ad zones.

Reverses `mass-delete` / `DELETE /api/v1/adspaces/{id}` for every listed zone you own; the smart zones closed with them come back too. Keys pinned to `adzone_ids` / `site_ids` are refused.

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
$api_v1_adspaces_mass_recover_post_request = {"ids":[11865993]}; // \GoAdServerApi\Model\ApiV1AdspacesMassRecoverPostRequest

try {
    $apiInstance->apiV1AdspacesMassRecoverPost($api_v1_adspaces_mass_recover_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesMassRecoverPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_adspaces_mass_recover_post_request** | [**\GoAdServerApi\Model\ApiV1AdspacesMassRecoverPostRequest**](../Model/ApiV1AdspacesMassRecoverPostRequest.md)|  | |

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

## `apiV1AdspacesTemplatesGet()`

```php
apiV1AdspacesTemplatesGet(): \GoAdServerApi\Model\ApiV1AdspacesTemplatesGet200ResponseInner[]
```

List your saved ad zone templates.

Templates are the panel's saved \"Add multiple\" presets: a name plus an opaque `data` object the panel stores and replays. The server does not interpret `data`.

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

try {
    $result = $apiInstance->apiV1AdspacesTemplatesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesTemplatesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1AdspacesTemplatesGet200ResponseInner[]**](../Model/ApiV1AdspacesTemplatesGet200ResponseInner.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AdspacesTemplatesIdDelete()`

```php
apiV1AdspacesTemplatesIdDelete($id)
```

Delete a template.

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
    $apiInstance->apiV1AdspacesTemplatesIdDelete($id);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesTemplatesIdDelete: ', $e->getMessage(), PHP_EOL;
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

## `apiV1AdspacesTemplatesIdPut()`

```php
apiV1AdspacesTemplatesIdPut($id, $api_v1_adspaces_templates_id_put_request)
```

Replace a template's data.

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
$api_v1_adspaces_templates_id_put_request = new \GoAdServerApi\Model\ApiV1AdspacesTemplatesIdPutRequest(); // \GoAdServerApi\Model\ApiV1AdspacesTemplatesIdPutRequest

try {
    $apiInstance->apiV1AdspacesTemplatesIdPut($id, $api_v1_adspaces_templates_id_put_request);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesTemplatesIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_adspaces_templates_id_put_request** | [**\GoAdServerApi\Model\ApiV1AdspacesTemplatesIdPutRequest**](../Model/ApiV1AdspacesTemplatesIdPutRequest.md)|  | |

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

## `apiV1AdspacesTemplatesPost()`

```php
apiV1AdspacesTemplatesPost($api_v1_adspaces_templates_post_request)
```

Save an ad zone template.

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
$api_v1_adspaces_templates_post_request = {"name":"Blog layout","data":{"adzone_typeid":2,"sizeid":1,"items":[{"name":"Header"}]}}; // \GoAdServerApi\Model\ApiV1AdspacesTemplatesPostRequest

try {
    $apiInstance->apiV1AdspacesTemplatesPost($api_v1_adspaces_templates_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesTemplatesPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_adspaces_templates_post_request** | [**\GoAdServerApi\Model\ApiV1AdspacesTemplatesPostRequest**](../Model/ApiV1AdspacesTemplatesPostRequest.md)|  | |

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

## `apiV1AdspacesTypePost()`

```php
apiV1AdspacesTypePost($type, $adspace_create): \GoAdServerApi\Model\ApiV1AdspacesIdPatch200Response
```

Create a new adspace.

Create an adspace of the given `{type}`. Required: `name` + `siteid` (and `adsizeid` or `banner_settings.sizeid` for banner-flavored types). Everything else has a sensible default.  **Type identity comes from the path, not the body.** The server resolves the `{type}` path param to its `adzone_typeid` and `adtype_id` and injects them server-side — you do **not** need to pass `adzone_typeid` or `adtype_id` in the body. Any value sent for those keys is overwritten.  The full per-type field list is at `GET /api/v1/adspaces/{type}/_schema` — pass that schema's body shape here. The type-specific settings go under the body key named in `settings_block.name` (e.g. `banner_settings`, `pop_settings`, `native_settings`).  Discovery flow: 1. `GET /api/v1/lookups/adspace-types` — find the `{type}` keys    the tenant exposes and their per-type `options` flags. 2. `GET /api/v1/sites` — pick a `siteid`. 3. `GET /api/v1/lookups/banner-sizes` — pick a banner size when    creating banner-flavored zones. 4. `GET /api/v1/adspaces/{type}/_schema` — enumerate all fields. 5. `POST /api/v1/adspaces/{type}` with the body.  Resource-pinned keys (`resource_filter.site_ids`) reject creates outside the allow-list with `403 forbidden_resource`.

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
$type = 'type_example'; // string
$adspace_create = new \GoAdServerApi\Model\AdspaceCreate(); // \GoAdServerApi\Model\AdspaceCreate

try {
    $result = $apiInstance->apiV1AdspacesTypePost($type, $adspace_create);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesTypePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **adspace_create** | [**\GoAdServerApi\Model\AdspaceCreate**](../Model/AdspaceCreate.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1AdspacesIdPatch200Response**](../Model/ApiV1AdspacesIdPatch200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AdspacesTypeSchemaGet()`

```php
apiV1AdspacesTypeSchemaGet($type): \GoAdServerApi\Model\AdspaceSchema
```

Field metadata for create/update of one adspace type.

Discovery endpoint mirroring `GET /api/v1/campaigns/{type}/_schema`. Returns the canonical list of public field names a `POST /api/v1/adspaces/{type}` accepts, with type, required-flag, default value, an example, and a one-line description.  Adspaces are polymorphic — each type carries its own type-specific block (e.g. `banner_settings` for `bannerads`, `pop_settings` for `popads`, `native_settings` for `nativeads`). The `settings_block` field of the response describes that block; send it under the body key `settings_block.name`.  Use `GET /api/v1/lookups/adspace-types` to discover which `{type}` values are exposable on the calling tenant.

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
$type = 'type_example'; // string | Adspace type config key — see `GET /api/v1/lookups/adspace-types`.

try {
    $result = $apiInstance->apiV1AdspacesTypeSchemaGet($type);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdspacesApi->apiV1AdspacesTypeSchemaGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**| Adspace type config key — see &#x60;GET /api/v1/lookups/adspace-types&#x60;. | |

### Return type

[**\GoAdServerApi\Model\AdspaceSchema**](../Model/AdspaceSchema.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
