# AdspaceCreateScreenSettings

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**venue_type** | **string** | Where the screen hangs; campaigns can target venue types. Anything else is 422 &#x60;screen_settings.venue_type&#x60;. | [optional] [default to 'other']
**lat** | **float** | Latitude, for campaign geo-fences (out of range 422). | [optional] [default to 0]
**lon** | **float** | Longitude (out of range 422). | [optional] [default to 0]
**address** | **string** | For your own reference; plain text. | [optional] [default to '']
**width** | **int** | Screen width in px (clamped). | [optional] [default to 1920]
**height** | **int** | Screen height in px (clamped). | [optional] [default to 1080]
**orientation** | **string** |  | [optional] [default to 'landscape']
**audience_per_play** | **float** | People who see one play (clamped). A proof of play costs the CPM bid × this. | [optional] [default to 1]
**opening_hours** | **string** | Daily on-air window HH:MM-HH:MM in &#x60;timezone&#x60; (22:00-06:00 spans midnight); empty &#x3D; always. Malformed 422 &#x60;screen_settings.opening_hours&#x60;. | [optional] [default to '']
**timezone** | **string** | IANA time zone for opening_hours; empty &#x3D; the ad server&#39;s. Unknown 422. | [optional] [default to '']
**slot_seconds** | **int** | Seconds an image ad stays on screen (clamped); a video plays to its end. | [optional] [default to 10]
**playlist_size** | **int** | Ads per pull (clamped). | [optional] [default to 6]
**screen_key** | **string** | The key the player sends. Ignored on write. | [optional] [readonly]
**rotate_key** | **bool** | true issues a new screen_key (the current player stops). | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
