# GoAdServerApi\SmartZonesApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1PublisherSmartzonesDistributionAdspacesIdGet()**](SmartZonesApi.md#apiV1PublisherSmartzonesDistributionAdspacesIdGet) | **GET** /api/v1/publisher/smartzones/distribution/adspaces/{id} | Network split for one ad zone. |
| [**apiV1PublisherSmartzonesDistributionPerSiteGet()**](SmartZonesApi.md#apiV1PublisherSmartzonesDistributionPerSiteGet) | **GET** /api/v1/publisher/smartzones/distribution/per-site | Traffic and earnings split across networks for one site. |
| [**apiV1PublisherSmartzonesDistributionSitesGet()**](SmartZonesApi.md#apiV1PublisherSmartzonesDistributionSitesGet) | **GET** /api/v1/publisher/smartzones/distribution/sites | Sites that carry smart zones. |
| [**apiV1PublisherSmartzonesGet()**](SmartZonesApi.md#apiV1PublisherSmartzonesGet) | **GET** /api/v1/publisher/smartzones | List smart zones. |
| [**apiV1PublisherSmartzonesIdDelete()**](SmartZonesApi.md#apiV1PublisherSmartzonesIdDelete) | **DELETE** /api/v1/publisher/smartzones/{id} | Delete a smart zone. |
| [**apiV1PublisherSmartzonesIdGet()**](SmartZonesApi.md#apiV1PublisherSmartzonesIdGet) | **GET** /api/v1/publisher/smartzones/{id} | Get one smart zone. |
| [**apiV1PublisherSmartzonesIdPut()**](SmartZonesApi.md#apiV1PublisherSmartzonesIdPut) | **PUT** /api/v1/publisher/smartzones/{id} | Update a smart zone. |
| [**apiV1PublisherSmartzonesImportPost()**](SmartZonesApi.md#apiV1PublisherSmartzonesImportPost) | **POST** /api/v1/publisher/smartzones/import | Bulk import smart zones. |
| [**apiV1PublisherSmartzonesNetworksGet()**](SmartZonesApi.md#apiV1PublisherSmartzonesNetworksGet) | **GET** /api/v1/publisher/smartzones/networks | List your smart network accounts. |
| [**apiV1PublisherSmartzonesNetworksIdDelete()**](SmartZonesApi.md#apiV1PublisherSmartzonesNetworksIdDelete) | **DELETE** /api/v1/publisher/smartzones/networks/{id} | Remove a smart network account. |
| [**apiV1PublisherSmartzonesNetworksPost()**](SmartZonesApi.md#apiV1PublisherSmartzonesNetworksPost) | **POST** /api/v1/publisher/smartzones/networks | Add or update a smart network account. |
| [**apiV1PublisherSmartzonesPost()**](SmartZonesApi.md#apiV1PublisherSmartzonesPost) | **POST** /api/v1/publisher/smartzones | Create a smart zone. |
| [**apiV1PublisherSmartzonesSetupDataGet()**](SmartZonesApi.md#apiV1PublisherSmartzonesSetupDataGet) | **GET** /api/v1/publisher/smartzones/setup-data | Lookups for the smart-zone form. |
| [**apiV1PublisherSmartzonesSspCampaignsGet()**](SmartZonesApi.md#apiV1PublisherSmartzonesSspCampaignsGet) | **GET** /api/v1/publisher/smartzones/ssp-campaigns | SSP campaigns usable as a smart zone&#39;s RTB feed. |


## `apiV1PublisherSmartzonesDistributionAdspacesIdGet()`

```php
apiV1PublisherSmartzonesDistributionAdspacesIdGet($id, $from, $to, $basis)
```

Network split for one ad zone.

Per-day share, eCPM and volume of each smart zone under one of your ad zones, which zone led each day, a country heat map and a summary. **Money is in currency units (not cents).**

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SmartZonesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Ad zone (adspace) id.
$from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime
$to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime
$basis = 'imp'; // string

try {
    $apiInstance->apiV1PublisherSmartzonesDistributionAdspacesIdGet($id, $from, $to, $basis);
} catch (Exception $e) {
    echo 'Exception when calling SmartZonesApi->apiV1PublisherSmartzonesDistributionAdspacesIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Ad zone (adspace) id. | |
| **from** | **\DateTime**|  | [optional] |
| **to** | **\DateTime**|  | [optional] |
| **basis** | **string**|  | [optional] [default to &#39;imp&#39;] |

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

## `apiV1PublisherSmartzonesDistributionPerSiteGet()`

```php
apiV1PublisherSmartzonesDistributionPerSiteGet($siteid, $from, $to, $basis)
```

Traffic and earnings split across networks for one site.

How one site's smart-zone traffic and earnings are spread over the networks: daily series (revenue, impressions, clicks, share, eCPM with the prior window), KPIs against the prior window of the same length, and a per-ad-zone breakdown with a health hint. **Money is in currency units (not cents)**: `earned*`, `avg_ecpm`, `ecpm_*` and the revenue/eCPM series. Impressions count views + clicks (click-only ad types log as clicks).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SmartZonesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$siteid = 56; // int
$from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inclusive start. Defaults to 6 days ago (both `from` and `to` must be sent to override).
$to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inclusive end. Defaults to today.
$basis = 'imp'; // string | Share basis — impressions or earnings.

try {
    $apiInstance->apiV1PublisherSmartzonesDistributionPerSiteGet($siteid, $from, $to, $basis);
} catch (Exception $e) {
    echo 'Exception when calling SmartZonesApi->apiV1PublisherSmartzonesDistributionPerSiteGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **siteid** | **int**|  | |
| **from** | **\DateTime**| Inclusive start. Defaults to 6 days ago (both &#x60;from&#x60; and &#x60;to&#x60; must be sent to override). | [optional] |
| **to** | **\DateTime**| Inclusive end. Defaults to today. | [optional] |
| **basis** | **string**| Share basis — impressions or earnings. | [optional] [default to &#39;imp&#39;] |

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

## `apiV1PublisherSmartzonesDistributionSitesGet()`

```php
apiV1PublisherSmartzonesDistributionSitesGet()
```

Sites that carry smart zones.

Your sites with at least one live smart zone on a network, with ad zone and zone counts — the picker for the per-site distribution view.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SmartZonesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1PublisherSmartzonesDistributionSitesGet();
} catch (Exception $e) {
    echo 'Exception when calling SmartZonesApi->apiV1PublisherSmartzonesDistributionSitesGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1PublisherSmartzonesGet()`

```php
apiV1PublisherSmartzonesGet($page, $per_page, $sort_by, $sort_dir, $network_id, $siteid, $spaceid, $select_type, $adzone_typeid, $status, $filter_id, $filter_name, $filter_adzone_name)
```

List smart zones.

Smart zones hang under one of your ad zones and send part of its traffic to an outside ad network (or, with `smart_networkid = 0`, split your own inventory). The same list the panel's Smart Zones page shows. Paginated with `page` / `per_page` (max 2000).  Keys pinned to `adzone_ids` or `site_ids` are refused with `403 forbidden_resource` on every smart-zone route: a smart zone, its network accounts and the import span zones the pin cannot follow.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SmartZonesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$page = 1; // int
$per_page = 20; // int
$sort_by = 'id'; // string
$sort_dir = 'desc'; // string
$network_id = 56; // int | Only zones on this smart network account (`smart_networkid`).
$siteid = 56; // int | Only zones under this site. `site_id` is accepted too.
$spaceid = 56; // int | Only zones under this ad zone.
$select_type = 56; // int | 0 = percentage, 1 = eCPM, 2 = RTB, 3 = eCPM + limit %.
$adzone_typeid = 56; // int | Only zones whose parent ad zone has this type id.
$status = 'status_example'; // string
$filter_id = 56; // int
$filter_name = 'filter_name_example'; // string | Substring match on the zone name.
$filter_adzone_name = 'filter_adzone_name_example'; // string | Substring match on the parent ad zone name.

try {
    $apiInstance->apiV1PublisherSmartzonesGet($page, $per_page, $sort_by, $sort_dir, $network_id, $siteid, $spaceid, $select_type, $adzone_typeid, $status, $filter_id, $filter_name, $filter_adzone_name);
} catch (Exception $e) {
    echo 'Exception when calling SmartZonesApi->apiV1PublisherSmartzonesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 20] |
| **sort_by** | **string**|  | [optional] [default to &#39;id&#39;] |
| **sort_dir** | **string**|  | [optional] [default to &#39;desc&#39;] |
| **network_id** | **int**| Only zones on this smart network account (&#x60;smart_networkid&#x60;). | [optional] |
| **siteid** | **int**| Only zones under this site. &#x60;site_id&#x60; is accepted too. | [optional] |
| **spaceid** | **int**| Only zones under this ad zone. | [optional] |
| **select_type** | **int**| 0 &#x3D; percentage, 1 &#x3D; eCPM, 2 &#x3D; RTB, 3 &#x3D; eCPM + limit %. | [optional] |
| **adzone_typeid** | **int**| Only zones whose parent ad zone has this type id. | [optional] |
| **status** | **string**|  | [optional] |
| **filter_id** | **int**|  | [optional] |
| **filter_name** | **string**| Substring match on the zone name. | [optional] |
| **filter_adzone_name** | **string**| Substring match on the parent ad zone name. | [optional] |

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

## `apiV1PublisherSmartzonesIdDelete()`

```php
apiV1PublisherSmartzonesIdDelete($id)
```

Delete a smart zone.

Soft delete (`isdeleted = 1`). There is no recover route; recreate the zone instead.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SmartZonesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1PublisherSmartzonesIdDelete($id);
} catch (Exception $e) {
    echo 'Exception when calling SmartZonesApi->apiV1PublisherSmartzonesIdDelete: ', $e->getMessage(), PHP_EOL;
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

## `apiV1PublisherSmartzonesIdGet()`

```php
apiV1PublisherSmartzonesIdGet($id)
```

Get one smart zone.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SmartZonesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1PublisherSmartzonesIdGet($id);
} catch (Exception $e) {
    echo 'Exception when calling SmartZonesApi->apiV1PublisherSmartzonesIdGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1PublisherSmartzonesIdPut()`

```php
apiV1PublisherSmartzonesIdPut($id, $api_v1_publisher_smartzones_id_put_request)
```

Update a smart zone.

Partial update: only the keys you send are written. Moving the zone to another ad zone (`spaceid`) is allowed; the new ad zone must be one of your live ones and `siteid` follows it (a bare `siteid` is ignored). Changing `smart_networkid` re-derives the catalogue `networkid`; both it and `ssp_campaignid` must be your own. Booleans may be sent as `true`/`false` or `1`/`0`.  Unknown body fields are refused with `422 unknown_fields`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SmartZonesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_publisher_smartzones_id_put_request = {"is_active":false}; // \GoAdServerApi\Model\ApiV1PublisherSmartzonesIdPutRequest

try {
    $apiInstance->apiV1PublisherSmartzonesIdPut($id, $api_v1_publisher_smartzones_id_put_request);
} catch (Exception $e) {
    echo 'Exception when calling SmartZonesApi->apiV1PublisherSmartzonesIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_publisher_smartzones_id_put_request** | [**\GoAdServerApi\Model\ApiV1PublisherSmartzonesIdPutRequest**](../Model/ApiV1PublisherSmartzonesIdPutRequest.md)|  | |

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

## `apiV1PublisherSmartzonesImportPost()`

```php
apiV1PublisherSmartzonesImportPost($api_v1_publisher_smartzones_import_post_request)
```

Bulk import smart zones.

Creates (or, when a zone with the same name already exists under the same ad zone, overwrites) one smart zone per `zones[]` entry, all on one network account. Entries without a `spaceid` or `url`, or whose ad zone is not one of your live ones, are skipped; `imported` counts the rows written. Each zone's site is its ad zone's site (the top-level `siteid` is ignored). Imported zones are active.  Unknown top-level body fields are refused with `422 unknown_fields`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SmartZonesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_publisher_smartzones_import_post_request = {"smart_network_id":14,"network_id":3,"select_type":1,"select_pct":20,"settings":{"skip_when_stats":1},"zones":[{"spaceid":4471,"name":"Exo 728 DE","zoneid":"12345","url":"https://a.network.example/zone/12345","device_type_ids":[1]}]}; // \GoAdServerApi\Model\ApiV1PublisherSmartzonesImportPostRequest

try {
    $apiInstance->apiV1PublisherSmartzonesImportPost($api_v1_publisher_smartzones_import_post_request);
} catch (Exception $e) {
    echo 'Exception when calling SmartZonesApi->apiV1PublisherSmartzonesImportPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_publisher_smartzones_import_post_request** | [**\GoAdServerApi\Model\ApiV1PublisherSmartzonesImportPostRequest**](../Model/ApiV1PublisherSmartzonesImportPostRequest.md)|  | |

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

## `apiV1PublisherSmartzonesNetworksGet()`

```php
apiV1PublisherSmartzonesNetworksGet()
```

List your smart network accounts.

Your accounts on outside ad networks (the \"Networks\" tab). `available_networks` lists the catalogue networks you can add, with the credential flags each needs.  **Credentials are masked on the API:** `apikey` and every string in `api_data` come back as `****` + their last 4 characters (`has_apikey` says whether a key is stored). Writing a masked value back through `POST .../networks` keeps the stored secret, so a read-modify-write round trip is safe; send the real value to change it.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SmartZonesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1PublisherSmartzonesNetworksGet();
} catch (Exception $e) {
    echo 'Exception when calling SmartZonesApi->apiV1PublisherSmartzonesNetworksGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1PublisherSmartzonesNetworksIdDelete()`

```php
apiV1PublisherSmartzonesNetworksIdDelete($id)
```

Remove a smart network account.

Hard delete. Refused while any live smart zone still uses the account.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SmartZonesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1PublisherSmartzonesNetworksIdDelete($id);
} catch (Exception $e) {
    echo 'Exception when calling SmartZonesApi->apiV1PublisherSmartzonesNetworksIdDelete: ', $e->getMessage(), PHP_EOL;
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

## `apiV1PublisherSmartzonesNetworksPost()`

```php
apiV1PublisherSmartzonesNetworksPost($api_v1_publisher_smartzones_networks_post_request)
```

Add or update a smart network account.

Without `id` (or `id: 0`) creates an account; with the `id` of one of yours updates it (every field is rewritten — send the full object). `name` + `prepend_name` must be unique among your accounts. Empty credential pairs are dropped from `api_data`, and a set `apikey` removes `username`/`userpass`.  Unknown body fields are refused with `422 unknown_fields`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SmartZonesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_publisher_smartzones_networks_post_request = {"name":"ExoClick main","networkid":3,"currency":"USD","is_active":1,"pullstats":1,"apikey":"xxxxxxxx","api_data":{"userid":"998877"}}; // \GoAdServerApi\Model\ApiV1PublisherSmartzonesNetworksPostRequest

try {
    $apiInstance->apiV1PublisherSmartzonesNetworksPost($api_v1_publisher_smartzones_networks_post_request);
} catch (Exception $e) {
    echo 'Exception when calling SmartZonesApi->apiV1PublisherSmartzonesNetworksPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_publisher_smartzones_networks_post_request** | [**\GoAdServerApi\Model\ApiV1PublisherSmartzonesNetworksPostRequest**](../Model/ApiV1PublisherSmartzonesNetworksPostRequest.md)|  | |

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

## `apiV1PublisherSmartzonesPost()`

```php
apiV1PublisherSmartzonesPost($api_v1_publisher_smartzones_post_request)
```

Create a smart zone.

Creates a smart zone under one of your live ad zones. The zone's `siteid` is always taken from the ad zone (a `siteid` in the body is ignored). With no network (`smart_networkid` 0 or omitted) the selection type is forced to percentage, because eCPM and RTB need an upstream network to rank against.  Unknown body fields are refused with `422 unknown_fields`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SmartZonesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_publisher_smartzones_post_request = {"name":"Exo backfill — DE mobile","spaceid":4471,"smart_networkid":14,"countries":"DE,AT","device_type_ids":"3","is_active":true,"select_type":1,"select_pct":30,"creative":"{\"creative\":{\"type\":\"url\",\"value\":\"https://a.network.example/zone/12345\"}}","settings":"{\"skip_when_stats\":1}","network_zoneid":12345}; // \GoAdServerApi\Model\ApiV1PublisherSmartzonesPostRequest

try {
    $apiInstance->apiV1PublisherSmartzonesPost($api_v1_publisher_smartzones_post_request);
} catch (Exception $e) {
    echo 'Exception when calling SmartZonesApi->apiV1PublisherSmartzonesPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_publisher_smartzones_post_request** | [**\GoAdServerApi\Model\ApiV1PublisherSmartzonesPostRequest**](../Model/ApiV1PublisherSmartzonesPostRequest.md)|  | |

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

## `apiV1PublisherSmartzonesSetupDataGet()`

```php
apiV1PublisherSmartzonesSetupDataGet()
```

Lookups for the smart-zone form.

Everything the create/update body refers to: countries, device types (empty when device targeting is off for the tenant), your websites and live ad zones, the ad zone types you use, and the network catalogue (`smartzone_networks`) with the per-network flags that say which credentials and ids that network needs.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SmartZonesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1PublisherSmartzonesSetupDataGet();
} catch (Exception $e) {
    echo 'Exception when calling SmartZonesApi->apiV1PublisherSmartzonesSetupDataGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1PublisherSmartzonesSspCampaignsGet()`

```php
apiV1PublisherSmartzonesSspCampaignsGet($networkid, $adtypeid)
```

SSP campaigns usable as a smart zone's RTB feed.

Your active RTB-feed SSP campaigns (`ssptype = ssp`, `use_type = 2`) on the RTB network that matches the catalogue network `networkid`, optionally narrowed to one ad type. Empty when the network has no RTB twin.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SmartZonesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$networkid = 56; // int | Catalogue network id (setup-data `smartzone_networks[].id`).
$adtypeid = 56; // int | Ad type id of the parent ad zone.

try {
    $apiInstance->apiV1PublisherSmartzonesSspCampaignsGet($networkid, $adtypeid);
} catch (Exception $e) {
    echo 'Exception when calling SmartZonesApi->apiV1PublisherSmartzonesSspCampaignsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **networkid** | **int**| Catalogue network id (setup-data &#x60;smartzone_networks[].id&#x60;). | |
| **adtypeid** | **int**| Ad type id of the parent ad zone. | [optional] |

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
