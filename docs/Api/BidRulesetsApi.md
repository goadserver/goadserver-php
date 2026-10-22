# GoAdServerApi\BidRulesetsApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1BidRulesetsGet()**](BidRulesetsApi.md#apiV1BidRulesetsGet) | **GET** /api/v1/bid-rulesets | List the caller&#39;s bid rulesets (lightweight rows). |
| [**apiV1BidRulesetsIdClonePost()**](BidRulesetsApi.md#apiV1BidRulesetsIdClonePost) | **POST** /api/v1/bid-rulesets/{id}/clone | Clone a ruleset with all its rules. |
| [**apiV1BidRulesetsIdDelete()**](BidRulesetsApi.md#apiV1BidRulesetsIdDelete) | **DELETE** /api/v1/bid-rulesets/{id} | Soft-delete a ruleset. |
| [**apiV1BidRulesetsIdGet()**](BidRulesetsApi.md#apiV1BidRulesetsIdGet) | **GET** /api/v1/bid-rulesets/{id} | Show one ruleset, including all rules. |
| [**apiV1BidRulesetsIdPatch()**](BidRulesetsApi.md#apiV1BidRulesetsIdPatch) | **PATCH** /api/v1/bid-rulesets/{id} | Update a ruleset&#39;s metadata and/or replace its rules. |
| [**apiV1BidRulesetsPost()**](BidRulesetsApi.md#apiV1BidRulesetsPost) | **POST** /api/v1/bid-rulesets | Create a new bid ruleset (with rules in one shot). |


## `apiV1BidRulesetsGet()`

```php
apiV1BidRulesetsGet($paytype, $include_deleted): \GoAdServerApi\Model\ApiV1BidRulesetsGet200Response
```

List the caller's bid rulesets (lightweight rows).

Returns every non-deleted ruleset of the authenticated user, optionally filtered by paytype. Not paginated: `page` is always 1 and `per_page` / `total_rows` equal the number of rows returned (users typically hold fewer than 50 rulesets). Each row carries identity + summary counts (`rule_count`, `campaign_count`) but **not** the rule rows themselves — fetch the full record via `GET /api/v1/bid-rulesets/{id}` when you need the conditions JSON for editing.  Use this endpoint to populate ruleset-picker UIs (e.g. the campaign-edit \"Use bid rules\" picker) and library views.  Default sort is `updated DESC`. Soft-deleted rulesets are excluded.  Reference: [`docs/BID_RULES.md`](BID_RULES.md).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\BidRulesetsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$paytype = 'paytype_example'; // string | Filter to rulesets of one paytype.
$include_deleted = false; // bool | Also return soft-deleted rulesets (`is_deleted: true`).

try {
    $result = $apiInstance->apiV1BidRulesetsGet($paytype, $include_deleted);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling BidRulesetsApi->apiV1BidRulesetsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **paytype** | **string**| Filter to rulesets of one paytype. | [optional] |
| **include_deleted** | **bool**| Also return soft-deleted rulesets (&#x60;is_deleted: true&#x60;). | [optional] [default to false] |

### Return type

[**\GoAdServerApi\Model\ApiV1BidRulesetsGet200Response**](../Model/ApiV1BidRulesetsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1BidRulesetsIdClonePost()`

```php
apiV1BidRulesetsIdClonePost($id, $api_v1_bid_rulesets_id_clone_post_request): \GoAdServerApi\Model\BidRuleset
```

Clone a ruleset with all its rules.

Duplicates the ruleset row and every non-deleted rule on it. New ruleset name defaults to `<original> (copy)` — override via the body. Same `paytype` and `default_tz` as the source. Ad / campaign attachments are **not** copied — the clone starts unattached (`campaign_count: 0`).  Use this to fork a working ruleset for an experiment without affecting the live campaigns that reference the original.  Reference: [`docs/BID_RULES.md`](BID_RULES.md).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\BidRulesetsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_bid_rulesets_id_clone_post_request = new \GoAdServerApi\Model\ApiV1BidRulesetsIdClonePostRequest(); // \GoAdServerApi\Model\ApiV1BidRulesetsIdClonePostRequest

try {
    $result = $apiInstance->apiV1BidRulesetsIdClonePost($id, $api_v1_bid_rulesets_id_clone_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling BidRulesetsApi->apiV1BidRulesetsIdClonePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_bid_rulesets_id_clone_post_request** | [**\GoAdServerApi\Model\ApiV1BidRulesetsIdClonePostRequest**](../Model/ApiV1BidRulesetsIdClonePostRequest.md)|  | [optional] |

### Return type

[**\GoAdServerApi\Model\BidRuleset**](../Model/BidRuleset.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1BidRulesetsIdDelete()`

```php
apiV1BidRulesetsIdDelete($id): \GoAdServerApi\Model\ApiV1BidRulesetsIdDelete200Response
```

Soft-delete a ruleset.

Sets `isdeleted=1` on the ruleset (rule rows stay in place for audit; goadserver simply stops loading them on next refresh). Idempotent.  **Hard-blocked while attached to a non-deleted campaign** — the response is a 422 whose message gives the number of attached campaigns (`ruleset is attached to N active campaign(s); detach first`). Find them with `GET /api/v1/campaigns` and detach each one (set `bidruleset_id=0` via `PATCH /api/v1/campaigns/{type}/{id}`) first. The list endpoint's `campaign_count` flags this ahead of time.  Reference: [`docs/BID_RULES.md`](BID_RULES.md).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\BidRulesetsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $result = $apiInstance->apiV1BidRulesetsIdDelete($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling BidRulesetsApi->apiV1BidRulesetsIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1BidRulesetsIdDelete200Response**](../Model/ApiV1BidRulesetsIdDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1BidRulesetsIdGet()`

```php
apiV1BidRulesetsIdGet($id): \GoAdServerApi\Model\BidRuleset
```

Show one ruleset, including all rules.

Returns the full ruleset record with the rule rows in priority order — this is what an editor UI loads to render the rule list and condition pickers.  Reference: [`docs/BID_RULES.md`](BID_RULES.md).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\BidRulesetsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $result = $apiInstance->apiV1BidRulesetsIdGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling BidRulesetsApi->apiV1BidRulesetsIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\BidRuleset**](../Model/BidRuleset.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1BidRulesetsIdPatch()`

```php
apiV1BidRulesetsIdPatch($id, $api_v1_bid_rulesets_id_patch_request): \GoAdServerApi\Model\BidRuleset
```

Update a ruleset's metadata and/or replace its rules.

Fields you leave out keep their stored value:  - `name`, `paytype`, `default_tz` omitted → unchanged. - `rules` omitted → the current rules are kept. - `rules` present → the array **replaces** the whole rule list   in one transaction; `rules: []` clears it.  Every save rewrites the rule rows: the previous rows are soft-deleted and the resulting list is inserted with **new** rule ids (stats already attributed to an old rule id stay on the old row). Rule `id`s in the body are ignored.  `paytype` can be changed, as in the panel editor — make sure the attached campaigns use the same paytype.  You can send back a record fetched with `GET` unchanged: the read-only fields (`id`, `userid`, `is_deleted`, `created`, `updated`, `campaign_count`, `rule_count`) are accepted and ignored, also in strict mode.  Reorder by changing `priority` values — rules walk in ascending priority at serve time.  Editing a ruleset that's attached to multiple campaigns affects all of them — no per-campaign override exists. The list endpoint surfaces `campaign_count` so a UI can warn before save.  Reference: [`docs/BID_RULES.md`](BID_RULES.md).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\BidRulesetsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_bid_rulesets_id_patch_request = {"name":"RTB CPC playbook v2"}; // \GoAdServerApi\Model\ApiV1BidRulesetsIdPatchRequest

try {
    $result = $apiInstance->apiV1BidRulesetsIdPatch($id, $api_v1_bid_rulesets_id_patch_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling BidRulesetsApi->apiV1BidRulesetsIdPatch: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_bid_rulesets_id_patch_request** | [**\GoAdServerApi\Model\ApiV1BidRulesetsIdPatchRequest**](../Model/ApiV1BidRulesetsIdPatchRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\BidRuleset**](../Model/BidRuleset.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1BidRulesetsPost()`

```php
apiV1BidRulesetsPost($api_v1_bid_rulesets_post_request): \GoAdServerApi\Model\BidRuleset
```

Create a new bid ruleset (with rules in one shot).

Creates a ruleset and the full rule list in a single transaction. Rules without an `id` are inserted; the supplied `priority` is honored — sort the array client-side or just set `priority` to the desired walk order.  `paytype` must match the paytype of the campaigns you attach the ruleset to. `default_tz` defaults to `UTC` when omitted. Rule `bid` values are whole cents.  Reference: [`docs/BID_RULES.md`](BID_RULES.md).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\BidRulesetsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_bid_rulesets_post_request = {"name":"RTB CPC playbook","paytype":"cpc","default_tz":"UTC"}; // \GoAdServerApi\Model\ApiV1BidRulesetsPostRequest

try {
    $result = $apiInstance->apiV1BidRulesetsPost($api_v1_bid_rulesets_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling BidRulesetsApi->apiV1BidRulesetsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_bid_rulesets_post_request** | [**\GoAdServerApi\Model\ApiV1BidRulesetsPostRequest**](../Model/ApiV1BidRulesetsPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\BidRuleset**](../Model/BidRuleset.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
