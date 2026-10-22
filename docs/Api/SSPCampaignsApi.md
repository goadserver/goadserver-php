# GoAdServerApi\SSPCampaignsApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1PublisherSspCampaignsGet()**](SSPCampaignsApi.md#apiV1PublisherSspCampaignsGet) | **GET** /api/v1/publisher/ssp/campaigns | List SSP campaigns (feeds). |
| [**apiV1PublisherSspCampaignsIdClonePost()**](SSPCampaignsApi.md#apiV1PublisherSspCampaignsIdClonePost) | **POST** /api/v1/publisher/ssp/campaigns/{id}/clone | Clone an SSP campaign. |
| [**apiV1PublisherSspCampaignsIdGet()**](SSPCampaignsApi.md#apiV1PublisherSspCampaignsIdGet) | **GET** /api/v1/publisher/ssp/campaigns/{id} | Get one SSP campaign. |
| [**apiV1PublisherSspCampaignsIdPut()**](SSPCampaignsApi.md#apiV1PublisherSspCampaignsIdPut) | **PUT** /api/v1/publisher/ssp/campaigns/{id} | Update an SSP campaign. |
| [**apiV1PublisherSspCampaignsIdTestRtbGet()**](SSPCampaignsApi.md#apiV1PublisherSspCampaignsIdTestRtbGet) | **GET** /api/v1/publisher/ssp/campaigns/{id}/test-rtb | Context for an RTB test request. |
| [**apiV1PublisherSspCampaignsIdTestRtbPost()**](SSPCampaignsApi.md#apiV1PublisherSspCampaignsIdTestRtbPost) | **POST** /api/v1/publisher/ssp/campaigns/{id}/test-rtb | Send one test bid request to the feed&#39;s endpoint. |
| [**apiV1PublisherSspCampaignsIdToggleDeletePut()**](SSPCampaignsApi.md#apiV1PublisherSspCampaignsIdToggleDeletePut) | **PUT** /api/v1/publisher/ssp/campaigns/{id}/toggle-delete | Delete or restore an SSP campaign. |
| [**apiV1PublisherSspCampaignsIdTogglePausePut()**](SSPCampaignsApi.md#apiV1PublisherSspCampaignsIdTogglePausePut) | **PUT** /api/v1/publisher/ssp/campaigns/{id}/toggle-pause | Pause or resume an SSP campaign. |
| [**apiV1PublisherSspCampaignsIdWlRonCampaignsGet()**](SSPCampaignsApi.md#apiV1PublisherSspCampaignsIdWlRonCampaignsGet) | **GET** /api/v1/publisher/ssp/campaigns/{id}/wl-ron-campaigns | RON campaigns that can be whitelisted on a feed. |
| [**apiV1PublisherSspCampaignsPost()**](SSPCampaignsApi.md#apiV1PublisherSspCampaignsPost) | **POST** /api/v1/publisher/ssp/campaigns | Create an SSP campaign. |
| [**apiV1PublisherSspCampaignsSetupDataGet()**](SSPCampaignsApi.md#apiV1PublisherSspCampaignsSetupDataGet) | **GET** /api/v1/publisher/ssp/campaigns/setup-data | Lookups for the SSP campaign form. |
| [**apiV1PublisherSspFiltersGet()**](SSPCampaignsApi.md#apiV1PublisherSspFiltersGet) | **GET** /api/v1/publisher/ssp/filters | Filter values for the SSP campaign list. |
| [**apiV1PublisherSspNetworksGet()**](SSPCampaignsApi.md#apiV1PublisherSspNetworksGet) | **GET** /api/v1/publisher/ssp/networks | RTB networks your SSP campaigns use. |
| [**apiV1PublisherSspSellerDisclosurePut()**](SSPCampaignsApi.md#apiV1PublisherSspSellerDisclosurePut) | **PUT** /api/v1/publisher/ssp/seller-disclosure | Publish or hide your name in sellers.json. |
| [**apiV1PublisherSspSitesIdAdsTxtCheckPost()**](SSPCampaignsApi.md#apiV1PublisherSspSitesIdAdsTxtCheckPost) | **POST** /api/v1/publisher/ssp/sites/{id}/ads-txt-check | Re-check a site&#39;s ads.txt now. |
| [**apiV1PublisherSspSitesIdAdsTxtStatusGet()**](SSPCampaignsApi.md#apiV1PublisherSspSitesIdAdsTxtStatusGet) | **GET** /api/v1/publisher/ssp/sites/{id}/ads-txt-status | Last stored ads.txt check for a site. |


## `apiV1PublisherSspCampaignsGet()`

```php
apiV1PublisherSspCampaignsGet()
```

List SSP campaigns (feeds).

Every SSP campaign of the account, newest first, including paused and deleted ones (`campaignstate`: 0 active, 1 paused, 2 deleted). An SSP campaign is a feed that sells your inventory to — or buys RTB traffic from — another platform. The response is a bare JSON array, not paginated.  All SSP routes need one of the SSP rights (`mod_ssp`, `ssp_external`, `ssp_networks`, `ssp_exchange`, `ssp_headerbidding`) and answer for publisher and advertiser (ad-manager) accounts alike. Keys pinned to `adzone_ids` or `site_ids` are refused with `403 forbidden_resource`: a feed spans sites and zones the pin cannot follow.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SSPCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1PublisherSspCampaignsGet();
} catch (Exception $e) {
    echo 'Exception when calling SSPCampaignsApi->apiV1PublisherSspCampaignsGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1PublisherSspCampaignsIdClonePost()`

```php
apiV1PublisherSspCampaignsIdClonePost($id)
```

Clone an SSP campaign.

Copies every column into a new campaign named `<name>('Copy')`. A clone of an external/exchange feed gets its own fresh `hash`. No request body.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SSPCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1PublisherSspCampaignsIdClonePost($id);
} catch (Exception $e) {
    echo 'Exception when calling SSPCampaignsApi->apiV1PublisherSspCampaignsIdClonePost: ', $e->getMessage(), PHP_EOL;
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

## `apiV1PublisherSspCampaignsIdGet()`

```php
apiV1PublisherSspCampaignsIdGet($id)
```

Get one SSP campaign.

The full campaign as the edit form loads it. Targeting lists come back as string arrays (`\"0\"`/empty = all). `json_zone_urls` is the stored `{zoneId: {url, active}}` map, `zone_urls` the same as an array with zone names. Note: the browser-language targeting is read back as `language` (comma-separated) but written as `browserlanguages`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SSPCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1PublisherSspCampaignsIdGet($id);
} catch (Exception $e) {
    echo 'Exception when calling SSPCampaignsApi->apiV1PublisherSspCampaignsIdGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1PublisherSspCampaignsIdPut()`

```php
apiV1PublisherSspCampaignsIdPut($id, $ssp_campaign_write)
```

Update an SSP campaign.

**Full replace, not a patch**: every column is rewritten from the body, so a missing targeting list resets to \"all\" and a missing scalar to its default. Read the campaign first and send it back whole. Exceptions: `header_bidding` is locked to its create-time value, `dsp_ids` is kept unless an admin sets it, and an absent `bill_on_burl` keeps the stored value. Same validation (409) and `unknown_fields` (422) as create.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SSPCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$ssp_campaign_write = new \GoAdServerApi\Model\SspCampaignWrite(); // \GoAdServerApi\Model\SspCampaignWrite

try {
    $apiInstance->apiV1PublisherSspCampaignsIdPut($id, $ssp_campaign_write);
} catch (Exception $e) {
    echo 'Exception when calling SSPCampaignsApi->apiV1PublisherSspCampaignsIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **ssp_campaign_write** | [**\GoAdServerApi\Model\SspCampaignWrite**](../Model/SspCampaignWrite.md)|  | |

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

## `apiV1PublisherSspCampaignsIdTestRtbGet()`

```php
apiV1PublisherSspCampaignsIdTestRtbGet($id)
```

Context for an RTB test request.

The resolved ad type, request URL and RTB type of the campaign, the tenant's common banner sizes and the caller's IP/UA, as defaults for the POST.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SSPCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1PublisherSspCampaignsIdTestRtbGet($id);
} catch (Exception $e) {
    echo 'Exception when calling SSPCampaignsApi->apiV1PublisherSspCampaignsIdTestRtbGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1PublisherSspCampaignsIdTestRtbPost()`

```php
apiV1PublisherSspCampaignsIdTestRtbPost($id, $api_v1_publisher_ssp_campaigns_id_test_rtb_post_request)
```

Send one test bid request to the feed's endpoint.

Builds a request the way the ad server would for this campaign, sends it (outbound to public addresses only) and classifies the answer. Every field is optional. **`bidfloor` and `best_price` are CPM in currency units.**

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SSPCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_publisher_ssp_campaigns_id_test_rtb_post_request = {"ad_type":"banner","width":728,"height":90,"country":"DE","bidfloor":0.5}; // \GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdTestRtbPostRequest

try {
    $apiInstance->apiV1PublisherSspCampaignsIdTestRtbPost($id, $api_v1_publisher_ssp_campaigns_id_test_rtb_post_request);
} catch (Exception $e) {
    echo 'Exception when calling SSPCampaignsApi->apiV1PublisherSspCampaignsIdTestRtbPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_publisher_ssp_campaigns_id_test_rtb_post_request** | [**\GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdTestRtbPostRequest**](../Model/ApiV1PublisherSspCampaignsIdTestRtbPostRequest.md)|  | [optional] |

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

## `apiV1PublisherSspCampaignsIdToggleDeletePut()`

```php
apiV1PublisherSspCampaignsIdToggleDeletePut($id, $api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request)
```

Delete or restore an SSP campaign.

Soft delete (`isdeleted`). `{status: 0}` restores it.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SSPCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request = {"status":1}; // \GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdToggleDeletePutRequest

try {
    $apiInstance->apiV1PublisherSspCampaignsIdToggleDeletePut($id, $api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request);
} catch (Exception $e) {
    echo 'Exception when calling SSPCampaignsApi->apiV1PublisherSspCampaignsIdToggleDeletePut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request** | [**\GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdToggleDeletePutRequest**](../Model/ApiV1PublisherSspCampaignsIdToggleDeletePutRequest.md)|  | |

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

## `apiV1PublisherSspCampaignsIdTogglePausePut()`

```php
apiV1PublisherSspCampaignsIdTogglePausePut($id, $api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request)
```

Pause or resume an SSP campaign.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SSPCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request = {"status":0}; // \GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdTogglePausePutRequest

try {
    $apiInstance->apiV1PublisherSspCampaignsIdTogglePausePut($id, $api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request);
} catch (Exception $e) {
    echo 'Exception when calling SSPCampaignsApi->apiV1PublisherSspCampaignsIdTogglePausePut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request** | [**\GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdTogglePausePutRequest**](../Model/ApiV1PublisherSspCampaignsIdTogglePausePutRequest.md)|  | |

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

## `apiV1PublisherSspCampaignsIdWlRonCampaignsGet()`

```php
apiV1PublisherSspCampaignsIdWlRonCampaignsGet($id, $adtype, $siteid, $search, $ids)
```

RON campaigns that can be whitelisted on a feed.

Candidates for `wl_ron_campaign_ids`: RON campaigns of the feed's ad type (matching the site's main category when that module is on). `available: false` means the picker is off for this tenant (the `modules.publisher.connect_campaigns` module, ad-manager tenants only) — not an error. Use `id = 0` with `?adtype=` for a campaign that is not saved yet. `ids` echoes the current selection.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SSPCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | SSP campaign id, or 0.
$adtype = 56; // int | Ad type when `id` is 0.
$siteid = 56; // int | Override the site used for the category match (must be yours).
$search = 'search_example'; // string
$ids = 'ids_example'; // string | Comma-separated currently-selected ids.

try {
    $apiInstance->apiV1PublisherSspCampaignsIdWlRonCampaignsGet($id, $adtype, $siteid, $search, $ids);
} catch (Exception $e) {
    echo 'Exception when calling SSPCampaignsApi->apiV1PublisherSspCampaignsIdWlRonCampaignsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| SSP campaign id, or 0. | |
| **adtype** | **int**| Ad type when &#x60;id&#x60; is 0. | [optional] |
| **siteid** | **int**| Override the site used for the category match (must be yours). | [optional] |
| **search** | **string**|  | [optional] |
| **ids** | **string**| Comma-separated currently-selected ids. | [optional] |

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

## `apiV1PublisherSspCampaignsPost()`

```php
apiV1PublisherSspCampaignsPost($ssp_campaign_write)
```

Create an SSP campaign.

Same save path as the panel's SSP campaign form. New campaigns start **paused** (`ispaused = 1`); resume with `PUT /api/v1/publisher/ssp/campaigns/{id}/toggle-pause` `{status: 0}`. External and exchange campaigns get a fresh feed `hash` (their endpoint identity and the token for the SSP stats feed).  `ssptype`: - `ssp` — outgoing RTB to a network (`rtb_network` required).   `use_type`: 0 = one request URL, 1 = per-ad-zone URLs   (`zoneurls`), 2 = smart-zone RTB feed. - `external` — an incoming feed a partner calls (`siteid` required). - `exchange` — incoming exchange feed (`ssp_pct` 1–100 required). - `header_bidding: true` (external/exchange only) collapses the   campaign to Prebid slots (`slots` required).  Validation failures answer **409** with `{\"errors\": {\"<field>\": \"<CODE>\"}}` (codes such as `EMPTY_NAME`, `NO_NETWORK_ID_GIVEN`, `NO_WEBSITE_SELECTED`, `WEBSITE_NOT_FOUND`, `SSP_EXCHANGE_PCT_ERROR`, `HB_SLOTS_REQUIRED`) — the panel's shape, kept as is. Unknown body fields are refused with `422 unknown_fields`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SSPCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$ssp_campaign_write = {"name":"Exo RTB feed","adtype":2,"ssptype":"ssp","rtbtype":"openrtb","rtb_network":5,"use_type":0,"request_url":"https://rtb.network.example/bid?feed=abc","device_type_ids":[1,3],"regionids":[],"ssp_pct":80}; // \GoAdServerApi\Model\SspCampaignWrite

try {
    $apiInstance->apiV1PublisherSspCampaignsPost($ssp_campaign_write);
} catch (Exception $e) {
    echo 'Exception when calling SSPCampaignsApi->apiV1PublisherSspCampaignsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **ssp_campaign_write** | [**\GoAdServerApi\Model\SspCampaignWrite**](../Model/SspCampaignWrite.md)|  | |

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

## `apiV1PublisherSspCampaignsSetupDataGet()`

```php
apiV1PublisherSspCampaignsSetupDataGet()
```

Lookups for the SSP campaign form.

Ad types, RTB networks, your websites, targeting lists (device types, OS, browsers, connection types, languages, carriers, IP pools, RTB categories, tier country lists), your other SSP campaigns, enabled modules, display-option config, and the values a partner needs to integrate (`ad_domain`, `rtb_domain`, `hb_endpoint`, `openrtb2_endpoint`, `sellers_json_url`, `ads_txt_line`, `seller_id`, `public_seller_disclosure`). In-app (Prebid Mobile, docs/IN_APP.md): the SDK's Prebid Server host is `openrtb2_endpoint`, its account id the campaign's token (`hash`), and each ad unit's config id names its adzone; apps send `app.bundle` instead of `site.page`, interstitial units `imp.instl`, rewarded ones `imp.rwdd` / `imp.ext.prebid.is_rewarded_inventory`. `ads_txt_line` goes in app-ads.txt on the app's developer website.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SSPCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1PublisherSspCampaignsSetupDataGet();
} catch (Exception $e) {
    echo 'Exception when calling SSPCampaignsApi->apiV1PublisherSspCampaignsSetupDataGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1PublisherSspFiltersGet()`

```php
apiV1PublisherSspFiltersGet()
```

Filter values for the SSP campaign list.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SSPCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1PublisherSspFiltersGet();
} catch (Exception $e) {
    echo 'Exception when calling SSPCampaignsApi->apiV1PublisherSspFiltersGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1PublisherSspNetworksGet()`

```php
apiV1PublisherSspNetworksGet($page, $per_page, $sort_by, $sort_dir, $filter_id, $filter_name)
```

RTB networks your SSP campaigns use.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SSPCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$page = 1; // int
$per_page = 20; // int
$sort_by = 'id'; // string
$sort_dir = 'desc'; // string
$filter_id = 56; // int
$filter_name = 'filter_name_example'; // string

try {
    $apiInstance->apiV1PublisherSspNetworksGet($page, $per_page, $sort_by, $sort_dir, $filter_id, $filter_name);
} catch (Exception $e) {
    echo 'Exception when calling SSPCampaignsApi->apiV1PublisherSspNetworksGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 20] |
| **sort_by** | **string**|  | [optional] [default to &#39;id&#39;] |
| **sort_dir** | **string**|  | [optional] [default to &#39;desc&#39;] |
| **filter_id** | **int**|  | [optional] |
| **filter_name** | **string**|  | [optional] |

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

## `apiV1PublisherSspSellerDisclosurePut()`

```php
apiV1PublisherSspSellerDisclosurePut($api_v1_publisher_ssp_seller_disclosure_put_request)
```

Publish or hide your name in sellers.json.

`true`: `/sellers.json` lists your company name and primary site domain. `false`: confidential, `seller_id` only (the default).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SSPCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_publisher_ssp_seller_disclosure_put_request = {"enabled":true}; // \GoAdServerApi\Model\ApiV1PublisherSspSellerDisclosurePutRequest

try {
    $apiInstance->apiV1PublisherSspSellerDisclosurePut($api_v1_publisher_ssp_seller_disclosure_put_request);
} catch (Exception $e) {
    echo 'Exception when calling SSPCampaignsApi->apiV1PublisherSspSellerDisclosurePut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_publisher_ssp_seller_disclosure_put_request** | [**\GoAdServerApi\Model\ApiV1PublisherSspSellerDisclosurePutRequest**](../Model/ApiV1PublisherSspSellerDisclosurePutRequest.md)|  | |

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

## `apiV1PublisherSspSitesIdAdsTxtCheckPost()`

```php
apiV1PublisherSspSitesIdAdsTxtCheckPost($id)
```

Re-check a site's ads.txt now.

Fetches `https://<site>/ads.txt`, looks for the network's line (`ad_domain`, your `seller_id`, `DIRECT`), stores and returns the result. Within the fetch cooldown the stored result is returned without a new fetch. No request body.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SSPCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Your site id.

try {
    $apiInstance->apiV1PublisherSspSitesIdAdsTxtCheckPost($id);
} catch (Exception $e) {
    echo 'Exception when calling SSPCampaignsApi->apiV1PublisherSspSitesIdAdsTxtCheckPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Your site id. | |

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

## `apiV1PublisherSspSitesIdAdsTxtStatusGet()`

```php
apiV1PublisherSspSitesIdAdsTxtStatusGet($id)
```

Last stored ads.txt check for a site.

No fetch. `checked:false` when the site was never checked.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SSPCampaignsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1PublisherSspSitesIdAdsTxtStatusGet($id);
} catch (Exception $e) {
    echo 'Exception when calling SSPCampaignsApi->apiV1PublisherSspSitesIdAdsTxtStatusGet: ', $e->getMessage(), PHP_EOL;
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
