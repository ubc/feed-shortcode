=== Feed Shortcode ===
Contributors: michaelha, ctlt-dev
Tags:
Requires at least: 3.4
Tested up to: 3.4
Stable tag: 1.0.1

==== Unit Test ====
Import unit-test.xml on your development server to test the shortcode

= Change Log =
Added month and year parameters so users can now specify which month and year to display. Does not work with UBC Events feeds. 
Examples:
[feed url="https://mednet.med.ubc.ca/_layouts/listfeed.aspx?List=0c3c99dc-49a7-40b2-884b-19a7fb5d0a9c&View=22cfbe51-46cf-41c1-a561-a8b6b48933cb" view="cal" month="2" year="2016"]
[feed url="https://mednet.med.ubc.ca/_layouts/listfeed.aspx?List=0c3c99dc-49a7-40b2-884b-19a7fb5d0a9c&View=22cfbe51-46cf-41c1-a561-a8b6b48933cb" view="cal" month="feb" year="2015"]
[feed url="https://mednet.med.ubc.ca/_layouts/listfeed.aspx?List=0c3c99dc-49a7-40b2-884b-19a7fb5d0a9c&View=22cfbe51-46cf-41c1-a561-a8b6b48933cb" view="cal" month="February"]

* Added the twitter shortcode that displays the tweets in easter a slider or list format. 
Examples : 
[twitter secret="" key="" user="enej" view="slider"]
[twitter secret="" key="" search="enej" view="list"] list view is the default view

Key and Secret can be obtained by going to https://dev.twitter.com/apps, login in and creating a new application



