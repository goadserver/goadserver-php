# ApiV1AccountBillingAveragesGet200Response

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**costs** | **float** | Projected month total in the plan currency (usage, overcharges and modules). | [optional]
**overcharges** | [**array<string,\GoAdServerApi\Model\ApiV1AccountBillingAveragesGet200ResponseOverchargesValue>**](ApiV1AccountBillingAveragesGet200ResponseOverchargesValue.md) | Usage above the fixed plan&#39;s included events, keyed by event type. | [optional]
**modules** | [**\GoAdServerApi\Model\ApiV1AccountBillingAveragesGet200ResponseModulesInner[]**](ApiV1AccountBillingAveragesGet200ResponseModulesInner.md) | Module charges, projected to month end. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
