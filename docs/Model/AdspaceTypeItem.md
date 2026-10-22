# AdspaceTypeItem

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** |  | [optional]
**key** | **string** | Use as the {type} path parameter on /api/v1/adspaces/{type}/_schema and POST /api/v1/adspaces/{type}. | [optional]
**name** | **string** |  | [optional]
**adtype_id** | **int** |  | [optional]
**settings_key** | **string** | Body key for the type-specific settings block; \&quot;\&quot; if the type takes no nested settings. | [optional]
**selling_types** | **string[]** |  | [optional]
**use_min_price** | **bool** |  | [optional]
**use_smartzone** | **bool** |  | [optional]
**has_own_ads** | **bool** |  | [optional]
**has_preview** | **bool** |  | [optional]
**right** | **string** |  | [optional]
**options** | [**\GoAdServerApi\Model\AdspaceTypeOption[]**](AdspaceTypeOption.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
