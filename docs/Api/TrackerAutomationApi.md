# GoAdServerApi\TrackerAutomationApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1TrackerExportTypeGet()**](TrackerAutomationApi.md#apiV1TrackerExportTypeGet) | **GET** /api/v1/tracker/export/{type} | Export tracker entities as CSV. |
| [**apiV1TrackerImportTypePost()**](TrackerAutomationApi.md#apiV1TrackerImportTypePost) | **POST** /api/v1/tracker/import/{type} | Bulk-import offers from CSV. |
| [**apiV1TrackerReportSchedulesGet()**](TrackerAutomationApi.md#apiV1TrackerReportSchedulesGet) | **GET** /api/v1/tracker/report-schedules | List scheduled report deliveries. |
| [**apiV1TrackerReportSchedulesIdDelete()**](TrackerAutomationApi.md#apiV1TrackerReportSchedulesIdDelete) | **DELETE** /api/v1/tracker/report-schedules/{id} | Delete a report schedule. |
| [**apiV1TrackerReportSchedulesIdGet()**](TrackerAutomationApi.md#apiV1TrackerReportSchedulesIdGet) | **GET** /api/v1/tracker/report-schedules/{id} | Get one report schedule. |
| [**apiV1TrackerReportSchedulesIdPut()**](TrackerAutomationApi.md#apiV1TrackerReportSchedulesIdPut) | **PUT** /api/v1/tracker/report-schedules/{id} | Update a report schedule. |
| [**apiV1TrackerReportSchedulesPost()**](TrackerAutomationApi.md#apiV1TrackerReportSchedulesPost) | **POST** /api/v1/tracker/report-schedules | Schedule a recurring report. |
| [**apiV1TrackerRulesGet()**](TrackerAutomationApi.md#apiV1TrackerRulesGet) | **GET** /api/v1/tracker/rules | List automation rules. |
| [**apiV1TrackerRulesIdDelete()**](TrackerAutomationApi.md#apiV1TrackerRulesIdDelete) | **DELETE** /api/v1/tracker/rules/{id} | Delete a rule. |
| [**apiV1TrackerRulesIdExecutionsGet()**](TrackerAutomationApi.md#apiV1TrackerRulesIdExecutionsGet) | **GET** /api/v1/tracker/rules/{id}/executions | Execution history for one rule. |
| [**apiV1TrackerRulesIdGet()**](TrackerAutomationApi.md#apiV1TrackerRulesIdGet) | **GET** /api/v1/tracker/rules/{id} | Get one rule. |
| [**apiV1TrackerRulesIdPut()**](TrackerAutomationApi.md#apiV1TrackerRulesIdPut) | **PUT** /api/v1/tracker/rules/{id} | Update a rule. |
| [**apiV1TrackerRulesIdRunPost()**](TrackerAutomationApi.md#apiV1TrackerRulesIdRunPost) | **POST** /api/v1/tracker/rules/{id}/run | Run a rule now. |
| [**apiV1TrackerRulesPost()**](TrackerAutomationApi.md#apiV1TrackerRulesPost) | **POST** /api/v1/tracker/rules | Create an automation rule. |


## `apiV1TrackerExportTypeGet()`

```php
apiV1TrackerExportTypeGet($type): string
```

Export tracker entities as CSV.

Returns `text/csv`, not JSON — the same file the scheduled reports mail. Scoped `tracker.read` rather than `stats.read`: this dumps configuration, not statistics.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerAutomationApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string | What to export.

try {
    $result = $apiInstance->apiV1TrackerExportTypeGet($type);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerAutomationApi->apiV1TrackerExportTypeGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**| What to export. | |

### Return type

**string**

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `text/csv`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerImportTypePost()`

```php
apiV1TrackerImportTypePost($type, $file, $commit, $overwrite): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Bulk-import offers from CSV.

`multipart/form-data` with the CSV as `file`. Two flags decide how far it goes: without `commit` the upload is validated and reported on but nothing is written, and without `overwrite` every row inserts and any `id` column is ignored, so a spreadsheet cannot clobber live rows by accident. An id that resolves to no row the caller owns is a row error, never a silent insert.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerAutomationApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string | What to import.
$file = '/path/to/file.txt'; // \SplFileObject | The CSV, at most 10 MiB.
$commit = 'commit_example'; // string | \\\"true\\\" to write. Absent = validate and report only.
$overwrite = 'overwrite_example'; // string | \\\"true\\\" to update rows whose id column matches one of the caller's own.

try {
    $result = $apiInstance->apiV1TrackerImportTypePost($type, $file, $commit, $overwrite);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerAutomationApi->apiV1TrackerImportTypePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**| What to import. | |
| **file** | **\SplFileObject****\SplFileObject**| The CSV, at most 10 MiB. | |
| **commit** | **string**| \\\&quot;true\\\&quot; to write. Absent &#x3D; validate and report only. | [optional] |
| **overwrite** | **string**| \\\&quot;true\\\&quot; to update rows whose id column matches one of the caller&#39;s own. | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `multipart/form-data`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerReportSchedulesGet()`

```php
apiV1TrackerReportSchedulesGet(): \GoAdServerApi\Model\ApiV1TrackerReportSchedulesGet200Response
```

List scheduled report deliveries.

Recurring CSV exports mailed to a list of recipients.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerAutomationApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1TrackerReportSchedulesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerAutomationApi->apiV1TrackerReportSchedulesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerReportSchedulesGet200Response**](../Model/ApiV1TrackerReportSchedulesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerReportSchedulesIdDelete()`

```php
apiV1TrackerReportSchedulesIdDelete($id): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
```

Delete a report schedule.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerAutomationApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Schedule ID.

try {
    $result = $apiInstance->apiV1TrackerReportSchedulesIdDelete($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerAutomationApi->apiV1TrackerReportSchedulesIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Schedule ID. | |

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

## `apiV1TrackerReportSchedulesIdGet()`

```php
apiV1TrackerReportSchedulesIdGet($id): \GoAdServerApi\Model\ApiV1TrackerReportSchedulesIdGet200Response
```

Get one report schedule.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerAutomationApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Schedule ID.

try {
    $result = $apiInstance->apiV1TrackerReportSchedulesIdGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerAutomationApi->apiV1TrackerReportSchedulesIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Schedule ID. | |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerReportSchedulesIdGet200Response**](../Model/ApiV1TrackerReportSchedulesIdGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerReportSchedulesIdPut()`

```php
apiV1TrackerReportSchedulesIdPut($id, $api_v1_tracker_report_schedules_id_put_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Update a report schedule.

Partial update.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerAutomationApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Schedule ID.
$api_v1_tracker_report_schedules_id_put_request = new \GoAdServerApi\Model\ApiV1TrackerReportSchedulesIdPutRequest(); // \GoAdServerApi\Model\ApiV1TrackerReportSchedulesIdPutRequest

try {
    $result = $apiInstance->apiV1TrackerReportSchedulesIdPut($id, $api_v1_tracker_report_schedules_id_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerAutomationApi->apiV1TrackerReportSchedulesIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Schedule ID. | |
| **api_v1_tracker_report_schedules_id_put_request** | [**\GoAdServerApi\Model\ApiV1TrackerReportSchedulesIdPutRequest**](../Model/ApiV1TrackerReportSchedulesIdPutRequest.md)|  | |

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

## `apiV1TrackerReportSchedulesPost()`

```php
apiV1TrackerReportSchedulesPost($api_v1_tracker_report_schedules_post_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Schedule a recurring report.

`report_type` names the same three exports `/export/{type}` serves.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerAutomationApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_tracker_report_schedules_post_request = new \GoAdServerApi\Model\ApiV1TrackerReportSchedulesPostRequest(); // \GoAdServerApi\Model\ApiV1TrackerReportSchedulesPostRequest

try {
    $result = $apiInstance->apiV1TrackerReportSchedulesPost($api_v1_tracker_report_schedules_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerAutomationApi->apiV1TrackerReportSchedulesPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_tracker_report_schedules_post_request** | [**\GoAdServerApi\Model\ApiV1TrackerReportSchedulesPostRequest**](../Model/ApiV1TrackerReportSchedulesPostRequest.md)|  | |

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

## `apiV1TrackerRulesGet()`

```php
apiV1TrackerRulesGet(): \GoAdServerApi\Model\ApiV1TrackerRulesGet200Response
```

List automation rules.

Standing rules that watch performance and act — pause an offer, block a placement, adjust a bid, or raise an alert.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerAutomationApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1TrackerRulesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerAutomationApi->apiV1TrackerRulesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerRulesGet200Response**](../Model/ApiV1TrackerRulesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerRulesIdDelete()`

```php
apiV1TrackerRulesIdDelete($id): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
```

Delete a rule.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerAutomationApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Rule ID.

try {
    $result = $apiInstance->apiV1TrackerRulesIdDelete($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerAutomationApi->apiV1TrackerRulesIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Rule ID. | |

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

## `apiV1TrackerRulesIdExecutionsGet()`

```php
apiV1TrackerRulesIdExecutionsGet($id): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Execution history for one rule.

Every firing, what matched, and what the rule did — including the runs it only reported because `apply_actions` was off.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerAutomationApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Rule ID.

try {
    $result = $apiInstance->apiV1TrackerRulesIdExecutionsGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerAutomationApi->apiV1TrackerRulesIdExecutionsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Rule ID. | |

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

## `apiV1TrackerRulesIdGet()`

```php
apiV1TrackerRulesIdGet($id): \GoAdServerApi\Model\ApiV1TrackerRulesIdGet200Response
```

Get one rule.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerAutomationApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Rule ID.

try {
    $result = $apiInstance->apiV1TrackerRulesIdGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerAutomationApi->apiV1TrackerRulesIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Rule ID. | |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerRulesIdGet200Response**](../Model/ApiV1TrackerRulesIdGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerRulesIdPut()`

```php
apiV1TrackerRulesIdPut($id, $api_v1_tracker_rules_id_put_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Update a rule.

Partial update.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerAutomationApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Rule ID.
$api_v1_tracker_rules_id_put_request = new \GoAdServerApi\Model\ApiV1TrackerRulesIdPutRequest(); // \GoAdServerApi\Model\ApiV1TrackerRulesIdPutRequest

try {
    $result = $apiInstance->apiV1TrackerRulesIdPut($id, $api_v1_tracker_rules_id_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerAutomationApi->apiV1TrackerRulesIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Rule ID. | |
| **api_v1_tracker_rules_id_put_request** | [**\GoAdServerApi\Model\ApiV1TrackerRulesIdPutRequest**](../Model/ApiV1TrackerRulesIdPutRequest.md)|  | |

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

## `apiV1TrackerRulesIdRunPost()`

```php
apiV1TrackerRulesIdRunPost($id, $window): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Run a rule now.

Evaluates immediately instead of waiting for the schedule. Honours the rule's own `apply_actions`, so a reporting-only rule stays harmless.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerAutomationApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Rule ID.
$window = 'window_example'; // string | Override the evaluation window for this run.

try {
    $result = $apiInstance->apiV1TrackerRulesIdRunPost($id, $window);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerAutomationApi->apiV1TrackerRulesIdRunPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Rule ID. | |
| **window** | **string**| Override the evaluation window for this run. | [optional] |

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

## `apiV1TrackerRulesPost()`

```php
apiV1TrackerRulesPost($api_v1_tracker_rules_post_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Create an automation rule.

`conditions` is the list the evaluator ANDs; `action` is what fires when they all hold. With `apply_actions` false the rule reports what it would have done without touching anything — the way to trust one before arming it.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerAutomationApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_tracker_rules_post_request = new \GoAdServerApi\Model\ApiV1TrackerRulesPostRequest(); // \GoAdServerApi\Model\ApiV1TrackerRulesPostRequest

try {
    $result = $apiInstance->apiV1TrackerRulesPost($api_v1_tracker_rules_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerAutomationApi->apiV1TrackerRulesPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_tracker_rules_post_request** | [**\GoAdServerApi\Model\ApiV1TrackerRulesPostRequest**](../Model/ApiV1TrackerRulesPostRequest.md)|  | |

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
