# RetargetingPixel

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** |  | [optional]
**name** | **string** |  | [optional]
**is_active** | **bool** |  | [optional]
**visitors** | **int** | Captured visitor count from ClickHouse. | [optional]
**purge_days_after** | **int** | Remove a visitor from the list this many days after capture (panel field &#x60;rmdays&#x60;). 0 &#x3D; off. | [optional]
**skip_days_after** | **int** | Stop targeting a visitor this many days after capture (panel field &#x60;skipdays&#x60;). 0 &#x3D; off. | [optional]
**skip_before_hours** | **int** | Don&#39;t target a visitor until this many hours after capture (panel field &#x60;skiphours&#x60;). 0 &#x3D; off. | [optional]
**skip_visits_after** | **int** | Stop targeting a visitor after this many visits (panel field &#x60;skipvisits&#x60;). 0 &#x3D; off. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
