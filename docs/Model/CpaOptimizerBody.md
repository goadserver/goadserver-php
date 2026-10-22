# CpaOptimizerBody

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**title** | **string** | Letters, digits and spaces; anything else becomes &#x60;_&#x60;. |
**filter_type** | **string** | What a rule blocks / re-bids. Fixed after create in the panel. | [optional]
**max_cpa** | **float** | Spend (account currency) on a source before it is judged. Required, non-zero. |
**max_days** | **int** |  | [optional]
**start_date** | **\DateTime** |  | [optional]
**target_cpa** | **array<string,float>** | Conversion-type id → target CPA (account currency). Zero entries are dropped. | [optional]
**min_margin** | **array<string,int>** | Conversion-type id → minimum margin %. | [optional]
**min_ctr** | **float** |  | [optional]
**min_ctr_imps** | **int** |  | [optional]
**max_ctr** | **float** |  | [optional]
**max_ctr_imps** | **int** |  | [optional]
**bounce_id** | **int** | Conversion pixel used as the bounce signal; -1 &#x3D; off. | [optional]
**bounce_min_imps** | **int** |  | [optional]
**bounce_max_perc** | **int** |  | [optional]
**bounce_check_type** | **string** |  | [optional]
**cpa_action** | **string** |  | [optional]
**keep_spots_blocked** | **int** |  | [optional]
**id** | **int** |  | [optional] [readonly]
**type** | **string** |  | [optional] [readonly]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
