# ApiV1ProductFeedsPostRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**campaign_id** | **int** | One of your RON campaigns (native 3, Search Ads 42 or banner 2). |
**url** | **string** | Feed URL, http(s), public host, at most 20 MB. |
**name** | **string** |  | [optional]
**format** | **string** |  | [optional] [default to 'auto']
**refresh_hours** | **int** | 0 &#x3D; manual. | [optional]
**max_items** | **int** |  | [optional]
**price_in_title** | **bool** |  | [optional]
**bid** | **float** | The new ads&#39; bid; 0 &#x3D; the bid of the campaign&#39;s newest ad. | [optional]
**template** | **string** | Dynamic banners (banner campaigns): card, overlay, minimal or custom. native (or leaving it out) &#x3D; plain native ads. | [optional]
**template_html** | **string** | custom template only. | [optional]
**cta** | **string** |  | [optional]
**accent** | **string** | #rrggbb | [optional]
**bansizeid** | **int** | Dynamic banners: one of the campaign&#39;s banner_sizes. | [optional]
**retarget** | **bool** |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
