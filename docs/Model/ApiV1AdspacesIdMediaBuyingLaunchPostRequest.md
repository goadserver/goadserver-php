# ApiV1AdspacesIdMediaBuyingLaunchPostRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**adnetwork_id** | **int** |  | [optional]
**spec** | **object** | name, landing_url, bids [{country, price}], devices, traffic_type, daily_budget, freq_cap, freq_hours, start_inactive, blacklist, ad_type, template_campaign_id — as the launch options allow. | [optional]
**copy_blocks_from** | **int** | Another buying ad zone whose blocked placements are copied. | [optional]
**copy_blocks_kind** | **string** |  | [optional]
**preset** | **string** | Switch Agentic Media Buying on with this preset (see GET …/policy/suggest) once the network approves the campaign and it is connected to the zone. Stored as the policy&#39;s &#x60;start_preset&#x60; until then. | [optional]
**test_days** | **int** | Launch test phase in full days, 0-14 (default 3; 0 &#x3D; none): the network budget is not raised before the campaign&#39;s margin is judged against the target. Stored as the policy&#39;s &#x60;test_phase&#x60;. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
