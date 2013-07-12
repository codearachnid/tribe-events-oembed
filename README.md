Tribe The Events Calendar: oEmbed
===================

Enable oEmbed functionality on your WordPress The Events Calendar plugin. This will create the ability to 
serve your events via oEmbed directly through `http://{domain}/{event}/{slug}/oembed/` or `http://{domain}/{events}/oembed/`.

The service endpoint requires the url or id parameter for the event you are requesting to serve.

If requested the html value will display in a similar layout as 
![image](https://raw.github.com/codearachnid/tribe-events-oembed/master/screenshot.png)


## Known Limitations

* currently recurring events are not recognized
* self embedded links may not work as expected
* sometimes random issues with permalinks, fix by flushing permalinks. Settings > Permalinks > Save

