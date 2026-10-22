# BidRuleset

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** |  | [optional]
**userid** | **int** | Owner advertiser id. | [optional]
**name** | **string** |  | [optional]
**paytype** | **string** | Pricing flavor of every bid in the ruleset. Must match the campaign&#39;s paytype at attach-time — a &#x60;cpc&#x60; ruleset can only attach to a CPC campaign, etc. | [optional]
**default_tz** | **string** | IANA timezone applied to any rule whose own &#x60;tz&#x60; is empty. Defaults to &#x60;UTC&#x60;. | [optional]
**rules** | [**\GoAdServerApi\Model\BidRule[]**](BidRule.md) |  | [optional]
**is_deleted** | **bool** |  | [optional]
**created** | **int** | Unix seconds. | [optional]
**updated** | **int** | Unix seconds. | [optional]
**campaign_count** | **int** | Count of non-deleted campaigns currently attached to this ruleset. Useful as the \&quot;edit affects N other campaigns\&quot; heads-up before a destructive change. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
