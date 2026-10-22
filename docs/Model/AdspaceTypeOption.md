# AdspaceTypeOption

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**key** | **string** |  | [optional]
**type** | **string** |  | [optional]
**default** | **mixed** |  | [optional]
**multiple** | **bool** | Allow multiple values (selects only). | [optional]
**list_items** | **string** | Where this option&#39;s options come from (e.g. \&quot;banner_sizes\&quot;, \&quot;countries\&quot;). | [optional]
**label** | **string** |  | [optional]
**min** | **int** |  | [optional]
**max** | **int** |  | [optional]
**mod_enabled** | **string** | Module gate path; only present when the option is conditional. | [optional]
**items** | **array<string,mixed>** | Inline enum options (e.g. popads.displaytypes → {popup: {id:1}, popunder: {id:2}}). | [optional]
**sub_options** | [**\GoAdServerApi\Model\AdspaceTypeOption[]**](AdspaceTypeOption.md) | Nested options revealed by a bool toggle (e.g. use_fixed_capping → capping_minutes). | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
