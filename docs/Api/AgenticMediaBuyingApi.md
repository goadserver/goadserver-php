# GoAdServerApi\AgenticMediaBuyingApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost) | **POST** /api/v1/adspaces/{id}/media-buying/actions/{actionId}/decide | Approve or reject a proposal. |
| [**apiV1AdspacesIdMediaBuyingActionsGet()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingActionsGet) | **GET** /api/v1/adspaces/{id}/media-buying/actions | Activity log: every block, bid, budget and other change, and open proposals. |
| [**apiV1AdspacesIdMediaBuyingAgentRunPost()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingAgentRunPost) | **POST** /api/v1/adspaces/{id}/media-buying/agent/run | Run the buying agent now. |
| [**apiV1AdspacesIdMediaBuyingAgentRunsGet()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingAgentRunsGet) | **GET** /api/v1/adspaces/{id}/media-buying/agent/runs | The buying agent&#39;s recent runs with their summaries and proposals. |
| [**apiV1AdspacesIdMediaBuyingBlockPost()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingBlockPost) | **POST** /api/v1/adspaces/{id}/media-buying/block | Block or unblock one source zone at the network. |
| [**apiV1AdspacesIdMediaBuyingCompareGet()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingCompareGet) | **GET** /api/v1/adspaces/{id}/media-buying/compare | Compare with another buy (A/B, e.g. against a control left to the network&#39;s own optimiser). |
| [**apiV1AdspacesIdMediaBuyingGet()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingGet) | **GET** /api/v1/adspaces/{id}/media-buying | Overview: the connected network, what it supports, the policy and open proposals. |
| [**apiV1AdspacesIdMediaBuyingIpsGet()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingIpsGet) | **GET** /api/v1/adspaces/{id}/media-buying/ips | The IP preblock list: ranges on the network&#39;s exclusion list and proposals. |
| [**apiV1AdspacesIdMediaBuyingIpsPost()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingIpsPost) | **POST** /api/v1/adspaces/{id}/media-buying/ips | Change the IP preblock list. |
| [**apiV1AdspacesIdMediaBuyingLaunchGet()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingLaunchGet) | **GET** /api/v1/adspaces/{id}/media-buying/launch | What a new campaign can be launched with (unconnected ad zones). |
| [**apiV1AdspacesIdMediaBuyingLaunchPost()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingLaunchPost) | **POST** /api/v1/adspaces/{id}/media-buying/launch | Launch a new campaign at the network for this ad zone. |
| [**apiV1AdspacesIdMediaBuyingLaunchTemplatesGet()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingLaunchTemplatesGet) | **GET** /api/v1/adspaces/{id}/media-buying/launch/templates | Existing campaigns that can be cloned for a launch. |
| [**apiV1AdspacesIdMediaBuyingNetworkBudgetGet()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingNetworkBudgetGet) | **GET** /api/v1/adspaces/{id}/media-buying/network-budget | The campaign&#39;s daily budget at the network. |
| [**apiV1AdspacesIdMediaBuyingPolicyPut()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingPolicyPut) | **PUT** /api/v1/adspaces/{id}/media-buying/policy | Save the buying policy (any subset of its fields). |
| [**apiV1AdspacesIdMediaBuyingPolicySuggestGet()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingPolicySuggestGet) | **GET** /api/v1/adspaces/{id}/media-buying/policy/suggest | Suggested policy from the zone&#39;s own traffic, with a reason per field. |
| [**apiV1AdspacesIdMediaBuyingReportPost()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingReportPost) | **POST** /api/v1/adspaces/{id}/media-buying/report | Send the media buying report now. |
| [**apiV1AdspacesIdMediaBuyingSegmentsGet()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingSegmentsGet) | **GET** /api/v1/adspaces/{id}/media-buying/segments | Cost and revenue per segment: country, OS, hour, day of week, browser, region, carrier, language. |
| [**apiV1AdspacesIdMediaBuyingSourceBidGet()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingSourceBidGet) | **GET** /api/v1/adspaces/{id}/media-buying/source/bid | The campaign&#39;s current bid at the network. |
| [**apiV1AdspacesIdMediaBuyingSourceBidPut()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingSourceBidPut) | **PUT** /api/v1/adspaces/{id}/media-buying/source/bid | Set the campaign&#39;s bid at the network. |
| [**apiV1AdspacesIdMediaBuyingSourceRetryPost()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingSourceRetryPost) | **POST** /api/v1/adspaces/{id}/media-buying/source/retry | Retry the connection to the source campaign. |
| [**apiV1AdspacesIdMediaBuyingTargetingGet()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingTargetingGet) | **GET** /api/v1/adspaces/{id}/media-buying/targeting | The campaign&#39;s placement targeting (all, or an allow-list). |
| [**apiV1AdspacesIdMediaBuyingTargetingPut()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingTargetingPut) | **PUT** /api/v1/adspaces/{id}/media-buying/targeting | Switch to / edit the allow-list, or back to all placements. |
| [**apiV1AdspacesIdMediaBuyingTargetingSuggestGet()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingTargetingSuggestGet) | **GET** /api/v1/adspaces/{id}/media-buying/targeting/suggest | Suggested allow-list: the profitable placements of the policy window. |
| [**apiV1AdspacesIdMediaBuyingTrackRecordGet()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingTrackRecordGet) | **GET** /api/v1/adspaces/{id}/media-buying/track-record | Track record: did each change do what it was meant to? |
| [**apiV1AdspacesIdMediaBuyingZonesGet()**](AgenticMediaBuyingApi.md#apiV1AdspacesIdMediaBuyingZonesGet) | **GET** /api/v1/adspaces/{id}/media-buying/zones | Source-zone report: revenue against cost per placement. |
| [**apiV1MediaBuyingPortfolioGet()**](AgenticMediaBuyingApi.md#apiV1MediaBuyingPortfolioGet) | **GET** /api/v1/media-buying/portfolio | Every buy side by side: margin against target, spend, proposals, the agent&#39;s latest summary. |
| [**apiV1TrackerMediaBuyingPortfolioGet()**](AgenticMediaBuyingApi.md#apiV1TrackerMediaBuyingPortfolioGet) | **GET** /api/v1/tracker/media-buying/portfolio | Every buy side by side (tracker accounts — the same overview): margin against target, spend, proposals, the agent&#39;s latest summary. |
| [**apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost) | **POST** /api/v1/tracker/tracking-links/{id}/media-buying/actions/{actionId}/decide | Approve or reject a proposal. |
| [**apiV1TrackerTrackingLinksIdMediaBuyingActionsGet()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingActionsGet) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/actions | Activity log: every block, bid, budget and other change, and open proposals. |
| [**apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost) | **POST** /api/v1/tracker/tracking-links/{id}/media-buying/agent/run | Run the buying agent now. |
| [**apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/agent/runs | The buying agent&#39;s recent runs with their summaries and proposals. |
| [**apiV1TrackerTrackingLinksIdMediaBuyingBlockPost()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingBlockPost) | **POST** /api/v1/tracker/tracking-links/{id}/media-buying/block | Block or unblock one source zone at the network. |
| [**apiV1TrackerTrackingLinksIdMediaBuyingCompareGet()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingCompareGet) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/compare | Compare with another buy (A/B, e.g. against a control left to the network&#39;s own optimiser). |
| [**apiV1TrackerTrackingLinksIdMediaBuyingGet()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingGet) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying | Overview: the connected network, what it supports, the policy and open proposals. |
| [**apiV1TrackerTrackingLinksIdMediaBuyingIpsGet()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingIpsGet) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/ips | The IP preblock list: ranges on the network&#39;s exclusion list and proposals. |
| [**apiV1TrackerTrackingLinksIdMediaBuyingIpsPost()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingIpsPost) | **POST** /api/v1/tracker/tracking-links/{id}/media-buying/ips | Change the IP preblock list. |
| [**apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/network-budget | The campaign&#39;s daily budget at the network. |
| [**apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut) | **PUT** /api/v1/tracker/tracking-links/{id}/media-buying/policy | Save the buying policy (any subset of its fields). |
| [**apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/policy/suggest | Suggested policy from the zone&#39;s own traffic, with a reason per field. |
| [**apiV1TrackerTrackingLinksIdMediaBuyingReportPost()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingReportPost) | **POST** /api/v1/tracker/tracking-links/{id}/media-buying/report | Send the media buying report now. |
| [**apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/segments | Cost and revenue per segment: country, OS, hour, day of week, browser, region, carrier, language. |
| [**apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/source/bid | The campaign&#39;s current bid at the network. |
| [**apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut) | **PUT** /api/v1/tracker/tracking-links/{id}/media-buying/source/bid | Set the campaign&#39;s bid at the network. |
| [**apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost) | **POST** /api/v1/tracker/tracking-links/{id}/media-buying/source/retry | Retry the connection to the source campaign. |
| [**apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/targeting | The campaign&#39;s placement targeting (all, or an allow-list). |
| [**apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut) | **PUT** /api/v1/tracker/tracking-links/{id}/media-buying/targeting | Switch to / edit the allow-list, or back to all placements. |
| [**apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/targeting/suggest | Suggested allow-list: the profitable placements of the policy window. |
| [**apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/track-record | Track record: did each change do what it was meant to? |
| [**apiV1TrackerTrackingLinksIdMediaBuyingZonesGet()**](AgenticMediaBuyingApi.md#apiV1TrackerTrackingLinksIdMediaBuyingZonesGet) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/zones | Source-zone report: revenue against cost per placement. |


## `apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost()`

```php
apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost($id, $action_id, $api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request)
```

Approve or reject a proposal.

Approving carries the proposal out at the network (a block, a bid, a budget, a segment exclusion). Approving an `expand_country` proposal (ad zones only) duplicates the ad zone — linked campaigns, own ads, settings, buying policy — and launches its own network campaign for that one country at the proposed bid and test budget, with the source zone's quality blocks as its blacklist; answers `{id, action, status, zone_id, new_spaceid, launch}`; a failure removes the duplicate again (422).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.
$action_id = 56; // int | The proposal id from the activity log.
$api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request = new \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingActionsActionIdDecidePostRequest(); // \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingActionsActionIdDecidePostRequest

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost($id, $action_id, $api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |
| **action_id** | **int**| The proposal id from the activity log. | |
| **api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request** | [**\GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingActionsActionIdDecidePostRequest**](../Model/ApiV1AdspacesIdMediaBuyingActionsActionIdDecidePostRequest.md)|  | |

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

## `apiV1AdspacesIdMediaBuyingActionsGet()`

```php
apiV1AdspacesIdMediaBuyingActionsGet($id, $status, $actor, $action, $zone, $from, $to, $limit, $format)
```

Activity log: every block, bid, budget and other change, and open proposals.

Newest first. A `bid_change` on a network with a bid per country carries `metrics.countries` [{country, pct, old, new, margin, cost, revenue}].

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.
$status = 'status_example'; // string | `proposed` | `applied` | `rejected` | `failed` | `expired`.
$actor = 'actor_example'; // string | `policy` | `agent` | `advertiser` | `manual` | `inherited`.
$action = 'action_example'; // string | e.g. `block`, `unblock`, `zone_bid`, `bid_change`, `net_budget`, `exclude_segment`, `expand_country`, `ip_block`, `report`.
$zone = 'zone_example'; // string | One source zone id.
$from = 'from_example'; // string | YYYY-MM-DD.
$to = 'to_example'; // string | YYYY-MM-DD.
$limit = 56; // int | 1-1000, default 200.
$format = 'format_example'; // string | `csv` for a CSV download (up to 5000 rows).

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingActionsGet($id, $status, $actor, $action, $zone, $from, $to, $limit, $format);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingActionsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |
| **status** | **string**| &#x60;proposed&#x60; | &#x60;applied&#x60; | &#x60;rejected&#x60; | &#x60;failed&#x60; | &#x60;expired&#x60;. | [optional] |
| **actor** | **string**| &#x60;policy&#x60; | &#x60;agent&#x60; | &#x60;advertiser&#x60; | &#x60;manual&#x60; | &#x60;inherited&#x60;. | [optional] |
| **action** | **string**| e.g. &#x60;block&#x60;, &#x60;unblock&#x60;, &#x60;zone_bid&#x60;, &#x60;bid_change&#x60;, &#x60;net_budget&#x60;, &#x60;exclude_segment&#x60;, &#x60;expand_country&#x60;, &#x60;ip_block&#x60;, &#x60;report&#x60;. | [optional] |
| **zone** | **string**| One source zone id. | [optional] |
| **from** | **string**| YYYY-MM-DD. | [optional] |
| **to** | **string**| YYYY-MM-DD. | [optional] |
| **limit** | **int**| 1-1000, default 200. | [optional] |
| **format** | **string**| &#x60;csv&#x60; for a CSV download (up to 5000 rows). | [optional] |

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

## `apiV1AdspacesIdMediaBuyingAgentRunPost()`

```php
apiV1AdspacesIdMediaBuyingAgentRunPost($id, $body)
```

Run the buying agent now.

At most once per 10 minutes per zone (429 `too_soon`). Refused while the zone is switched off. Returns the new run.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.
$body = array('key' => new \stdClass); // object

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingAgentRunPost($id, $body);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingAgentRunPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |
| **body** | **object**|  | [optional] |

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

## `apiV1AdspacesIdMediaBuyingAgentRunsGet()`

```php
apiV1AdspacesIdMediaBuyingAgentRunsGet($id)
```

The buying agent's recent runs with their summaries and proposals.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingAgentRunsGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingAgentRunsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |

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

## `apiV1AdspacesIdMediaBuyingBlockPost()`

```php
apiV1AdspacesIdMediaBuyingBlockPost($id, $api_v1_adspaces_id_media_buying_block_post_request)
```

Block or unblock one source zone at the network.

Writes the change to the network campaign right away and logs it as a manual action. 502 when the network refuses.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.
$api_v1_adspaces_id_media_buying_block_post_request = new \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingBlockPostRequest(); // \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingBlockPostRequest

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingBlockPost($id, $api_v1_adspaces_id_media_buying_block_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingBlockPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |
| **api_v1_adspaces_id_media_buying_block_post_request** | [**\GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingBlockPostRequest**](../Model/ApiV1AdspacesIdMediaBuyingBlockPostRequest.md)|  | |

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

## `apiV1AdspacesIdMediaBuyingCompareGet()`

```php
apiV1AdspacesIdMediaBuyingCompareGet($id, $with, $days)
```

Compare with another buy (A/B, e.g. against a control left to the network's own optimiser).

This buy against another of yours over the same days (default: its control — policy `control_for` — or the buy it is the control of). Per buy: revenue, network cost, profit, margin, profit and volume per day, and each day's figures. Verdict: a paired test on the daily margins — `a_better` / `b_better` (a = this buy) only when the mean difference is at least twice its standard error over at least 5 days with revenue on both sides, else `too_close` or `too_little_data`; `note` says it in a sentence. Database only, no network call.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.
$with = 56; // int | The other buy (ad zone or tracking link id); default its control.
$days = 14; // int | Full days, ending yesterday.

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingCompareGet($id, $with, $days);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingCompareGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |
| **with** | **int**| The other buy (ad zone or tracking link id); default its control. | [optional] |
| **days** | **int**| Full days, ending yesterday. | [optional] [default to 14] |

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

## `apiV1AdspacesIdMediaBuyingGet()`

```php
apiV1AdspacesIdMediaBuyingGet($id)
```

Overview: the connected network, what it supports, the policy and open proposals.

Everything the panel shows before the report: network and campaign, capability flags (`supports_*`; `supports_country_bids` = the campaign bid is an absolute bid per country, judged and moved per country), the URL suffix that captures the source zone id (`capture`), the stored `policy`, `pending_proposals`, the source campaign state and `owner_gets_report`. 404 when the ad space is not connected to an external network campaign.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |

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

## `apiV1AdspacesIdMediaBuyingIpsGet()`

```php
apiV1AdspacesIdMediaBuyingIpsGet($id)
```

The IP preblock list: ranges on the network's exclusion list and proposals.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingIpsGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingIpsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |

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

## `apiV1AdspacesIdMediaBuyingIpsPost()`

```php
apiV1AdspacesIdMediaBuyingIpsPost($id, $api_v1_adspaces_id_media_buying_ips_post_request)
```

Change the IP preblock list.

`add` blocks the given ranges now, `approve` / `reject` decide proposals, `remove` takes ranges off, `refresh` builds the list now (no `cidrs`).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.
$api_v1_adspaces_id_media_buying_ips_post_request = new \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingIpsPostRequest(); // \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingIpsPostRequest

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingIpsPost($id, $api_v1_adspaces_id_media_buying_ips_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingIpsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |
| **api_v1_adspaces_id_media_buying_ips_post_request** | [**\GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingIpsPostRequest**](../Model/ApiV1AdspacesIdMediaBuyingIpsPostRequest.md)|  | |

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

## `apiV1AdspacesIdMediaBuyingLaunchGet()`

```php
apiV1AdspacesIdMediaBuyingLaunchGet($id, $adnetwork_id)
```

What a new campaign can be launched with (unconnected ad zones).

Without `adnetwork_id`: the network accounts that can launch. With it: that network's launch options (formats, countries, minimum bids).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.
$adnetwork_id = 56; // int | One of the owner's network accounts.

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingLaunchGet($id, $adnetwork_id);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingLaunchGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |
| **adnetwork_id** | **int**| One of the owner&#39;s network accounts. | [optional] |

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

## `apiV1AdspacesIdMediaBuyingLaunchPost()`

```php
apiV1AdspacesIdMediaBuyingLaunchPost($id, $api_v1_adspaces_id_media_buying_launch_post_request)
```

Launch a new campaign at the network for this ad zone.

Files the create request; once the network approves it the campaign is connected to the zone (auto pause/activate on).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.
$api_v1_adspaces_id_media_buying_launch_post_request = new \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingLaunchPostRequest(); // \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingLaunchPostRequest

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingLaunchPost($id, $api_v1_adspaces_id_media_buying_launch_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingLaunchPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |
| **api_v1_adspaces_id_media_buying_launch_post_request** | [**\GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingLaunchPostRequest**](../Model/ApiV1AdspacesIdMediaBuyingLaunchPostRequest.md)|  | |

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

## `apiV1AdspacesIdMediaBuyingLaunchTemplatesGet()`

```php
apiV1AdspacesIdMediaBuyingLaunchTemplatesGet($id, $adnetwork_id)
```

Existing campaigns that can be cloned for a launch.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.
$adnetwork_id = 56; // int | The network account.

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingLaunchTemplatesGet($id, $adnetwork_id);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingLaunchTemplatesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |
| **adnetwork_id** | **int**| The network account. | [optional] |

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

## `apiV1AdspacesIdMediaBuyingNetworkBudgetGet()`

```php
apiV1AdspacesIdMediaBuyingNetworkBudgetGet($id)
```

The campaign's daily budget at the network.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingNetworkBudgetGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingNetworkBudgetGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |

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

## `apiV1AdspacesIdMediaBuyingPolicyPut()`

```php
apiV1AdspacesIdMediaBuyingPolicyPut($id, $media_buying_policy)
```

Save the buying policy (any subset of its fields).

Fields left out keep their stored value. Returns the stored, normalized policy.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.
$media_buying_policy = new \GoAdServerApi\Model\MediaBuyingPolicy(); // \GoAdServerApi\Model\MediaBuyingPolicy

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingPolicyPut($id, $media_buying_policy);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingPolicyPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |
| **media_buying_policy** | [**\GoAdServerApi\Model\MediaBuyingPolicy**](../Model/MediaBuyingPolicy.md)|  | |

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

## `apiV1AdspacesIdMediaBuyingPolicySuggestGet()`

```php
apiV1AdspacesIdMediaBuyingPolicySuggestGet($id, $preset)
```

Suggested policy from the zone's own traffic, with a reason per field.

Nothing is saved. `data.policy` is the full suggested policy; `data.suggestions` lists `{field, value, why}`; with `preset` the preset's own changes follow, each with its reason.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.
$preset = 'preset_example'; // string | On top of the suggestion: conservative (every lever proposes, 95% certainty, twice the minimum cost and traffic, 5% bid steps, at most 20 changes a day), balanced (the suggestion as it is) or aggressive (placement blocks, bids per placement and segment exclusions in auto, the agent in auto, 80% certainty, 15% bid steps). No preset touches the network budget, the campaign bid or the IP preblock. Unknown value: 422.

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingPolicySuggestGet($id, $preset);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingPolicySuggestGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |
| **preset** | **string**| On top of the suggestion: conservative (every lever proposes, 95% certainty, twice the minimum cost and traffic, 5% bid steps, at most 20 changes a day), balanced (the suggestion as it is) or aggressive (placement blocks, bids per placement and segment exclusions in auto, the agent in auto, 80% certainty, 15% bid steps). No preset touches the network budget, the campaign bid or the IP preblock. Unknown value: 422. | [optional] |

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

## `apiV1AdspacesIdMediaBuyingReportPost()`

```php
apiV1AdspacesIdMediaBuyingReportPost($id, $api_v1_adspaces_id_media_buying_report_post_request)
```

Send the media buying report now.

The same report the daily / weekly schedule sends: to the admins' notification event and, where the owner runs the buy (admanager, tracking links), the owner's email and the `media_buying.report` event. Returns the report and where it went.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.
$api_v1_adspaces_id_media_buying_report_post_request = new \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingReportPostRequest(); // \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingReportPostRequest

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingReportPost($id, $api_v1_adspaces_id_media_buying_report_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingReportPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |
| **api_v1_adspaces_id_media_buying_report_post_request** | [**\GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingReportPostRequest**](../Model/ApiV1AdspacesIdMediaBuyingReportPostRequest.md)|  | |

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

## `apiV1AdspacesIdMediaBuyingSegmentsGet()`

```php
apiV1AdspacesIdMediaBuyingSegmentsGet($id, $dim, $refresh)
```

Cost and revenue per segment: country, OS, hour, day of week, browser, region, carrier, language.

`data.cost_basis`: `network` (country / os / hour: the network's exact cost per segment; 400 `not_supported` when the network does not report it), `daily` (weekday: the network's cost per day over 28 days, keys 1 = Monday … 7 = Sunday, `days` per row = how many of that day were charged; judged from 3) or `estimated` (browser / region / carrier / language: the period's cost split by each value's share of the traffic, `cost_estimated: true`; a candidate needs twice `segment_min_spend`). On ExoClick browser and language are `network` (its own cost per value); a row keyed `n:<name>` is cost for a value our stats do not have and is never a candidate. The weekday and estimated dimensions work on every network and are always proposals (never written by `segment_mode` auto); approving a browser or language exclusion on ExoClick or TrafficStars writes it there (`writable: true`; TrafficStars only takes include lists, so it is refused on a campaign that targets every browser / language), every other one is applied by hand.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.
$dim = 'dim_example'; // string | `country` (default where the network reports cost per segment) | `os` | `hour` | `weekday` (default otherwise) | `browser` | `region` | `carrier` | `language`. The overview's `segment_dims` lists the ones this zone has.
$refresh = 'refresh_example'; // string | `1` re-reads the network instead of the cache.

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingSegmentsGet($id, $dim, $refresh);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingSegmentsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |
| **dim** | **string**| &#x60;country&#x60; (default where the network reports cost per segment) | &#x60;os&#x60; | &#x60;hour&#x60; | &#x60;weekday&#x60; (default otherwise) | &#x60;browser&#x60; | &#x60;region&#x60; | &#x60;carrier&#x60; | &#x60;language&#x60;. The overview&#39;s &#x60;segment_dims&#x60; lists the ones this zone has. | [optional] |
| **refresh** | **string**| &#x60;1&#x60; re-reads the network instead of the cache. | [optional] |

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

## `apiV1AdspacesIdMediaBuyingSourceBidGet()`

```php
apiV1AdspacesIdMediaBuyingSourceBidGet($id)
```

The campaign's current bid at the network.

400 `not_supported` when the network does not report bids.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingSourceBidGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingSourceBidGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |

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

## `apiV1AdspacesIdMediaBuyingSourceBidPut()`

```php
apiV1AdspacesIdMediaBuyingSourceBidPut($id, $api_v1_adspaces_id_media_buying_source_bid_put_request)
```

Set the campaign's bid at the network.

In the network's currency and bid unit.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.
$api_v1_adspaces_id_media_buying_source_bid_put_request = new \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingSourceBidPutRequest(); // \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingSourceBidPutRequest

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingSourceBidPut($id, $api_v1_adspaces_id_media_buying_source_bid_put_request);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingSourceBidPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |
| **api_v1_adspaces_id_media_buying_source_bid_put_request** | [**\GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingSourceBidPutRequest**](../Model/ApiV1AdspacesIdMediaBuyingSourceBidPutRequest.md)|  | |

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

## `apiV1AdspacesIdMediaBuyingSourceRetryPost()`

```php
apiV1AdspacesIdMediaBuyingSourceRetryPost($id, $body)
```

Retry the connection to the source campaign.

Clears a failed pause/activate state so goadserver tries the network again.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.
$body = array('key' => new \stdClass); // object

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingSourceRetryPost($id, $body);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingSourceRetryPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |
| **body** | **object**|  | [optional] |

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

## `apiV1AdspacesIdMediaBuyingTargetingGet()`

```php
apiV1AdspacesIdMediaBuyingTargetingGet($id)
```

The campaign's placement targeting (all, or an allow-list).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingTargetingGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingTargetingGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |

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

## `apiV1AdspacesIdMediaBuyingTargetingPut()`

```php
apiV1AdspacesIdMediaBuyingTargetingPut($id, $api_v1_adspaces_id_media_buying_targeting_put_request)
```

Switch to / edit the allow-list, or back to all placements.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.
$api_v1_adspaces_id_media_buying_targeting_put_request = new \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingTargetingPutRequest(); // \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingTargetingPutRequest

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingTargetingPut($id, $api_v1_adspaces_id_media_buying_targeting_put_request);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingTargetingPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |
| **api_v1_adspaces_id_media_buying_targeting_put_request** | [**\GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingTargetingPutRequest**](../Model/ApiV1AdspacesIdMediaBuyingTargetingPutRequest.md)|  | |

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

## `apiV1AdspacesIdMediaBuyingTargetingSuggestGet()`

```php
apiV1AdspacesIdMediaBuyingTargetingSuggestGet($id)
```

Suggested allow-list: the profitable placements of the policy window.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingTargetingSuggestGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingTargetingSuggestGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |

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

## `apiV1AdspacesIdMediaBuyingTrackRecordGet()`

```php
apiV1AdspacesIdMediaBuyingTrackRecordGet($id)
```

Track record: did each change do what it was meant to?

Every change of the last 60 days (blocks, bid per placement, campaign bid, network budget, segment exclusions…) measured on the money: the policy window of full days before the change against the same after it, per lever and actor (`policy` / `agent` / `manual`). Per lever: proposed / approved / rejected / expired / auto / failed counts, measured right / wrong (`no_data` under 0.05 cost a side, `pending` until the after-window is complete, `not_judged` for quality blocks), `right_pct`, `approval_pct`, `profit_per_day` (blocks: the loss stopped per day) and `ready_for_auto` (>= 10 measured and >= 70% right — a hint, no mode is switched); the 20 newest measured actions with their before / after. Cached 15 minutes.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingTrackRecordGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingTrackRecordGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |

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

## `apiV1AdspacesIdMediaBuyingZonesGet()`

```php
apiV1AdspacesIdMediaBuyingZonesGet($id, $days, $revenue_metric)
```

Source-zone report: revenue against cost per placement.

Per source zone (placement) of the connected campaign over the policy window: cost (exact from the network or estimated), revenue, margin, CPA, traffic, delivery, off-geo share, confidence fields and whether it is blocked.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The ad space id.
$days = 56; // int | Days to report (1-30); default the policy window.
$revenue_metric = 'revenue_metric_example'; // string | `paid` or `earned`; default the policy's.

try {
    $apiInstance->apiV1AdspacesIdMediaBuyingZonesGet($id, $days, $revenue_metric);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1AdspacesIdMediaBuyingZonesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ad space id. | |
| **days** | **int**| Days to report (1-30); default the policy window. | [optional] |
| **revenue_metric** | **string**| &#x60;paid&#x60; or &#x60;earned&#x60;; default the policy&#39;s. | [optional] |

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

## `apiV1MediaBuyingPortfolioGet()`

```php
apiV1MediaBuyingPortfolioGet($days)
```

Every buy side by side: margin against target, spend, proposals, the agent's latest summary.

One row per ad zone or tracking link connected to a network campaign (`kind` adzone | tracking_link): network and campaign, the linked campaigns' countries, the policy's modes and target, revenue / network cost / profit / margin over the last `days` full days, cost per day, today's revenue and cost, `status` (losing | below_target | on_target | no_data — worst first), open proposals, last action and the agent's latest summary; plus `totals`. Database only, no network call. A key pinned to ad spaces or sites sees only those.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$days = 7; // int | Full days, ending yesterday.

try {
    $apiInstance->apiV1MediaBuyingPortfolioGet($days);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1MediaBuyingPortfolioGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **days** | **int**| Full days, ending yesterday. | [optional] [default to 7] |

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

## `apiV1TrackerMediaBuyingPortfolioGet()`

```php
apiV1TrackerMediaBuyingPortfolioGet($days)
```

Every buy side by side (tracker accounts — the same overview): margin against target, spend, proposals, the agent's latest summary.

One row per ad zone or tracking link connected to a network campaign (`kind` adzone | tracking_link): network and campaign, the linked campaigns' countries, the policy's modes and target, revenue / network cost / profit / margin over the last `days` full days, cost per day, today's revenue and cost, `status` (losing | below_target | on_target | no_data — worst first), open proposals, last action and the agent's latest summary; plus `totals`. Database only, no network call. A key pinned to ad spaces or sites sees only those.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$days = 7; // int | Full days, ending yesterday.

try {
    $apiInstance->apiV1TrackerMediaBuyingPortfolioGet($days);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerMediaBuyingPortfolioGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **days** | **int**| Full days, ending yesterday. | [optional] [default to 7] |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost($id, $action_id, $api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request)
```

Approve or reject a proposal.

Approving carries the proposal out at the network (a block, a bid, a budget, a segment exclusion). Approving an `expand_country` proposal (ad zones only) duplicates the ad zone — linked campaigns, own ads, settings, buying policy — and launches its own network campaign for that one country at the proposed bid and test budget, with the source zone's quality blocks as its blacklist; answers `{id, action, status, zone_id, new_spaceid, launch}`; a failure removes the duplicate again (422).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.
$action_id = 56; // int | The proposal id from the activity log.
$api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request = new \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostRequest(); // \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostRequest

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost($id, $action_id, $api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |
| **action_id** | **int**| The proposal id from the activity log. | |
| **api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request** | [**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostRequest**](../Model/ApiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostRequest.md)|  | |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingActionsGet()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingActionsGet($id, $status, $actor, $action, $zone, $from, $to, $limit, $format)
```

Activity log: every block, bid, budget and other change, and open proposals.

Newest first. A `bid_change` on a network with a bid per country carries `metrics.countries` [{country, pct, old, new, margin, cost, revenue}].

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.
$status = 'status_example'; // string | `proposed` | `applied` | `rejected` | `failed` | `expired`.
$actor = 'actor_example'; // string | `policy` | `agent` | `advertiser` | `manual` | `inherited`.
$action = 'action_example'; // string | e.g. `block`, `unblock`, `zone_bid`, `bid_change`, `net_budget`, `exclude_segment`, `expand_country`, `ip_block`, `report`.
$zone = 'zone_example'; // string | One source zone id.
$from = 'from_example'; // string | YYYY-MM-DD.
$to = 'to_example'; // string | YYYY-MM-DD.
$limit = 56; // int | 1-1000, default 200.
$format = 'format_example'; // string | `csv` for a CSV download (up to 5000 rows).

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingActionsGet($id, $status, $actor, $action, $zone, $from, $to, $limit, $format);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingActionsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |
| **status** | **string**| &#x60;proposed&#x60; | &#x60;applied&#x60; | &#x60;rejected&#x60; | &#x60;failed&#x60; | &#x60;expired&#x60;. | [optional] |
| **actor** | **string**| &#x60;policy&#x60; | &#x60;agent&#x60; | &#x60;advertiser&#x60; | &#x60;manual&#x60; | &#x60;inherited&#x60;. | [optional] |
| **action** | **string**| e.g. &#x60;block&#x60;, &#x60;unblock&#x60;, &#x60;zone_bid&#x60;, &#x60;bid_change&#x60;, &#x60;net_budget&#x60;, &#x60;exclude_segment&#x60;, &#x60;expand_country&#x60;, &#x60;ip_block&#x60;, &#x60;report&#x60;. | [optional] |
| **zone** | **string**| One source zone id. | [optional] |
| **from** | **string**| YYYY-MM-DD. | [optional] |
| **to** | **string**| YYYY-MM-DD. | [optional] |
| **limit** | **int**| 1-1000, default 200. | [optional] |
| **format** | **string**| &#x60;csv&#x60; for a CSV download (up to 5000 rows). | [optional] |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost($id, $body)
```

Run the buying agent now.

At most once per 10 minutes per zone (429 `too_soon`). Refused while the zone is switched off. Returns the new run.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.
$body = array('key' => new \stdClass); // object

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost($id, $body);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |
| **body** | **object**|  | [optional] |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet($id)
```

The buying agent's recent runs with their summaries and proposals.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingBlockPost()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingBlockPost($id, $api_v1_tracker_tracking_links_id_media_buying_block_post_request)
```

Block or unblock one source zone at the network.

Writes the change to the network campaign right away and logs it as a manual action. 502 when the network refuses.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.
$api_v1_tracker_tracking_links_id_media_buying_block_post_request = new \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingBlockPostRequest(); // \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingBlockPostRequest

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingBlockPost($id, $api_v1_tracker_tracking_links_id_media_buying_block_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingBlockPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |
| **api_v1_tracker_tracking_links_id_media_buying_block_post_request** | [**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingBlockPostRequest**](../Model/ApiV1TrackerTrackingLinksIdMediaBuyingBlockPostRequest.md)|  | |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingCompareGet()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingCompareGet($id, $with, $days)
```

Compare with another buy (A/B, e.g. against a control left to the network's own optimiser).

This buy against another of yours over the same days (default: its control — policy `control_for` — or the buy it is the control of). Per buy: revenue, network cost, profit, margin, profit and volume per day, and each day's figures. Verdict: a paired test on the daily margins — `a_better` / `b_better` (a = this buy) only when the mean difference is at least twice its standard error over at least 5 days with revenue on both sides, else `too_close` or `too_little_data`; `note` says it in a sentence. Database only, no network call.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.
$with = 56; // int | The other buy (ad zone or tracking link id); default its control.
$days = 14; // int | Full days, ending yesterday.

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingCompareGet($id, $with, $days);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingCompareGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |
| **with** | **int**| The other buy (ad zone or tracking link id); default its control. | [optional] |
| **days** | **int**| Full days, ending yesterday. | [optional] [default to 14] |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingGet()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingGet($id)
```

Overview: the connected network, what it supports, the policy and open proposals.

Everything the panel shows before the report: network and campaign, capability flags (`supports_*`; `supports_country_bids` = the campaign bid is an absolute bid per country, judged and moved per country), the URL suffix that captures the source zone id (`capture`), the stored `policy`, `pending_proposals`, the source campaign state and `owner_gets_report`. 404 when the tracking link is not connected to an external network campaign.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingIpsGet()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingIpsGet($id)
```

The IP preblock list: ranges on the network's exclusion list and proposals.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingIpsGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingIpsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingIpsPost()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingIpsPost($id, $api_v1_tracker_tracking_links_id_media_buying_ips_post_request)
```

Change the IP preblock list.

`add` blocks the given ranges now, `approve` / `reject` decide proposals, `remove` takes ranges off, `refresh` builds the list now (no `cidrs`).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.
$api_v1_tracker_tracking_links_id_media_buying_ips_post_request = new \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingIpsPostRequest(); // \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingIpsPostRequest

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingIpsPost($id, $api_v1_tracker_tracking_links_id_media_buying_ips_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingIpsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |
| **api_v1_tracker_tracking_links_id_media_buying_ips_post_request** | [**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingIpsPostRequest**](../Model/ApiV1TrackerTrackingLinksIdMediaBuyingIpsPostRequest.md)|  | |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet($id)
```

The campaign's daily budget at the network.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut($id, $media_buying_policy)
```

Save the buying policy (any subset of its fields).

Fields left out keep their stored value. Returns the stored, normalized policy.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.
$media_buying_policy = new \GoAdServerApi\Model\MediaBuyingPolicy(); // \GoAdServerApi\Model\MediaBuyingPolicy

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut($id, $media_buying_policy);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |
| **media_buying_policy** | [**\GoAdServerApi\Model\MediaBuyingPolicy**](../Model/MediaBuyingPolicy.md)|  | |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet($id, $preset)
```

Suggested policy from the zone's own traffic, with a reason per field.

Nothing is saved. `data.policy` is the full suggested policy; `data.suggestions` lists `{field, value, why}`; with `preset` the preset's own changes follow, each with its reason.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.
$preset = 'preset_example'; // string | On top of the suggestion: conservative (every lever proposes, 95% certainty, twice the minimum cost and traffic, 5% bid steps, at most 20 changes a day), balanced (the suggestion as it is) or aggressive (placement blocks, bids per placement and segment exclusions in auto, the agent in auto, 80% certainty, 15% bid steps). No preset touches the network budget, the campaign bid or the IP preblock. Unknown value: 422.

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet($id, $preset);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |
| **preset** | **string**| On top of the suggestion: conservative (every lever proposes, 95% certainty, twice the minimum cost and traffic, 5% bid steps, at most 20 changes a day), balanced (the suggestion as it is) or aggressive (placement blocks, bids per placement and segment exclusions in auto, the agent in auto, 80% certainty, 15% bid steps). No preset touches the network budget, the campaign bid or the IP preblock. Unknown value: 422. | [optional] |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingReportPost()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingReportPost($id, $api_v1_tracker_tracking_links_id_media_buying_report_post_request)
```

Send the media buying report now.

The same report the daily / weekly schedule sends: to the admins' notification event and, where the owner runs the buy (admanager, tracking links), the owner's email and the `media_buying.report` event. Returns the report and where it went.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.
$api_v1_tracker_tracking_links_id_media_buying_report_post_request = new \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingReportPostRequest(); // \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingReportPostRequest

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingReportPost($id, $api_v1_tracker_tracking_links_id_media_buying_report_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingReportPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |
| **api_v1_tracker_tracking_links_id_media_buying_report_post_request** | [**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingReportPostRequest**](../Model/ApiV1TrackerTrackingLinksIdMediaBuyingReportPostRequest.md)|  | |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet($id, $dim, $refresh)
```

Cost and revenue per segment: country, OS, hour, day of week, browser, region, carrier, language.

`data.cost_basis`: `network` (country / os / hour: the network's exact cost per segment; 400 `not_supported` when the network does not report it), `daily` (weekday: the network's cost per day over 28 days, keys 1 = Monday … 7 = Sunday, `days` per row = how many of that day were charged; judged from 3) or `estimated` (browser / region / carrier / language: the period's cost split by each value's share of the traffic, `cost_estimated: true`; a candidate needs twice `segment_min_spend`). On ExoClick browser and language are `network` (its own cost per value); a row keyed `n:<name>` is cost for a value our stats do not have and is never a candidate. The weekday and estimated dimensions work on every network and are always proposals (never written by `segment_mode` auto); approving a browser or language exclusion on ExoClick or TrafficStars writes it there (`writable: true`; TrafficStars only takes include lists, so it is refused on a campaign that targets every browser / language), every other one is applied by hand.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.
$dim = 'dim_example'; // string | `country` (default where the network reports cost per segment) | `os` | `hour` | `weekday` (default otherwise) | `browser` | `region` | `carrier` | `language`. The overview's `segment_dims` lists the ones this zone has.
$refresh = 'refresh_example'; // string | `1` re-reads the network instead of the cache.

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet($id, $dim, $refresh);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |
| **dim** | **string**| &#x60;country&#x60; (default where the network reports cost per segment) | &#x60;os&#x60; | &#x60;hour&#x60; | &#x60;weekday&#x60; (default otherwise) | &#x60;browser&#x60; | &#x60;region&#x60; | &#x60;carrier&#x60; | &#x60;language&#x60;. The overview&#39;s &#x60;segment_dims&#x60; lists the ones this zone has. | [optional] |
| **refresh** | **string**| &#x60;1&#x60; re-reads the network instead of the cache. | [optional] |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet($id)
```

The campaign's current bid at the network.

400 `not_supported` when the network does not report bids.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut($id, $api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request)
```

Set the campaign's bid at the network.

In the network's currency and bid unit.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.
$api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request = new \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutRequest(); // \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutRequest

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut($id, $api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |
| **api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request** | [**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutRequest**](../Model/ApiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutRequest.md)|  | |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost($id, $body)
```

Retry the connection to the source campaign.

Clears a failed pause/activate state so goadserver tries the network again.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.
$body = array('key' => new \stdClass); // object

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost($id, $body);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |
| **body** | **object**|  | [optional] |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet($id)
```

The campaign's placement targeting (all, or an allow-list).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut($id, $api_v1_tracker_tracking_links_id_media_buying_targeting_put_request)
```

Switch to / edit the allow-list, or back to all placements.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.
$api_v1_tracker_tracking_links_id_media_buying_targeting_put_request = new \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingTargetingPutRequest(); // \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingTargetingPutRequest

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut($id, $api_v1_tracker_tracking_links_id_media_buying_targeting_put_request);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |
| **api_v1_tracker_tracking_links_id_media_buying_targeting_put_request** | [**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingTargetingPutRequest**](../Model/ApiV1TrackerTrackingLinksIdMediaBuyingTargetingPutRequest.md)|  | |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet($id)
```

Suggested allow-list: the profitable placements of the policy window.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet($id)
```

Track record: did each change do what it was meant to?

Every change of the last 60 days (blocks, bid per placement, campaign bid, network budget, segment exclusions…) measured on the money: the policy window of full days before the change against the same after it, per lever and actor (`policy` / `agent` / `manual`). Per lever: proposed / approved / rejected / expired / auto / failed counts, measured right / wrong (`no_data` under 0.05 cost a side, `pending` until the after-window is complete, `not_judged` for quality blocks), `right_pct`, `approval_pct`, `profit_per_day` (blocks: the loss stopped per day) and `ready_for_auto` (>= 10 measured and >= 70% right — a hint, no mode is switched); the 20 newest measured actions with their before / after. Cached 15 minutes.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet($id);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |

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

## `apiV1TrackerTrackingLinksIdMediaBuyingZonesGet()`

```php
apiV1TrackerTrackingLinksIdMediaBuyingZonesGet($id, $days, $revenue_metric)
```

Source-zone report: revenue against cost per placement.

Per source zone (placement) of the connected campaign over the policy window: cost (exact from the network or estimated), revenue, margin, CPA, traffic, delivery, off-geo share, confidence fields and whether it is blocked.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AgenticMediaBuyingApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | The tracking link id.
$days = 56; // int | Days to report (1-30); default the policy window.
$revenue_metric = 'revenue_metric_example'; // string | `paid` or `earned`; default the policy's.

try {
    $apiInstance->apiV1TrackerTrackingLinksIdMediaBuyingZonesGet($id, $days, $revenue_metric);
} catch (Exception $e) {
    echo 'Exception when calling AgenticMediaBuyingApi->apiV1TrackerTrackingLinksIdMediaBuyingZonesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The tracking link id. | |
| **days** | **int**| Days to report (1-30); default the policy window. | [optional] |
| **revenue_metric** | **string**| &#x60;paid&#x60; or &#x60;earned&#x60;; default the policy&#39;s. | [optional] |

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
