# GoAdServerApi\CleanupApi

What no longer serves — idle campaigns, ads, creatives, pools, adzones and websites with last-served date, lifetime totals and a suggested next step. Read-only. To apply the suggestions (what the panel&#39;s \&quot;Clean up suggested\&quot; button does), read each kind with GET /cleanup/{side}?kind&#x3D;… and send the rows to the kind&#39;s own write route: suggestion pause → PUT /campaigns/mass-toggle (pause true) or PUT /campaigns/{type}/{campaignId}/ads/mass-toggle (activate false); suggestion archive → POST /campaigns/mass-delete, POST /campaigns/{type}/{campaignId}/ads/mass-delete, POST /creatives/bulk-delete, DELETE /creative-pools/{id}, DELETE /url-pools/{id}, POST /adspaces/mass-delete, DELETE /sites/{id}. A network can switch Cleanup off for its users; every /cleanup route then answers 403. Leave review and keep rows alone. Ads before campaigns, adspaces before sites; at most 200 ids per bulk call.

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1CleanupAdvertiserArchivedGet()**](CleanupApi.md#apiV1CleanupAdvertiserArchivedGet) | **GET** /api/v1/cleanup/advertiser/archived | Archived advertiser items that can be purged. |
| [**apiV1CleanupAdvertiserExportGet()**](CleanupApi.md#apiV1CleanupAdvertiserExportGet) | **GET** /api/v1/cleanup/advertiser/export | Download the advertiser cleanup candidates. |
| [**apiV1CleanupAdvertiserGet()**](CleanupApi.md#apiV1CleanupAdvertiserGet) | **GET** /api/v1/cleanup/advertiser | List advertiser items that no longer serve. |
| [**apiV1CleanupAdvertiserLinkedGet()**](CleanupApi.md#apiV1CleanupAdvertiserLinkedGet) | **GET** /api/v1/cleanup/advertiser/linked | What still uses advertiser cleanup rows. |
| [**apiV1CleanupAdvertiserPurgePost()**](CleanupApi.md#apiV1CleanupAdvertiserPurgePost) | **POST** /api/v1/cleanup/advertiser/purge | Purge archived advertiser items for good. |
| [**apiV1CleanupAdvertiserRunsGet()**](CleanupApi.md#apiV1CleanupAdvertiserRunsGet) | **GET** /api/v1/cleanup/advertiser/runs | Recent advertiser cleanup runs. |
| [**apiV1CleanupAdvertiserRunsIdGet()**](CleanupApi.md#apiV1CleanupAdvertiserRunsIdGet) | **GET** /api/v1/cleanup/advertiser/runs/{id} | One advertiser cleanup run with its items. |
| [**apiV1CleanupAdvertiserRunsIdUndonePost()**](CleanupApi.md#apiV1CleanupAdvertiserRunsIdUndonePost) | **POST** /api/v1/cleanup/advertiser/runs/{id}/undone | Mark a advertiser cleanup run as undone. |
| [**apiV1CleanupAdvertiserRunsPost()**](CleanupApi.md#apiV1CleanupAdvertiserRunsPost) | **POST** /api/v1/cleanup/advertiser/runs | Record a advertiser cleanup run. |
| [**apiV1CleanupAdvertiserSummaryGet()**](CleanupApi.md#apiV1CleanupAdvertiserSummaryGet) | **GET** /api/v1/cleanup/advertiser/summary | Count the advertiser cleanup candidates per kind. |
| [**apiV1CleanupPublisherArchivedGet()**](CleanupApi.md#apiV1CleanupPublisherArchivedGet) | **GET** /api/v1/cleanup/publisher/archived | Archived publisher items that can be purged. |
| [**apiV1CleanupPublisherExportGet()**](CleanupApi.md#apiV1CleanupPublisherExportGet) | **GET** /api/v1/cleanup/publisher/export | Download the publisher cleanup candidates. |
| [**apiV1CleanupPublisherGet()**](CleanupApi.md#apiV1CleanupPublisherGet) | **GET** /api/v1/cleanup/publisher | List publisher items that no longer serve. |
| [**apiV1CleanupPublisherLinkedGet()**](CleanupApi.md#apiV1CleanupPublisherLinkedGet) | **GET** /api/v1/cleanup/publisher/linked | What still uses publisher cleanup rows. |
| [**apiV1CleanupPublisherPurgePost()**](CleanupApi.md#apiV1CleanupPublisherPurgePost) | **POST** /api/v1/cleanup/publisher/purge | Purge archived publisher items for good. |
| [**apiV1CleanupPublisherRunsGet()**](CleanupApi.md#apiV1CleanupPublisherRunsGet) | **GET** /api/v1/cleanup/publisher/runs | Recent publisher cleanup runs. |
| [**apiV1CleanupPublisherRunsIdGet()**](CleanupApi.md#apiV1CleanupPublisherRunsIdGet) | **GET** /api/v1/cleanup/publisher/runs/{id} | One publisher cleanup run with its items. |
| [**apiV1CleanupPublisherRunsIdUndonePost()**](CleanupApi.md#apiV1CleanupPublisherRunsIdUndonePost) | **POST** /api/v1/cleanup/publisher/runs/{id}/undone | Mark a publisher cleanup run as undone. |
| [**apiV1CleanupPublisherRunsPost()**](CleanupApi.md#apiV1CleanupPublisherRunsPost) | **POST** /api/v1/cleanup/publisher/runs | Record a publisher cleanup run. |
| [**apiV1CleanupPublisherSummaryGet()**](CleanupApi.md#apiV1CleanupPublisherSummaryGet) | **GET** /api/v1/cleanup/publisher/summary | Count the publisher cleanup candidates per kind. |


## `apiV1CleanupAdvertiserArchivedGet()`

```php
apiV1CleanupAdvertiserArchivedGet()
```

Archived advertiser items that can be purged.

Per kind the caller may read, how many archived (deleted) items are still restorable, i.e. not purged.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1CleanupAdvertiserArchivedGet();
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupAdvertiserArchivedGet: ', $e->getMessage(), PHP_EOL;
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
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CleanupAdvertiserExportGet()`

```php
apiV1CleanupAdvertiserExportGet($format, $kinds, $inactive_days, $never_served)
```

Download the advertiser cleanup candidates.

Every kind the caller may read (or the comma-separated `kinds`) with the same filter, as one xlsx workbook with a sheet per kind (default) or one csv where each kind starts with its own header row.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$format = 'xlsx'; // string
$kinds = 'kinds_example'; // string | Comma-separated subset of: campaign,ad,creative,creative_pool,url_pool.
$inactive_days = 90; // int
$never_served = false; // bool

try {
    $apiInstance->apiV1CleanupAdvertiserExportGet($format, $kinds, $inactive_days, $never_served);
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupAdvertiserExportGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **format** | **string**|  | [optional] [default to &#39;xlsx&#39;] |
| **kinds** | **string**| Comma-separated subset of: campaign,ad,creative,creative_pool,url_pool. | [optional] |
| **inactive_days** | **int**|  | [optional] [default to 90] |
| **never_served** | **bool**|  | [optional] [default to false] |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`, `text/csv`, `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CleanupAdvertiserGet()`

```php
apiV1CleanupAdvertiserGet($kind, $inactive_days, $never_served, $search, $sort, $dir, $page, $per_page)
```

List advertiser items that no longer serve.

One kind at a time (`campaign`, `ad`, `creative`, `creative_pool`, `url_pool` — campaigns and ads need `campaigns.read`, the rest `creatives.read`): every not-deleted row the account owns that has not served an impression for at least `inactive_days` days (default 90; `0` lists everything), or with `never_served=1` only the rows that never served at all. Each row carries its first / last served date (`\"\"` = never), `days_idle` (`-1` = never served), lifetime impressions / clicks / spend in the account's currency units, and a suggestion:  - `pause` — still switched on but idle (campaigns, ads). - `archive` — off or not switchable and nothing depends on it. - `review` — idle but still used (`used_by` live ads or pool   items for creatives and pools, live adzones for websites). - `keep` — served within the last 90 days; only appears with   `inactive_days=0`, the full inventory.  Read-only: act through the kind's own endpoints (pause a campaign or ad, mass-delete, delete a pool or site), which keep their own checks. History comes from the full stats history (2021 onward) and is cached for five minutes. RTB campaigns are not listed: their delivery is reported separately. Each kind also needs its own scope (campaigns.read, creatives.read); a kind the key or team member cannot read is 403. The panel's Cleanup page uses the same handler.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$kind = 'kind_example'; // string
$inactive_days = 90; // int
$never_served = false; // bool
$search = 'search_example'; // string | Substring of the name.
$sort = 'last_served'; // string | last_served ascending = never served first, then the longest idle.
$dir = 'asc'; // string
$page = 1; // int
$per_page = 100; // int

try {
    $apiInstance->apiV1CleanupAdvertiserGet($kind, $inactive_days, $never_served, $search, $sort, $dir, $page, $per_page);
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupAdvertiserGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **kind** | **string**|  | [optional] |
| **inactive_days** | **int**|  | [optional] [default to 90] |
| **never_served** | **bool**|  | [optional] [default to false] |
| **search** | **string**| Substring of the name. | [optional] |
| **sort** | **string**| last_served ascending &#x3D; never served first, then the longest idle. | [optional] [default to &#39;last_served&#39;] |
| **dir** | **string**|  | [optional] [default to &#39;asc&#39;] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 100] |

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

## `apiV1CleanupAdvertiserLinkedGet()`

```php
apiV1CleanupAdvertiserLinkedGet($kind, $ids)
```

What still uses advertiser cleanup rows.

For rows suggested `review` (still in use): the live ads in live campaigns that use them (creatives, creative pools, URL pools), the campaigns those ads would leave without any live ad, the live pool items holding a creative, and the live ad zones on a website. Read only; the panel's archive dialog archives these first when the user ticks \"archive linked too\" (ads, then emptied campaigns, then ad zones and pool items, then the rows).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$kind = 'kind_example'; // string
$ids = 'ids_example'; // string | Comma-separated row ids, 1 to 5000.

try {
    $apiInstance->apiV1CleanupAdvertiserLinkedGet($kind, $ids);
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupAdvertiserLinkedGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **kind** | **string**|  | |
| **ids** | **string**| Comma-separated row ids, 1 to 5000. | |

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

## `apiV1CleanupAdvertiserPurgePost()`

```php
apiV1CleanupAdvertiserPurgePost()
```

Purge archived advertiser items for good.

Every archived item of the named kinds leaves the Deleted views and can no longer be restored (each kind's recover / undelete route answers 404 for it). The rows stay archived in the database, so past statistics keep their names and serving is not affected. Cannot be undone; logged in GET /runs as a `purge` entry. Body `{kinds: [...], confirm: \"PURGE\"}` (campaign, ad, creative, creative_pool, url_pool).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1CleanupAdvertiserPurgePost();
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupAdvertiserPurgePost: ', $e->getMessage(), PHP_EOL;
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

## `apiV1CleanupAdvertiserRunsGet()`

```php
apiV1CleanupAdvertiserRunsGet()
```

Recent advertiser cleanup runs.

Every pause / archive the Cleanup page (or an integration) recorded in the last 90 days, newest first, at most 50: `id`, `source` (selected | suggested | linked), `time`, `actor_userid`, `undone`, and `counts` keyed `<op>:<kind>`. Item lists come from GET /runs/{id}.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1CleanupAdvertiserRunsGet();
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupAdvertiserRunsGet: ', $e->getMessage(), PHP_EOL;
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
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CleanupAdvertiserRunsIdGet()`

```php
apiV1CleanupAdvertiserRunsIdGet($id)
```

One advertiser cleanup run with its items.

What to call to undo it, action by action, newest action first: pause → resume (PUT /campaigns/mass-toggle pause false, PUT /campaigns/{type}/{campaignId}/ads/mass-toggle activate true); archive → restore (POST /campaigns/mass-undelete, POST /campaigns/{type}/{campaignId}/ads/mass-undelete, POST /creatives/bulk-undelete, POST /adspaces/mass-recover, POST /creative-pools/{id}/recover, POST /url-pools/{id}/recover, POST /sites/{id}/recover); remove_pool_item → POST /creative-pools/{id}/items/recover. Then POST /runs/{id}/undone.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1CleanupAdvertiserRunsIdGet($id);
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupAdvertiserRunsIdGet: ', $e->getMessage(), PHP_EOL;
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
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CleanupAdvertiserRunsIdUndonePost()`

```php
apiV1CleanupAdvertiserRunsIdUndonePost($id)
```

Mark a advertiser cleanup run as undone.

Call after restoring / resuming its items. 409 `already_undone` when it already was.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1CleanupAdvertiserRunsIdUndonePost($id);
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupAdvertiserRunsIdUndonePost: ', $e->getMessage(), PHP_EOL;
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
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CleanupAdvertiserRunsPost()`

```php
apiV1CleanupAdvertiserRunsPost()
```

Record a advertiser cleanup run.

Record what you just changed so it can be listed and undone. Body `{source, actions: [{op, kind, items: [{id, campaign_id, campaign_type, pool_id, was_active}]}]}`; `op` is `pause` (campaigns, ads), `archive` (any kind of this side) or `remove_pool_item` (kind `creative`; `pool_id`, `was_active`). Ads need `campaign_id` + `campaign_type`, campaigns `campaign_type`. At most 100000 items. Recording changes nothing by itself.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1CleanupAdvertiserRunsPost();
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupAdvertiserRunsPost: ', $e->getMessage(), PHP_EOL;
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

## `apiV1CleanupAdvertiserSummaryGet()`

```php
apiV1CleanupAdvertiserSummaryGet($inactive_days, $never_served)
```

Count the advertiser cleanup candidates per kind.

For every kind the caller may read: how many rows match the same `inactive_days` / `never_served` filter, how many never served, and the split by suggestion.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$inactive_days = 90; // int
$never_served = false; // bool

try {
    $apiInstance->apiV1CleanupAdvertiserSummaryGet($inactive_days, $never_served);
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupAdvertiserSummaryGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **inactive_days** | **int**|  | [optional] [default to 90] |
| **never_served** | **bool**|  | [optional] [default to false] |

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

## `apiV1CleanupPublisherArchivedGet()`

```php
apiV1CleanupPublisherArchivedGet()
```

Archived publisher items that can be purged.

Per kind the caller may read, how many archived (deleted) items are still restorable, i.e. not purged.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1CleanupPublisherArchivedGet();
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupPublisherArchivedGet: ', $e->getMessage(), PHP_EOL;
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
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CleanupPublisherExportGet()`

```php
apiV1CleanupPublisherExportGet($format, $kinds, $inactive_days, $never_served)
```

Download the publisher cleanup candidates.

Every kind the caller may read (or the comma-separated `kinds`) with the same filter, as one xlsx workbook with a sheet per kind (default) or one csv where each kind starts with its own header row.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$format = 'xlsx'; // string
$kinds = 'kinds_example'; // string | Comma-separated subset of: adzone,website.
$inactive_days = 90; // int
$never_served = false; // bool

try {
    $apiInstance->apiV1CleanupPublisherExportGet($format, $kinds, $inactive_days, $never_served);
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupPublisherExportGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **format** | **string**|  | [optional] [default to &#39;xlsx&#39;] |
| **kinds** | **string**| Comma-separated subset of: adzone,website. | [optional] |
| **inactive_days** | **int**|  | [optional] [default to 90] |
| **never_served** | **bool**|  | [optional] [default to false] |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`, `text/csv`, `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CleanupPublisherGet()`

```php
apiV1CleanupPublisherGet($kind, $inactive_days, $never_served, $search, $sort, $dir, $page, $per_page)
```

List publisher items that no longer serve.

One kind at a time (`adzone` needs `adspaces.read`, `website` needs `sites.read`): every not-deleted row the account owns that has not served an impression for at least `inactive_days` days (default 90; `0` lists everything), or with `never_served=1` only the rows that never served at all. Each row carries its first / last served date (`\"\"` = never), `days_idle` (`-1` = never served), lifetime impressions / clicks / earnings in the account's currency units, and a suggestion:  - `pause` — still switched on but idle (campaigns, ads). - `archive` — off or not switchable and nothing depends on it. - `review` — idle but still used (`used_by` live ads or pool   items for creatives and pools, live adzones for websites). - `keep` — served within the last 90 days; only appears with   `inactive_days=0`, the full inventory.  Read-only: act through the kind's own endpoints (pause a campaign or ad, mass-delete, delete a pool or site), which keep their own checks. History comes from the full stats history (2021 onward) and is cached for five minutes. RTB campaigns are not listed: their delivery is reported separately. Each kind also needs its own scope (adspaces.read, sites.read); a kind the key or team member cannot read is 403. The panel's Cleanup page uses the same handler.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$kind = 'kind_example'; // string
$inactive_days = 90; // int
$never_served = false; // bool
$search = 'search_example'; // string | Substring of the name.
$sort = 'last_served'; // string | last_served ascending = never served first, then the longest idle.
$dir = 'asc'; // string
$page = 1; // int
$per_page = 100; // int

try {
    $apiInstance->apiV1CleanupPublisherGet($kind, $inactive_days, $never_served, $search, $sort, $dir, $page, $per_page);
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupPublisherGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **kind** | **string**|  | [optional] |
| **inactive_days** | **int**|  | [optional] [default to 90] |
| **never_served** | **bool**|  | [optional] [default to false] |
| **search** | **string**| Substring of the name. | [optional] |
| **sort** | **string**| last_served ascending &#x3D; never served first, then the longest idle. | [optional] [default to &#39;last_served&#39;] |
| **dir** | **string**|  | [optional] [default to &#39;asc&#39;] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 100] |

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

## `apiV1CleanupPublisherLinkedGet()`

```php
apiV1CleanupPublisherLinkedGet($kind, $ids)
```

What still uses publisher cleanup rows.

For rows suggested `review` (still in use): the live ads in live campaigns that use them (creatives, creative pools, URL pools), the campaigns those ads would leave without any live ad, the live pool items holding a creative, and the live ad zones on a website. Read only; the panel's archive dialog archives these first when the user ticks \"archive linked too\" (ads, then emptied campaigns, then ad zones and pool items, then the rows).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$kind = 'kind_example'; // string
$ids = 'ids_example'; // string | Comma-separated row ids, 1 to 5000.

try {
    $apiInstance->apiV1CleanupPublisherLinkedGet($kind, $ids);
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupPublisherLinkedGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **kind** | **string**|  | |
| **ids** | **string**| Comma-separated row ids, 1 to 5000. | |

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

## `apiV1CleanupPublisherPurgePost()`

```php
apiV1CleanupPublisherPurgePost()
```

Purge archived publisher items for good.

Every archived item of the named kinds leaves the Deleted views and can no longer be restored (each kind's recover / undelete route answers 404 for it). The rows stay archived in the database, so past statistics keep their names and serving is not affected. Cannot be undone; logged in GET /runs as a `purge` entry. Body `{kinds: [...], confirm: \"PURGE\"}` (adzone, website).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1CleanupPublisherPurgePost();
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupPublisherPurgePost: ', $e->getMessage(), PHP_EOL;
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

## `apiV1CleanupPublisherRunsGet()`

```php
apiV1CleanupPublisherRunsGet()
```

Recent publisher cleanup runs.

Every pause / archive the Cleanup page (or an integration) recorded in the last 90 days, newest first, at most 50: `id`, `source` (selected | suggested | linked), `time`, `actor_userid`, `undone`, and `counts` keyed `<op>:<kind>`. Item lists come from GET /runs/{id}.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1CleanupPublisherRunsGet();
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupPublisherRunsGet: ', $e->getMessage(), PHP_EOL;
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
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CleanupPublisherRunsIdGet()`

```php
apiV1CleanupPublisherRunsIdGet($id)
```

One publisher cleanup run with its items.

What to call to undo it, action by action, newest action first: pause → resume (PUT /campaigns/mass-toggle pause false, PUT /campaigns/{type}/{campaignId}/ads/mass-toggle activate true); archive → restore (POST /campaigns/mass-undelete, POST /campaigns/{type}/{campaignId}/ads/mass-undelete, POST /creatives/bulk-undelete, POST /adspaces/mass-recover, POST /creative-pools/{id}/recover, POST /url-pools/{id}/recover, POST /sites/{id}/recover); remove_pool_item → POST /creative-pools/{id}/items/recover. Then POST /runs/{id}/undone.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1CleanupPublisherRunsIdGet($id);
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupPublisherRunsIdGet: ', $e->getMessage(), PHP_EOL;
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
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CleanupPublisherRunsIdUndonePost()`

```php
apiV1CleanupPublisherRunsIdUndonePost($id)
```

Mark a publisher cleanup run as undone.

Call after restoring / resuming its items. 409 `already_undone` when it already was.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1CleanupPublisherRunsIdUndonePost($id);
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupPublisherRunsIdUndonePost: ', $e->getMessage(), PHP_EOL;
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
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CleanupPublisherRunsPost()`

```php
apiV1CleanupPublisherRunsPost()
```

Record a publisher cleanup run.

Record what you just changed so it can be listed and undone. Body `{source, actions: [{op, kind, items: [{id, campaign_id, campaign_type, pool_id, was_active}]}]}`; `op` is `pause` (campaigns, ads), `archive` (any kind of this side) or `remove_pool_item` (kind `creative`; `pool_id`, `was_active`). Ads need `campaign_id` + `campaign_type`, campaigns `campaign_type`. At most 100000 items. Recording changes nothing by itself.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1CleanupPublisherRunsPost();
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupPublisherRunsPost: ', $e->getMessage(), PHP_EOL;
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

## `apiV1CleanupPublisherSummaryGet()`

```php
apiV1CleanupPublisherSummaryGet($inactive_days, $never_served)
```

Count the publisher cleanup candidates per kind.

For every kind the caller may read: how many rows match the same `inactive_days` / `never_served` filter, how many never served, and the split by suggestion.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CleanupApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$inactive_days = 90; // int
$never_served = false; // bool

try {
    $apiInstance->apiV1CleanupPublisherSummaryGet($inactive_days, $never_served);
} catch (Exception $e) {
    echo 'Exception when calling CleanupApi->apiV1CleanupPublisherSummaryGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **inactive_days** | **int**|  | [optional] [default to 90] |
| **never_served** | **bool**|  | [optional] [default to false] |

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
