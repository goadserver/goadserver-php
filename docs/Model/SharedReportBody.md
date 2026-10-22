# SharedReportBody

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**name** | **string** |  |
**statstype** | **string** | Anything else is stored as advertiser. | [optional] [default to 'advertiser']
**campaigns** | [**\GoAdServerApi\Model\SharedReportBodyCampaignsInner[]**](SharedReportBodyCampaignsInner.md) |  | [optional]
**sites** | [**\GoAdServerApi\Model\ApiV1SharedReportsIdPutRequestSitesInner[]**](ApiV1SharedReportsIdPutRequestSitesInner.md) |  | [optional]
**settings** | [**\GoAdServerApi\Model\SharedReportBodySettings**](SharedReportBodySettings.md) |  | [optional]
**options** | [**\GoAdServerApi\Model\SharedReportBodyOptions**](SharedReportBodyOptions.md) |  | [optional]
**tagids** | **string** | CSV of tag ids (GET /api/v1/tags). | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
