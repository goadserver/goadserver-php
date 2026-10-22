# GoAdServerApi\DirectDealsApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1CampaignsDirectdealIdCancelPut()**](DirectDealsApi.md#apiV1CampaignsDirectdealIdCancelPut) | **PUT** /api/v1/campaigns/directdeal/{id}/cancel | Cancel a direct deal. |
| [**apiV1DirectDealsAdzonesGet()**](DirectDealsApi.md#apiV1DirectDealsAdzonesGet) | **GET** /api/v1/direct-deals/adzones | Ad zones a direct deal can be booked on. |
| [**apiV1DirectDealsGet()**](DirectDealsApi.md#apiV1DirectDealsGet) | **GET** /api/v1/direct-deals | List direct deals with their derived status. |


## `apiV1CampaignsDirectdealIdCancelPut()`

```php
apiV1CampaignsDirectdealIdCancelPut($id)
```

Cancel a direct deal.

The panel's Cancel button: sets `iscancelled = 1`, which stops the deal for good — unlike `PATCH …/active` (pause), it cannot be resumed; book a new deal (or clone this one) instead. The row stays listed with `status: cancelled`. Pinned keys may only cancel a pinned campaign; an own-scoped member only a deal they manage. No body.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\DirectDealsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The direct deal (campaign) id.

try {
    $apiInstance->apiV1CampaignsDirectdealIdCancelPut($id);
} catch (Exception $e) {
    echo 'Exception when calling DirectDealsApi->apiV1CampaignsDirectdealIdCancelPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The direct deal (campaign) id. | |

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

## `apiV1DirectDealsAdzonesGet()`

```php
apiV1DirectDealsAdzonesGet($website_id)
```

Ad zones a direct deal can be booked on.

The account's own, non-deleted publisher ad zones (offer sites excluded) — the picker behind `ad_space_id` on `POST /api/v1/campaigns/directdeal`. A deal takes its ad type from the zone, so there is no ad-type filter. A team member limited to their own ad zones sees only those (directly or via the website), and can only book a deal on them. Same handler as the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\DirectDealsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$website_id = 56; // int | Only zones on this site.

try {
    $apiInstance->apiV1DirectDealsAdzonesGet($website_id);
} catch (Exception $e) {
    echo 'Exception when calling DirectDealsApi->apiV1DirectDealsAdzonesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **website_id** | **int**| Only zones on this site. | [optional] |

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

## `apiV1DirectDealsGet()`

```php
apiV1DirectDealsGet($page, $per_page, $status, $website_ids, $ad_type, $tag_ids, $filter_name, $sort_by, $sort_dir)
```

List direct deals with their derived status.

The panel's Direct Deals listing (`flatratecampaigns` rows with `isdirectdeal = 1`, deleted rows excluded), newest first. Unlike `GET /api/v1/campaigns?type=directdeal` each row carries the derived `status` and the booked zone / site names. Same handler as the panel.  `status` is derived in this order: `cancelled` (cancel button) → `completed` → `paused` → `active` (state 1) → `pending` (state 0) → `rejected` (state -1) → `ended` (state 2).  `totalbudget` is in the account's currency units. Account-wide, so a key pinned to campaigns gets 403 `forbidden_resource` — use `GET /api/v1/campaigns?type=directdeal` (pin-filtered) instead. For a team member whose campaign access is limited to their own campaigns, only the deals they manage (created, assigned, or filed under a campaign group they manage) are listed and counted.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\DirectDealsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$page = 1; // int
$per_page = 20; // int
$status = 'status_example'; // string | CSV of active, paused, pending, completed, ended, cancelled, rejected.
$website_ids = 'website_ids_example'; // string | CSV of site ids (the booked zone's site).
$ad_type = 'ad_type_example'; // string | CSV of ad type ids.
$tag_ids = 'tag_ids_example'; // string | CSV of tag ids; a folder tag also matches its sub-tags.
$filter_name = 'filter_name_example'; // string | Name substring.
$sort_by = 'id'; // string
$sort_dir = 'desc'; // string

try {
    $apiInstance->apiV1DirectDealsGet($page, $per_page, $status, $website_ids, $ad_type, $tag_ids, $filter_name, $sort_by, $sort_dir);
} catch (Exception $e) {
    echo 'Exception when calling DirectDealsApi->apiV1DirectDealsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 20] |
| **status** | **string**| CSV of active, paused, pending, completed, ended, cancelled, rejected. | [optional] |
| **website_ids** | **string**| CSV of site ids (the booked zone&#39;s site). | [optional] |
| **ad_type** | **string**| CSV of ad type ids. | [optional] |
| **tag_ids** | **string**| CSV of tag ids; a folder tag also matches its sub-tags. | [optional] |
| **filter_name** | **string**| Name substring. | [optional] |
| **sort_by** | **string**|  | [optional] [default to &#39;id&#39;] |
| **sort_dir** | **string**|  | [optional] [default to &#39;desc&#39;] |

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
