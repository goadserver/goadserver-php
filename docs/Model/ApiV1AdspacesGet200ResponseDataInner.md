# ApiV1AdspacesGet200ResponseDataInner

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** |  | [optional]
**name** | **string** |  | [optional]
**site** | [**\GoAdServerApi\Model\ApiV1TrackingConversionTypesPut200ResponseConversionTypesInner**](ApiV1TrackingConversionTypesPut200ResponseConversionTypesInner.md) |  | [optional]
**siteid** | **int** |  | [optional]
**adspace_type** | **string** | Type config key (e.g. bannerads). Empty when the underlying type is no longer in the tenant config. | [optional]
**adzone_typeid** | **int** |  | [optional]
**adtype_id** | **int** |  | [optional]
**ad_type** | **int** | Backward-compat alias for adtype_id. | [optional]
**adsizeid** | **int** |  | [optional]
**is_active** | **bool** | Derived from publisher_adzones.isinuse. | [optional]
**date_added** | **\DateTime** |  | [optional]
**time_changed** | **int** | Unix seconds of last update. | [optional]
**is_deleted** | **bool** |  | [optional]
**parentid** | **int** |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
