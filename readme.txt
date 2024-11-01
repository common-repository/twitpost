=== Plugin Name ===
Contributors: Indeedle
Tags: twitter, indeedle, twitpost, tweet
Requires at least: 2.7
Tested up to: 2.8.4
Stable tag: 0.0.8

A plugin that posts a short blurb on your Twitter page linking to a post you've recently published.

== Description ==

A plugin that posts a short blurb on your Twitter page linking to a post you've recently published.

The message can be customized, and you can chose to notify twitter of all new posts (and exclude them on a post-by-post basis) or notify twitter manually on a post-by-post basis.

Now includes the Twitter sidebar widget.

*Note:* This is designed to work with Wordpress MU as well as a regular Wordpress blog. (Hopefully)


= Changes =
5/05/09, 0.0.5 - Added tinyURL API to shrink blog URLs\\
6/05/09, 0.0.6 - Hopefully added a fix for the 417 error\\
7/05/09, 0.0.7 - Fixed up the messed up numbering\\
8/05/09, 0.0.8 - Added language support

= Support =
I tend to be forgetful about checking this page, so if you'd like support or there's a bug or you've got a comment, I do have a [project page](http://indeedle.com/projects/twitpost/) for this with information, as well as a link to a small forum. You can also email me at twitpost@indeedle.com.

== Installation ==

1. Upload folder `twitbar` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Tools->Twitpost
4. Enter your username & password for twitter and save them.
5. Edit the settings to customize how twitbar works

== Frequently Asked Questions ==

= What's the different between Automatic method and Manual method? =

The methods are how the plugin goes about deciding whether or not to contact Twitter about your update.

With Automatic ever post published will be added to twitter, unless a custom field called `twitpost_action` with a value of `skip` is added to the post. This means users can exclude certain posts from being published, or publish them later.

Manual means nothing is sent to twitter unless you specifically add a custom field called `twitpost_action` to the post with a value of `post`.

With both situations, once a post has been published the `twitpost_action` will be updated (or added if it doesn't exist) with the value `added`. This is so in the future if you update the post it will not be added to twitter. However, if you change the value to `post` and then update the post, it will be sent to twitter.

You can come back at any time and change the value of `twitpost_action` to `post` which will cause the post to be added to twitter again.

= How do I write my message? =

The keywords USER, TITLE and URL inside the message will be replaced with their actual values. Eg: USER has written a post called TITLE -> URL. Due to twitter being short, you should keep the message short.

= Why don't you shorten the URLs? =

Set the shrink url option to a service. If the URL doesn't shrink in your tweet, it may be because you don't have CURL installed.

= Will there be more tags for the message field? =

Yes, more are planned to be added so the message can be customized further. Suggestions are welcome.

= Why do you only use TinyURL as a url shrink service? =

If you'd like more to be added just suggestion one.