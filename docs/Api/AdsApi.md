# GoAdServerApi\AdsApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1CampaignsTypeCampaignIdAdsMassUndeletePost()**](AdsApi.md#apiV1CampaignsTypeCampaignIdAdsMassUndeletePost) | **POST** /api/v1/campaigns/{type}/{campaignId}/ads/mass-undelete | Restore archived ads of a campaign. |


## `apiV1CampaignsTypeCampaignIdAdsMassUndeletePost()`

```php
apiV1CampaignsTypeCampaignIdAdsMassUndeletePost($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_undelete_post_request)
```

Restore archived ads of a campaign.

The undo of POST …/ads/mass-delete, for every campaign type except rtb (the single PUT …/ads/{adId}/restore is RON-only). Only ads at is_deleted = 1 move; on a tenant that validates RON ads they go back through validation. At most 200 ids.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AdsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$campaign_id = 56; // int
$api_v1_campaigns_type_campaign_id_ads_mass_undelete_post_request = new \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsMassUndeletePostRequest(); // \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsMassUndeletePostRequest

try {
    $apiInstance->apiV1CampaignsTypeCampaignIdAdsMassUndeletePost($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_undelete_post_request);
} catch (Exception $e) {
    echo 'Exception when calling AdsApi->apiV1CampaignsTypeCampaignIdAdsMassUndeletePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | |
| **campaign_id** | **int**|  | |
| **api_v1_campaigns_type_campaign_id_ads_mass_undelete_post_request** | [**\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsMassUndeletePostRequest**](../Model/ApiV1CampaignsTypeCampaignIdAdsMassUndeletePostRequest.md)|  | |

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
