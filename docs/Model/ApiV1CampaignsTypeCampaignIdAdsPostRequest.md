# ApiV1CampaignsTypeCampaignIdAdsPostRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**title** | **string** |  |
**bid** | **float** |  |
**destination_url** | **string** |  | [optional]
**description** | **string** |  | [optional]
**display_url** | **string** |  | [optional]
**preview_url** | **string** |  | [optional]
**is_active** | **bool** |  | [optional] [default to true]
**sort_weight** | **int** |  | [optional] [default to 0]
**banner_pool_id** | **int** | Reference an existing banner pool (most common). | [optional]
**video_pool_id** | **int** |  | [optional]
**url_pool_id** | **int** | Independent click-URL rotation pool — combinable with any banner ref. | [optional]
**creative_id** | **int** | Reference a single creative directly. | [optional]
**banner_url** | **string** | Direct image URL (hosted-elsewhere creatives). | [optional]
**banner_html** | **string** | Raw HTML/JS payload (tag-based ad units). | [optional]
**bansizeid** | **int** | Auto-derived for pool/creative refs; required for url/html on size-aware adtypes. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
