# CpmOptimizerBody

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**title** | **string** |  |
**opt_type** | **string** | 0 &#x3D; start after min_spendings, 1 &#x3D; after min_impressions. | [optional]
**min_spendings** | **float** | Spend (account currency) before a source is judged. Required, non-zero. |
**min_impressions** | **int** |  | [optional]
**max_days** | **int** | Stats look-back in days. | [optional]
**start_date** | **\DateTime** |  | [optional]
**conv_type** | **string** | Conversion-type id judged on. | [optional]
**optimise_with** | **string** |  | [optional]
**max_cpa** | **float** | optimise_with&#x3D;conv_count: max cost per conversion (account currency). | [optional]
**allowed_margin** | **float** | optimise_with&#x3D;conv_earnings: allowed margin %. | [optional]
**use_bidoptimiser** | **int** |  | [optional]
**increase_bids** | **int** |  | [optional]
**increase_bid_perc** | **int** | Percent per step. | [optional]
**increase_bid_cap** | **int** | Hours between steps. | [optional]
**decrease_bids** | **int** |  | [optional]
**decrease_bid_perc** | **int** |  | [optional]
**decrease_bid_cap** | **int** |  | [optional]
**min_ctr** | **float** |  | [optional]
**min_ctr_imps** | **int** |  | [optional]
**max_ctr** | **float** |  | [optional]
**max_ctr_imps** | **int** |  | [optional]
**id** | **int** |  | [optional] [readonly]
**type** | **string** |  | [optional] [readonly]
**userid** | **int** |  | [optional] [readonly]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
