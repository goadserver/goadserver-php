# ApiV1AdspacesIdCodeGet200ResponseCtv

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**vast** | **string** | VAST for one ad (or player_settings.pod_size ads). | [optional]
**pod** | **string** | An ad pod of 3: &lt;Ad sequence&#x3D;1..3&gt;, no campaign or advertiser twice, at most pod_max_seconds. | [optional]
**vmap** | **string** | VMAP 1.0 of the zone&#39;s breaks (pre-roll unless vmap_no_preroll, midroll_offsets, post-roll with vmap_postroll); each AdTagURI is this zone&#39;s VAST with the pod size. | [optional]
**pause** | **string** | Pause ad as JSON {ad: {image, width, height, click_url, impression_url}} or {ad: null}; fire impression_url when shown. | [optional]
**pause_vast** | **string** | The same pause ad as VAST 3 NonLinear (StaticResource + NonLinearClickThrough). | [optional]
**ssai** | **string** | Server-side ad insertion: replace {viewer_ip} and {viewer_user_agent}. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
