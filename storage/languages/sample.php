<?php
/**
 * English language for the frontend
 */
return [
    /**
     * Language Information
     */
    "code" => "en",
    "region" => "en_US",
    "name" => "Sample",
    "author" => "GemPixel",
    "link" => "https://gempixel.com",
    "date" => "07/08/2022",
    "rtl" => false,
    /**
     * Language Data
     */
    "data" => [
        #: app/controllers/BlogController.php:54 storage/themes/default/blog.php:6
        #: storage/themes/default/partials/main_menu.php:88
        "Blog" => "",

        #: app/controllers/LinkController.php:57
        #: app/controllers/LinkController.php:444
        #: app/controllers/LinkController.php:468
        #: app/controllers/LinkController.php:491
        #: app/controllers/LinkController.php:520
        #: app/controllers/LinkController.php:547
        #: app/controllers/LinkController.php:576
        #: app/controllers/LinkController.php:604
        #: app/controllers/LinkController.php:648
        #: app/controllers/user/AccountController.php:44
        #: app/controllers/user/BioController.php:99
        #: app/controllers/user/BioController.php:185
        #: app/controllers/user/BioController.php:361
        #: app/controllers/user/BioController.php:390
        #: app/controllers/user/BioController.php:518
        #: app/controllers/user/BioController.php:708
        #: app/controllers/user/BioController.php:736
        #: app/controllers/user/BioController.php:806
        #: app/controllers/user/CampaignsController.php:79
        #: app/controllers/user/CampaignsController.php:123
        #: app/controllers/user/CampaignsController.php:170
        #: app/controllers/user/ChannelsController.php:152
        #: app/controllers/user/ChannelsController.php:184
        #: app/controllers/user/ChannelsController.php:216
        #: app/controllers/user/ChannelsController.php:301
        #: app/controllers/user/DomainsController.php:71
        #: app/controllers/user/DomainsController.php:97
        #: app/controllers/user/DomainsController.php:148
        #: app/controllers/user/DomainsController.php:175
        #: app/controllers/user/DomainsController.php:202
        #: app/controllers/user/ExportController.php:50
        #: app/controllers/user/ExportController.php:85
        #: app/controllers/user/ExportController.php:118
        #: app/controllers/user/ExportController.php:153
        #: app/controllers/user/OverlayController.php:78
        #: app/controllers/user/OverlayController.php:109
        #: app/controllers/user/OverlayController.php:136
        #: app/controllers/user/OverlayController.php:164
        #: app/controllers/user/OverlayController.php:1625
        #: app/controllers/user/PixelsController.php:73
        #: app/controllers/user/PixelsController.php:105
        #: app/controllers/user/PixelsController.php:159
        #: app/controllers/user/PixelsController.php:183
        #: app/controllers/user/PixelsController.php:224
        #: app/controllers/user/QRController.php:85
        #: app/controllers/user/QRController.php:219
        #: app/controllers/user/QRController.php:332
        #: app/controllers/user/QRController.php:407
        #: app/controllers/user/QRController.php:521
        #: app/controllers/user/QRController.php:554
        #: app/controllers/user/SplashController.php:74
        #: app/controllers/user/SplashController.php:100
        #: app/controllers/user/SplashController.php:175
        #: app/controllers/user/SplashController.php:201
        #: app/controllers/user/SplashController.php:281
        #: app/controllers/user/TeamsController.php:75
        "You do not have this permission. Please contact your team administrator." => "",

        #: app/controllers/LinkController.php:141
        "The password is invalid or does not match." => "",

        #: app/controllers/LinkController.php:448
        #: app/controllers/LinkController.php:918
        #: app/controllers/user/AccountController.php:441
        #: app/controllers/user/AccountController.php:449
        #: app/controllers/user/AccountController.php:474
        #: app/controllers/user/BioController.php:365
        #: app/controllers/user/CampaignsController.php:174
        #: app/controllers/user/ChannelsController.php:222
        #: app/controllers/user/DomainsController.php:207
        #: app/controllers/user/OverlayController.php:122
        #: app/controllers/user/OverlayController.php:149
        #: app/controllers/user/OverlayController.php:179
        #: app/controllers/user/OverlayController.php:1629
        #: app/controllers/user/PixelsController.php:228
        #: app/controllers/user/QRController.php:527
        #: app/controllers/user/TeamsController.php:172
        "An unexpected error occurred. Please try again." => "",

        #: app/controllers/LinkController.php:452
        "Link not found. Please try again." => "",

        #: app/controllers/LinkController.php:455
        "Link has been deleted." => "",

        #: app/controllers/LinkController.php:473
        "No link was selected. Please try again." => "",

        #: app/controllers/LinkController.php:479
        "Selected Links have been deleted." => "",

        #: app/controllers/LinkController.php:499
        #: app/controllers/LinkController.php:528
        #: app/controllers/LinkController.php:555
        #: app/controllers/LinkController.php:584
        #: app/controllers/LinkController.php:686
        #: app/controllers/user/ChannelsController.php:258
        "You need to select at least 1 link." => "",

        #: app/controllers/LinkController.php:507
        "Selected links have been archived." => "",

        #: app/controllers/LinkController.php:535
        "Selected links have been removed from archive." => "",

        #: app/controllers/LinkController.php:563
        "Selected links have been set to public." => "",

        #: app/controllers/LinkController.php:591
        "Selected links have been set to private." => "",

        #: app/controllers/LinkController.php:607
        #: app/controllers/LinkController.php:923
        #: app/controllers/user/ExportController.php:89
        "Link does not exist." => "",

        #: app/controllers/LinkController.php:609
        #: storage/themes/default/user/edit.php:4
        #: storage/themes/default/user/edit.php:7
        #: storage/themes/default/user/edit.php:231
        "Update Link" => "",

        #: app/controllers/LinkController.php:651
        "URL does not exist." => "",

        #: app/controllers/LinkController.php:663
        "Link has been updated successfully." => "",

        #: app/controllers/LinkController.php:675
        "Invalid campaign. Please choose a valid campaign." => "",

        #: app/controllers/LinkController.php:693
        "Selected links have been added to the {c} campaign." => "",

        #: app/controllers/LinkController.php:693
        "Selected links have been removed from campaigns." => "",

        #: app/controllers/LinkController.php:775
        "This has been disabled in demo." => "",

        #: app/controllers/LinkController.php:777
        "You need to be logged in to use this feature." => "",

        #: app/controllers/LinkController.php:889
        "You have to make your profile public or set a default bio for this page to be accessible." => "",

        #: app/controllers/LinkController.php:932
        "Statistics have been successfully reset." => "",

        #: app/controllers/PageController.php:76
        #: storage/themes/default/pages/contact.php:6
        #: storage/themes/default/partials/footer.php:80
        "Contact Us" => "",

        #: app/controllers/PageController.php:96
        #: app/controllers/PageController.php:159
        #: app/controllers/UsersController.php:269
        #: app/controllers/UsersController.php:353
        #: app/controllers/user/AccountController.php:322
        #: app/controllers/user/OverlayController.php:302
        #: app/controllers/user/OverlayController.php:462 app/helpers/Gate.php:383
        #: app/helpers/Gate.php:409 storage/themes/default/auth/register.php:30
        #: storage/themes/default/pages/contact.php:26
        #: storage/themes/default/pages/report.php:22
        "Please enter a valid email." => "",

        #: app/controllers/PageController.php:124
        "Your message has been sent. We will reply you as soon as possible." => "",

        #: app/controllers/PageController.php:140
        "Report Link" => "",

        #: app/controllers/PageController.php:167
        #: storage/themes/default/pages/report.php:26
        "Please enter a valid link." => "",

        #: app/controllers/PageController.php:204
        "Thank you. We will review this link and take action." => "",

        #: app/controllers/PageController.php:239
        #: storage/themes/default/pages/affiliate.php:22
        #: storage/themes/default/pages/faq.php:6
        #: storage/themes/default/pricing/index.php:99
        "Frequently Asked Questions" => "",

        #: app/controllers/PageController.php:254
        #: storage/themes/default/pages/api.php:49
        "API Reference for Developers" => "",

        #: app/controllers/PageController.php:299
        #: storage/themes/default/partials/footer.php:77
        "Affiliate Program" => "",

        #: app/controllers/PageController.php:312
        #: app/controllers/user/QRController.php:70
        #: storage/themes/default/index.php:311 storage/themes/default/pages/qr.php:6
        #: storage/themes/default/partials/footer.php:59
        #: storage/themes/default/partials/main_menu.php:58
        #: storage/themes/default/partials/sidebar_menu.php:17
        #: storage/themes/default/pricing/index.php:54
        #: storage/themes/default/qr/index.php:3
        #: storage/themes/default/qr/index.php:75
        #: storage/themes/default/teams/edit.php:19
        #: storage/themes/default/teams/index.php:127
        #: storage/themes/default/user/billing.php:78
        #: storage/themes/default/user/channel.php:17
        "QR Codes" => "",

        #: app/controllers/PageController.php:314 storage/themes/default/index.php:313
        #: storage/themes/default/pages/qr.php:9
        "Easy to use, dynamic and customizable QR codes for your marketing campaigns. Analyze statistics and optimize your marketing strategy and increase engagement." => "",

        #: app/controllers/PageController.php:327
        #: storage/themes/default/pages/bio.php:6
        #: storage/themes/default/partials/footer.php:60
        #: storage/themes/default/partials/main_menu.php:66
        #: storage/themes/default/pricing/index.php:53
        #: storage/themes/default/user/billing.php:77
        "Bio Profiles" => "",

        #: app/controllers/PageController.php:329
        #: storage/themes/default/pages/bio.php:9
        "Convert your followers by creating beautiful pages that group all of your important links on the single page." => "",

        #: app/controllers/PageController.php:347
        #: storage/themes/default/pages/consent.php:6
        "Cookie Policy Consent" => "",

        #: app/controllers/StatsController.php:62
        #: app/controllers/StatsController.php:67
        #: app/controllers/StatsController.php:242
        #: app/controllers/StatsController.php:247
        #: app/controllers/StatsController.php:471
        #: app/controllers/StatsController.php:476
        #: app/controllers/StatsController.php:628
        #: app/controllers/StatsController.php:633
        #: app/controllers/StatsController.php:783
        #: app/controllers/StatsController.php:788
        #: app/controllers/StatsController.php:939
        #: app/controllers/StatsController.php:944
        "This link is private and only the creator can access the stats. If you are the creator, please login to access it." => "",

        #: app/controllers/StatsController.php:90
        #: app/controllers/StatsController.php:268
        #: app/controllers/StatsController.php:497
        #: app/controllers/StatsController.php:654
        #: app/controllers/StatsController.php:809
        #: app/controllers/StatsController.php:965 app/helpers/App.php:716
        #: storage/themes/default/user/settings.php:84
        "Direct" => "",

        #: app/controllers/StatsController.php:94
        #: app/controllers/StatsController.php:99
        #: app/controllers/StatsController.php:104
        #: app/controllers/StatsController.php:275
        #: app/controllers/StatsController.php:504
        #: app/controllers/StatsController.php:661
        #: app/controllers/StatsController.php:816
        #: app/controllers/StatsController.php:987
        "Stats for" => "",

        #: app/controllers/StatsController.php:105
        "Advanced statistics page for the short URL" => "",

        #: app/controllers/StatsController.php:117
        #: app/controllers/StatsController.php:293
        #: app/controllers/StatsController.php:520
        #: app/controllers/StatsController.php:674
        #: app/controllers/StatsController.php:829
        #: app/controllers/user/CampaignsController.php:203
        #: app/controllers/user/StatsController.php:47
        #: storage/themes/default/pricing/checkout.php:100
        "Apply" => "",

        #: app/controllers/StatsController.php:118
        #: app/controllers/StatsController.php:294
        #: app/controllers/StatsController.php:521
        #: app/controllers/StatsController.php:675
        #: app/controllers/StatsController.php:830
        #: app/controllers/user/CampaignsController.php:204
        #: app/controllers/user/StatsController.php:48
        #: storage/themes/default/bio/index.php:109
        #: storage/themes/default/bio/index.php:127
        #: storage/themes/default/bio/index.php:154
        #: storage/themes/default/domains/index.php:94
        #: storage/themes/default/layouts/dashboard.php:129
        #: storage/themes/default/overlay/create.php:60
        #: storage/themes/default/overlay/index.php:94
        #: storage/themes/default/pixels/index.php:92
        #: storage/themes/default/qr/index.php:106
        #: storage/themes/default/qr/index.php:124
        #: storage/themes/default/qr/index.php:151
        #: storage/themes/default/splash/index.php:94
        #: storage/themes/default/teams/index.php:97
        #: storage/themes/default/teams/index.php:184
        #: storage/themes/default/user/campaigns.php:142
        #: storage/themes/default/user/campaigns.php:181
        #: storage/themes/default/user/campaigns.php:201
        #: storage/themes/default/user/campaignstats.php:111
        #: storage/themes/default/user/channel.php:66
        #: storage/themes/default/user/channels.php:101
        #: storage/themes/default/user/channels.php:143
        #: storage/themes/default/user/channels.php:163
        #: storage/themes/default/user/index.php:188
        #: storage/themes/default/user/index.php:205
        #: storage/themes/default/user/index.php:278
        #: storage/themes/default/user/index.php:306
        #: storage/themes/default/user/links.php:94
        #: storage/themes/default/user/links.php:111
        #: storage/themes/default/user/links.php:184
        #: storage/themes/default/user/links.php:212
        #: storage/themes/default/user/settings.php:239
        #: storage/themes/default/user/settings.php:260
        #: storage/themes/default/user/settings.php:292
        #: storage/themes/default/user/stats.php:70
        "Cancel" => "",

        #: app/controllers/StatsController.php:119
        #: app/controllers/StatsController.php:295
        #: app/controllers/StatsController.php:522
        #: app/controllers/StatsController.php:676
        #: app/controllers/StatsController.php:831
        #: app/controllers/user/CampaignsController.php:205
        #: app/controllers/user/StatsController.php:49
        "From" => "",

        #: app/controllers/StatsController.php:120
        #: app/controllers/StatsController.php:296
        #: app/controllers/StatsController.php:523
        #: app/controllers/StatsController.php:677
        #: app/controllers/StatsController.php:832
        #: app/controllers/user/CampaignsController.php:206
        #: app/controllers/user/StatsController.php:50
        "To" => "",

        #: app/controllers/StatsController.php:121
        #: app/controllers/StatsController.php:297
        #: app/controllers/StatsController.php:524
        #: app/controllers/StatsController.php:678
        #: app/controllers/StatsController.php:833
        #: app/controllers/user/CampaignsController.php:207
        #: app/controllers/user/StatsController.php:51
        #: storage/themes/default/index.php:26
        #: storage/themes/default/partials/shortener.php:60
        #: storage/themes/default/user/edit.php:298
        "Custom" => "",

        #: app/controllers/StatsController.php:122
        #: app/controllers/StatsController.php:298
        #: app/controllers/StatsController.php:525
        #: app/controllers/StatsController.php:679
        #: app/controllers/StatsController.php:834
        #: app/controllers/user/CampaignsController.php:208
        #: app/controllers/user/StatsController.php:52
        "Su" => "",

        #: app/controllers/StatsController.php:122
        #: app/controllers/StatsController.php:298
        #: app/controllers/StatsController.php:525
        #: app/controllers/StatsController.php:679
        #: app/controllers/StatsController.php:834
        #: app/controllers/user/CampaignsController.php:208
        #: app/controllers/user/StatsController.php:52
        "Mo" => "",

        #: app/controllers/StatsController.php:122
        #: app/controllers/StatsController.php:298
        #: app/controllers/StatsController.php:525
        #: app/controllers/StatsController.php:679
        #: app/controllers/StatsController.php:834
        #: app/controllers/user/CampaignsController.php:208
        #: app/controllers/user/StatsController.php:52
        "Tu" => "",

        #: app/controllers/StatsController.php:122
        #: app/controllers/StatsController.php:298
        #: app/controllers/StatsController.php:525
        #: app/controllers/StatsController.php:679
        #: app/controllers/StatsController.php:834
        #: app/controllers/user/CampaignsController.php:208
        #: app/controllers/user/StatsController.php:52
        "We" => "",

        #: app/controllers/StatsController.php:122
        #: app/controllers/StatsController.php:298
        #: app/controllers/StatsController.php:525
        #: app/controllers/StatsController.php:679
        #: app/controllers/StatsController.php:834
        #: app/controllers/user/CampaignsController.php:208
        #: app/controllers/user/StatsController.php:52
        "Th" => "",

        #: app/controllers/StatsController.php:122
        #: app/controllers/StatsController.php:298
        #: app/controllers/StatsController.php:525
        #: app/controllers/StatsController.php:679
        #: app/controllers/StatsController.php:834
        #: app/controllers/user/CampaignsController.php:208
        #: app/controllers/user/StatsController.php:52
        "Fr" => "",

        #: app/controllers/StatsController.php:122
        #: app/controllers/StatsController.php:298
        #: app/controllers/StatsController.php:525
        #: app/controllers/StatsController.php:679
        #: app/controllers/StatsController.php:834
        #: app/controllers/user/CampaignsController.php:208
        #: app/controllers/user/StatsController.php:52
        "Sa" => "",

        #: app/controllers/StatsController.php:123
        #: app/controllers/StatsController.php:299
        #: app/controllers/StatsController.php:526
        #: app/controllers/StatsController.php:680
        #: app/controllers/StatsController.php:835
        #: app/controllers/user/CampaignsController.php:209
        #: app/controllers/user/StatsController.php:53
        "January" => "",

        #: app/controllers/StatsController.php:123
        #: app/controllers/StatsController.php:299
        #: app/controllers/StatsController.php:526
        #: app/controllers/StatsController.php:680
        #: app/controllers/StatsController.php:835
        #: app/controllers/user/CampaignsController.php:209
        #: app/controllers/user/StatsController.php:53
        "February" => "",

        #: app/controllers/StatsController.php:123
        #: app/controllers/StatsController.php:299
        #: app/controllers/StatsController.php:526
        #: app/controllers/StatsController.php:680
        #: app/controllers/StatsController.php:835
        #: app/controllers/user/CampaignsController.php:209
        #: app/controllers/user/StatsController.php:53
        "March" => "",

        #: app/controllers/StatsController.php:123
        #: app/controllers/StatsController.php:299
        #: app/controllers/StatsController.php:526
        #: app/controllers/StatsController.php:680
        #: app/controllers/StatsController.php:835
        #: app/controllers/user/CampaignsController.php:209
        #: app/controllers/user/StatsController.php:53
        "April" => "",

        #: app/controllers/StatsController.php:123
        #: app/controllers/StatsController.php:299
        #: app/controllers/StatsController.php:526
        #: app/controllers/StatsController.php:680
        #: app/controllers/StatsController.php:835
        #: app/controllers/user/CampaignsController.php:209
        #: app/controllers/user/StatsController.php:53
        "May" => "",

        #: app/controllers/StatsController.php:123
        #: app/controllers/StatsController.php:299
        #: app/controllers/StatsController.php:526
        #: app/controllers/StatsController.php:680
        #: app/controllers/StatsController.php:835
        #: app/controllers/user/CampaignsController.php:209
        #: app/controllers/user/StatsController.php:53
        "June" => "",

        #: app/controllers/StatsController.php:123
        #: app/controllers/StatsController.php:299
        #: app/controllers/StatsController.php:526
        #: app/controllers/StatsController.php:680
        #: app/controllers/StatsController.php:835
        #: app/controllers/user/CampaignsController.php:209
        #: app/controllers/user/StatsController.php:53
        "July" => "",

        #: app/controllers/StatsController.php:123
        #: app/controllers/StatsController.php:299
        #: app/controllers/StatsController.php:526
        #: app/controllers/StatsController.php:680
        #: app/controllers/StatsController.php:835
        #: app/controllers/user/CampaignsController.php:209
        #: app/controllers/user/StatsController.php:53
        "August" => "",

        #: app/controllers/StatsController.php:123
        #: app/controllers/StatsController.php:299
        #: app/controllers/StatsController.php:526
        #: app/controllers/StatsController.php:680
        #: app/controllers/StatsController.php:835
        #: app/controllers/user/CampaignsController.php:209
        #: app/controllers/user/StatsController.php:53
        "September" => "",

        #: app/controllers/StatsController.php:123
        #: app/controllers/StatsController.php:299
        #: app/controllers/StatsController.php:526
        #: app/controllers/StatsController.php:680
        #: app/controllers/StatsController.php:835
        #: app/controllers/user/CampaignsController.php:209
        #: app/controllers/user/StatsController.php:53
        "October" => "",

        #: app/controllers/StatsController.php:123
        #: app/controllers/StatsController.php:299
        #: app/controllers/StatsController.php:526
        #: app/controllers/StatsController.php:680
        #: app/controllers/StatsController.php:835
        #: app/controllers/user/CampaignsController.php:209
        #: app/controllers/user/StatsController.php:53
        "November" => "",

        #: app/controllers/StatsController.php:123
        #: app/controllers/StatsController.php:299
        #: app/controllers/StatsController.php:526
        #: app/controllers/StatsController.php:680
        #: app/controllers/StatsController.php:835
        #: app/controllers/user/CampaignsController.php:209
        #: app/controllers/user/StatsController.php:53
        "December" => "",

        #: app/controllers/StatsController.php:131
        #: app/controllers/StatsController.php:307
        #: app/controllers/StatsController.php:534
        #: app/controllers/StatsController.php:688
        #: app/controllers/StatsController.php:843
        #: app/controllers/user/CampaignsController.php:216
        #: app/controllers/user/StatsController.php:60
        "Last 7 Days" => "",

        #: app/controllers/StatsController.php:132
        #: app/controllers/StatsController.php:308
        #: app/controllers/StatsController.php:535
        #: app/controllers/StatsController.php:689
        #: app/controllers/StatsController.php:844
        #: app/controllers/user/CampaignsController.php:217
        #: app/controllers/user/StatsController.php:61
        "Last 30 Days" => "",

        #: app/controllers/StatsController.php:133
        #: app/controllers/StatsController.php:309
        #: app/controllers/StatsController.php:536
        #: app/controllers/StatsController.php:690
        #: app/controllers/StatsController.php:845
        #: app/controllers/user/CampaignsController.php:218
        #: app/controllers/user/StatsController.php:62
        "This Month" => "",

        #: app/controllers/StatsController.php:134
        #: app/controllers/StatsController.php:310
        #: app/controllers/StatsController.php:537
        #: app/controllers/StatsController.php:691
        #: app/controllers/StatsController.php:846
        #: app/controllers/user/CampaignsController.php:219
        #: app/controllers/user/StatsController.php:63
        "Last Month" => "",

        #: app/controllers/StatsController.php:135
        #: app/controllers/StatsController.php:311
        #: app/controllers/StatsController.php:538
        #: app/controllers/StatsController.php:692
        #: app/controllers/StatsController.php:847
        #: app/controllers/user/CampaignsController.php:220
        #: app/controllers/user/StatsController.php:64
        "Last 3 Months" => "",

        #: app/controllers/StatsController.php:166
        #: app/controllers/StatsController.php:449
        #: app/controllers/user/CampaignsController.php:255
        #: app/controllers/user/DashboardController.php:204
        #: app/controllers/user/StatsController.php:110
        #: storage/themes/default/index.php:645
        #: storage/themes/default/partials/links.php:83
        #: storage/themes/default/stats/index.php:18
        #: storage/themes/default/stats/partial.php:32
        #: storage/themes/default/user/campaignstats.php:37
        #: storage/themes/default/user/index.php:18
        #: storage/themes/default/user/stats.php:24
        "Clicks" => "",

        #: app/controllers/StatsController.php:271
        #: app/controllers/StatsController.php:278
        "Country Stats for" => "",

        #: app/controllers/StatsController.php:281
        #: app/controllers/StatsController.php:993
        "Country statistics page for the short URL" => "",

        #: app/controllers/StatsController.php:386
        #: app/controllers/StatsController.php:914
        #: storage/themes/default/stats/partial.php:47
        #: storage/themes/default/stats/partial.php:55
        "Unknown" => "",

        #: app/controllers/StatsController.php:445
        #: storage/themes/default/stats/index.php:44
        #: storage/themes/default/user/index.php:147
        "Somewhere from" => "",

        #: app/controllers/StatsController.php:500
        #: app/controllers/StatsController.php:507
        "Platform Stats for" => "",

        #: app/controllers/StatsController.php:509
        "Platform statistics page for the short URL" => "",

        #: app/controllers/StatsController.php:657
        #: app/controllers/StatsController.php:664
        "Browser Stats for" => "",

        #: app/controllers/StatsController.php:666
        "Browser statistics page for the short URL" => "",

        #: app/controllers/StatsController.php:812
        #: app/controllers/StatsController.php:819
        "Language Stats for" => "",

        #: app/controllers/StatsController.php:821
        "Language statistics page for the short URL" => "",

        #: app/controllers/StatsController.php:983
        #: app/controllers/StatsController.php:990
        "Referrers Stats for" => "",

        #: app/controllers/SubscriptionController.php:86
        #: app/controllers/SubscriptionController.php:143
        "Current" => "",

        #: app/controllers/SubscriptionController.php:89
        #: app/controllers/SubscriptionController.php:93
        #: app/controllers/SubscriptionController.php:146
        #: app/controllers/SubscriptionController.php:150
        "{d}-Day Free Trial" => "",

        #: app/controllers/SubscriptionController.php:89
        #: app/controllers/SubscriptionController.php:146
        #: storage/themes/default/partials/topbar_menu.php:9
        "Upgrade" => "",

        #: app/controllers/SubscriptionController.php:93
        #: app/controllers/SubscriptionController.php:150
        #: storage/themes/default/index.php:88 storage/themes/default/index.php:316
        #: storage/themes/default/index.php:396 storage/themes/default/index.php:587
        #: storage/themes/default/pages/bio.php:12
        #: storage/themes/default/pages/bio.php:46
        #: storage/themes/default/pages/bio.php:64
        #: storage/themes/default/pages/qr.php:12
        #: storage/themes/default/pages/qr.php:29
        #: storage/themes/default/pages/qr.php:47
        #: storage/themes/default/partials/footer.php:15
        #: storage/themes/default/partials/main_menu.php:135
        #: storage/themes/default/partials/main_menu.php:152
        "Get Started" => "",

        #: app/controllers/SubscriptionController.php:163
        "Premium Plan Pricing" => "",

        #: app/controllers/SubscriptionController.php:199 app/helpers/Gate.php:421
        #: app/helpers/payments/Stripe.php:444
        "You have been successfully subscribed." => "",

        #: app/controllers/SubscriptionController.php:205
        "You have already used a trial." => "",

        #: app/controllers/SubscriptionController.php:234
        "You already subscribed to this plan. If you want to upgrade, please choose another plan." => "",

        #: app/controllers/SubscriptionController.php:330
        "Promo code has expired. Please try again." => "",

        #: app/controllers/SubscriptionController.php:333
        #: app/controllers/SubscriptionController.php:352
        #: app/controllers/SubscriptionController.php:367
        "Please enter a valid promo code." => "",

        #: app/controllers/UsersController.php:50
        #: storage/themes/default/gates/domain.php:14
        "Login to your account" => "",

        #: app/controllers/UsersController.php:71
        "You have been blocked for 1 hour due to many unsuccessful login attempts." => "",

        #: app/controllers/UsersController.php:74
        "Please enter a valid email or username." => "",

        #: app/controllers/UsersController.php:76
        #: app/controllers/UsersController.php:86
        #: app/controllers/UsersController.php:165 core/Auth.class.php:123
        #: core/Auth.class.php:196
        "Wrong email and password combination." => "",

        #: app/controllers/UsersController.php:103
        #: app/controllers/UsersController.php:605
        #: app/controllers/UsersController.php:682
        #: app/controllers/UsersController.php:799
        "You have been banned due to abuse. Please contact us for clarification." => "",

        #: app/controllers/UsersController.php:107
        #: app/controllers/UsersController.php:609
        #: app/controllers/UsersController.php:686
        #: app/controllers/UsersController.php:803
        "You haven't activated your account. Please check your email for the activation link. If you haven't received any emails from us, please contact us." => "",

        #: app/controllers/UsersController.php:129
        "Please enter the 2FA access code to login." => "",

        #: app/controllers/UsersController.php:152
        #: app/controllers/UsersController.php:322
        #: app/controllers/UsersController.php:325
        "You have been successfully registered." => "",

        #: app/controllers/UsersController.php:178
        #: storage/themes/default/auth/2fa.php:14
        "Enter your 2FA access code" => "",

        #: app/controllers/UsersController.php:199
        #: app/controllers/UsersController.php:204
        #: app/controllers/UsersController.php:208
        #: app/controllers/user/AccountController.php:454
        "Invalid token. Please try again." => "",

        #: app/controllers/UsersController.php:225
        "You have been successfully logged." => "",

        #: app/controllers/UsersController.php:240
        #: app/controllers/UsersController.php:260
        "We are not accepting users at this time." => "",

        #: app/controllers/UsersController.php:242
        "Register and manage your urls" => "",

        #: app/controllers/UsersController.php:243
        "Register an account and gain control over your urls. Manage them, edit them or remove them without hassle." => "",

        #: app/controllers/UsersController.php:265
        "The email, the username and the password are required." => "",

        #: app/controllers/UsersController.php:271
        #: app/controllers/user/AccountController.php:327
        "An account is already associated with this email." => "",

        #: app/controllers/UsersController.php:275
        #: app/controllers/UsersController.php:500
        #: app/controllers/user/AccountController.php:340
        "Please enter a valid username." => "",

        #: app/controllers/UsersController.php:276
        #: app/controllers/UsersController.php:502
        "Username already exists." => "",

        #: app/controllers/UsersController.php:280
        #: app/controllers/UsersController.php:506
        "This username cannot be used or already exists. Please choose another username" => "",

        #: app/controllers/UsersController.php:282
        #: app/controllers/UsersController.php:422
        #: app/controllers/UsersController.php:508
        "Password must be at least 5 characters." => "",

        #: app/controllers/UsersController.php:284
        #: app/controllers/UsersController.php:424
        #: app/controllers/UsersController.php:510
        #: app/controllers/user/AccountController.php:354
        "Passwords don't match." => "",

        #: app/controllers/UsersController.php:286
        #: app/controllers/UsersController.php:512
        "You must agree to our terms of service." => "",

        #: app/controllers/UsersController.php:314
        "An email has been sent to activate your account. Please check your spam folder if you didn't receive it." => "",

        #: app/controllers/UsersController.php:337
        #: app/controllers/UsersController.php:392
        #: storage/themes/default/auth/forgot.php:14
        #: storage/themes/default/auth/forgot.php:32
        #: storage/themes/default/auth/reset.php:14
        #: storage/themes/default/auth/reset.php:39
        "Reset Password" => "",

        #: app/controllers/UsersController.php:338
        #: storage/themes/default/auth/forgot.php:15
        "If you forgot your password, you can request a link to reset your password." => "",

        #: app/controllers/UsersController.php:363
        "If an active account is associated with this email, you should receive an email shortly." => "",

        #: app/controllers/UsersController.php:378
        #: app/controllers/UsersController.php:385
        #: app/controllers/UsersController.php:389
        #: app/controllers/UsersController.php:408
        #: app/controllers/UsersController.php:415
        #: app/controllers/UsersController.php:419
        #: app/controllers/UsersController.php:452
        "Token has expired, please request another link." => "",

        #: app/controllers/UsersController.php:427
        "Your new password cannot be the same as the old password." => "",

        #: app/controllers/UsersController.php:438
        "Your password has been changed." => "",

        #: app/controllers/UsersController.php:462
        "Your email has been successfully verified." => "",

        #: app/controllers/UsersController.php:476
        #: app/controllers/UsersController.php:497
        "The invitation link has expired or is currently unavailable. Please contact administrator." => "",

        #: app/controllers/UsersController.php:479
        #: storage/themes/default/auth/invite.php:14
        "Join Team" => "",

        #: app/controllers/UsersController.php:522
        "Your account has been successfully activated." => "",

        #: app/controllers/UsersController.php:533
        "You have been successfully logged out." => "",

        #: app/controllers/UsersController.php:546
        "Sorry, Facebook connect is not available right now." => "",

        #: app/controllers/UsersController.php:548
        "You must grant access to this application to use your facebook account." => "",

        #: app/controllers/UsersController.php:565
        #: app/controllers/UsersController.php:570
        "An error has occurred. Please try again later." => "",

        #: app/controllers/UsersController.php:580
        "You must grant permission to this application to use your profile information." => "",

        #: app/controllers/UsersController.php:584
        #: app/controllers/UsersController.php:667
        #: app/controllers/UsersController.php:784
        "The email linked to your account has been already used. If you have used that, please login to your existing account otherwise please contact us." => "",

        #: app/controllers/UsersController.php:635
        #: app/controllers/UsersController.php:717
        #: app/controllers/UsersController.php:835
        #: app/controllers/UsersController.php:868
        #: app/controllers/UsersController.php:893
        "Welcome! You have been successfully logged in." => "",

        #: app/controllers/UsersController.php:646
        #: app/controllers/UsersController.php:730
        "Sorry, Twitter connect is not available right now." => "",

        #: app/controllers/UsersController.php:649
        "You must grant permission to this application to use your twitter account." => "",

        #: app/controllers/UsersController.php:664
        "And error occurred, please try again later." => "",

        #: app/controllers/UsersController.php:747
        "An error has occurred! Please make sure that you have set up this application as instructed." => "",

        #: app/controllers/UsersController.php:760
        #: app/controllers/UsersController.php:775
        "Sorry, Google connect is not available right now." => "",

        #: app/controllers/UsersController.php:781
        "You must grant permission to this application to use your Google account." => "",

        #: app/controllers/UsersController.php:849
        "Token has expired, please login manually." => "",

        #: app/controllers/UsersController.php:856
        #: app/controllers/UsersController.php:860
        "Token has expired, please login manually" => "",

        #: app/controllers/api/LinksController.php:177
        #: app/controllers/api/QRController.php:180
        "Direct, email and other" => "",

        #: app/controllers/user/AccountController.php:56
        #: storage/themes/default/partials/main_menu.php:114
        #: storage/themes/default/partials/topbar_menu.php:61
        #: storage/themes/default/user/billing.php:1
        "Billing" => "",

        #: app/controllers/user/AccountController.php:73
        "Wow there. You are an admin. You can't cancel your membership." => "",

        #: app/controllers/user/AccountController.php:77 app/helpers/Slack.php:105
        "Something went wrong, please try again." => "",

        #: app/controllers/user/AccountController.php:81
        #: app/controllers/user/AccountController.php:87
        #: app/controllers/user/AccountController.php:185
        #: app/controllers/user/AccountController.php:191
        "Your password is incorrect." => "",

        #: app/controllers/user/AccountController.php:95
        "Your trial has been canceled." => "",

        #: app/controllers/user/AccountController.php:129
        "Your subscription has been canceled." => "",

        #: app/controllers/user/AccountController.php:145
        "Payment not found. Please try again." => "",

        #: app/controllers/user/AccountController.php:160
        #: storage/themes/default/user/billing.php:57
        "View Invoice" => "",

        #: app/controllers/user/AccountController.php:181
        "As an admin, you cannot delete your account form this page." => "",

        #: app/controllers/user/AccountController.php:227
        "Your account has been deleted successfully and your data has been wiped out. If you have any questions please don't hesitate to contact us." => "",

        #: app/controllers/user/AccountController.php:231
        "Your account has been terminated." => "",

        #: app/controllers/user/AccountController.php:254
        "Your account has been successfully terminated." => "",

        #: app/controllers/user/AccountController.php:303
        #: storage/themes/default/partials/main_menu.php:119
        #: storage/themes/default/partials/topbar_menu.php:66
        #: storage/themes/default/user/settings.php:1
        "Settings" => "",

        #: app/controllers/user/AccountController.php:342
        "This username has already been used. Please try again." => "",

        #: app/controllers/user/AccountController.php:352
        "Password must contain at least 5 characters." => "",

        #: app/controllers/user/AccountController.php:358
        "Passwords is the same as the old password." => "",

        #: app/controllers/user/AccountController.php:370
        #: app/controllers/user/AccountController.php:372
        #: app/controllers/user/BioController.php:227
        #: app/controllers/user/BioController.php:577
        "Avatar must be either a PNG or a JPEG (Max 500kb)." => "",

        #: app/controllers/user/AccountController.php:376
        #: app/controllers/user/SplashController.php:131
        #: app/controllers/user/SplashController.php:232
        "Avatar must be either a PNG or a JPEG with a recommended dimension of 200x200." => "",

        #: app/controllers/user/AccountController.php:416
        #: app/controllers/user/AccountController.php:425
        "Account has been successfully updated." => "",

        #: app/controllers/user/AccountController.php:416
        "You have changed your email. Please check your email before logging out and activate your account." => "",

        #: app/controllers/user/AccountController.php:460
        "2FA has been activated on your account. Please make sure to backup the secret key or the QR code." => "",

        #: app/controllers/user/AccountController.php:471
        "2FA has been disabled on your account." => "",

        #: app/controllers/user/AccountController.php:493
        "API key has been regenerated successfully. Please do not forget to update your application." => "",

        #: app/controllers/user/BioController.php:78
        "Import" => "",

        #: app/controllers/user/BioController.php:78
        "We have detected that you have an old bio page. Do you want to import it?<br><br><a href=\\\"?importoldbio=true\\\" class=\\\"btn btn-primary\\\">" => "",

        #: app/controllers/user/BioController.php:81
        #: storage/themes/default/bio/index.php:3
        #: storage/themes/default/bio/index.php:77
        #: storage/themes/default/partials/sidebar_menu.php:10
        #: storage/themes/default/teams/edit.php:26
        #: storage/themes/default/teams/index.php:134
        #: storage/themes/default/user/channel.php:15
        "Bio Pages" => "",

        #: app/controllers/user/BioController.php:113
        #: storage/themes/default/bio/index.php:7
        #: storage/themes/default/bio/index.php:67
        #: storage/themes/default/bio/new.php:3
        #: storage/themes/default/teams/edit.php:27
        #: storage/themes/default/teams/index.php:135
        "Create Bio" => "",

        #: app/controllers/user/BioController.php:118
        #: app/controllers/user/BioController.php:438
        "Icon" => "",

        #: app/controllers/user/BioController.php:119
        #: app/controllers/user/BioController.php:439
        #: storage/themes/default/bio/edit.php:382
        #: storage/themes/default/bio/edit.php:477
        #: storage/themes/default/bio/edit.php:480
        #: storage/themes/default/bio/edit.php:493
        #: storage/themes/default/bio/edit.php:612
        #: storage/themes/default/bio/edit.php:703
        #: storage/themes/default/bio/edit.php:712
        #: storage/themes/default/bio/new.php:336
        #: storage/themes/default/bio/new.php:431
        #: storage/themes/default/bio/new.php:434
        #: storage/themes/default/bio/new.php:447
        #: storage/themes/default/bio/new.php:566
        #: storage/themes/default/bio/new.php:657
        #: storage/themes/default/bio/new.php:666
        #: storage/themes/default/qr/edit.php:30 storage/themes/default/qr/new.php:12
        #: storage/themes/default/qr/new.php:57
        "Text" => "",

        #: app/controllers/user/BioController.php:120
        #: app/controllers/user/BioController.php:440
        #: storage/themes/default/invoice.php:57
        #: storage/themes/default/overlay/create_coupon.php:87
        #: storage/themes/default/overlay/create_newsletter.php:108
        #: storage/themes/default/overlay/edit_newsletter.php:108
        #: storage/themes/default/pages/api.php:162
        #: storage/themes/default/partials/shortener.php:93
        #: storage/themes/default/user/channels.php:83
        #: storage/themes/default/user/channels.php:125
        #: storage/themes/default/user/edit.php:320
        "Description" => "",

        #: app/controllers/user/BioController.php:121
        #: app/controllers/user/BioController.php:441
        #: storage/themes/default/bio/edit.php:497
        #: storage/themes/default/bio/new.php:451
        #: storage/themes/default/overlay/create_image.php:18
        #: storage/themes/default/overlay/edit_image.php:18
        #: storage/themes/default/qr/edit.php:43 storage/themes/default/qr/new.php:24
        #: storage/themes/default/qr/new.php:68
        "Link" => "",

        #: app/controllers/user/BioController.php:122
        #: app/controllers/user/BioController.php:442
        #: storage/themes/default/bio/edit.php:503
        #: storage/themes/default/bio/edit.php:616
        #: storage/themes/default/bio/edit.php:642
        #: storage/themes/default/bio/new.php:457
        #: storage/themes/default/bio/new.php:570
        #: storage/themes/default/bio/new.php:596
        "Color" => "",

        #: app/controllers/user/BioController.php:123
        #: app/controllers/user/BioController.php:443
        #: storage/themes/default/bio/edit.php:198
        #: storage/themes/default/bio/edit.php:210
        #: storage/themes/default/bio/edit.php:509
        #: storage/themes/default/bio/new.php:199
        #: storage/themes/default/bio/new.php:212
        #: storage/themes/default/bio/new.php:463
        #: storage/themes/default/qr/edit.php:377
        #: storage/themes/default/qr/edit.php:395
        #: storage/themes/default/qr/new.php:384 storage/themes/default/qr/new.php:402
        "Background" => "",

        #: app/controllers/user/BioController.php:124
        #: app/controllers/user/BioController.php:444
        #: storage/themes/default/bio/edit.php:515
        #: storage/themes/default/bio/new.php:469
        "Style" => "",

        #: app/controllers/user/BioController.php:125
        #: app/controllers/user/BioController.php:445
        #: storage/themes/default/bio/edit.php:517
        #: storage/themes/default/bio/new.php:471
        "Rectangular" => "",

        #: app/controllers/user/BioController.php:126
        #: app/controllers/user/BioController.php:446
        #: storage/themes/default/bio/edit.php:518
        #: storage/themes/default/bio/new.php:472
        #: storage/themes/default/qr/edit.php:490
        #: storage/themes/default/qr/new.php:497
        "Rounded" => "",

        #: app/controllers/user/BioController.php:127
        #: app/controllers/user/BioController.php:447
        #: app/controllers/user/OverlayController.php:290
        #: app/controllers/user/OverlayController.php:450
        #: storage/themes/default/auth/forgot.php:22
        #: storage/themes/default/auth/invite.php:22
        #: storage/themes/default/bio/edit.php:670
        #: storage/themes/default/bio/edit.php:716
        #: storage/themes/default/bio/new.php:624
        #: storage/themes/default/bio/new.php:670
        #: storage/themes/default/gates/profile.php:128
        #: storage/themes/default/overlay/create_contact.php:69
        #: storage/themes/default/overlay/create_contact.php:162
        #: storage/themes/default/pages/contact.php:25
        #: storage/themes/default/pages/contact.php:26
        #: storage/themes/default/pages/report.php:21
        #: storage/themes/default/pages/report.php:22
        #: storage/themes/default/qr/edit.php:56 storage/themes/default/qr/edit.php:60
        #: storage/themes/default/qr/edit.php:157
        #: storage/themes/default/qr/edit.php:236 storage/themes/default/qr/new.php:25
        #: storage/themes/default/qr/new.php:79 storage/themes/default/qr/new.php:83
        #: storage/themes/default/qr/new.php:183 storage/themes/default/qr/new.php:260
        #: storage/themes/default/teams/edit.php:7
        #: storage/themes/default/teams/index.php:115
        #: storage/themes/default/user/settings.php:34
        "Email" => "",

        #: app/controllers/user/BioController.php:128
        #: app/controllers/user/BioController.php:448
        #: storage/themes/default/bio/edit.php:580
        #: storage/themes/default/bio/new.php:534
        #: storage/themes/default/invoice.php:59
        #: storage/themes/default/user/billing.php:14
        #: storage/themes/default/user/billing.php:44
        "Amount" => "",

        #: app/controllers/user/BioController.php:129
        #: app/controllers/user/BioController.php:449
        #: storage/themes/default/bio/edit.php:584
        #: storage/themes/default/bio/new.php:538
        "Currency" => "",

        #: app/controllers/user/BioController.php:130
        #: app/controllers/user/BioController.php:134
        #: app/controllers/user/BioController.php:450
        #: app/controllers/user/BioController.php:454
        #: storage/themes/default/bio/edit.php:666
        #: storage/themes/default/bio/new.php:620
        #: storage/themes/default/qr/edit.php:77
        #: storage/themes/default/qr/edit.php:153
        #: storage/themes/default/qr/edit.php:232 storage/themes/default/qr/new.php:26
        #: storage/themes/default/qr/new.php:98 storage/themes/default/qr/new.php:179
        #: storage/themes/default/qr/new.php:256
        "Phone" => "",

        #: app/controllers/user/BioController.php:131
        #: app/controllers/user/BioController.php:451
        #: storage/themes/default/bio/edit.php:203
        #: storage/themes/default/bio/edit.php:406
        #: storage/themes/default/bio/edit.php:525
        #: storage/themes/default/bio/edit.php:528
        #: storage/themes/default/bio/new.php:204
        #: storage/themes/default/bio/new.php:360
        #: storage/themes/default/bio/new.php:479
        #: storage/themes/default/bio/new.php:482
        "Image" => "",

        #: app/controllers/user/BioController.php:132
        #: app/controllers/user/BioController.php:452
        #: storage/themes/default/bio/edit.php:658
        #: storage/themes/default/bio/new.php:612
        #: storage/themes/default/qr/edit.php:141
        #: storage/themes/default/qr/edit.php:220
        #: storage/themes/default/qr/new.php:167 storage/themes/default/qr/new.php:244
        "First Name" => "",

        #: app/controllers/user/BioController.php:133
        #: app/controllers/user/BioController.php:453
        #: storage/themes/default/bio/edit.php:662
        #: storage/themes/default/bio/new.php:616
        #: storage/themes/default/qr/edit.php:145
        #: storage/themes/default/qr/edit.php:224
        #: storage/themes/default/qr/new.php:171 storage/themes/default/qr/new.php:248
        "Last Name" => "",

        #: app/controllers/user/BioController.php:135
        #: app/controllers/user/BioController.php:455
        #: storage/themes/default/bio/edit.php:674
        #: storage/themes/default/bio/new.php:628
        "Site" => "",

        #: app/controllers/user/BioController.php:136
        #: app/controllers/user/BioController.php:456
        #: storage/themes/default/bio/edit.php:678
        #: storage/themes/default/bio/new.php:632
        #: storage/themes/default/pricing/checkout.php:34
        #: storage/themes/default/qr/edit.php:165
        #: storage/themes/default/qr/edit.php:244
        #: storage/themes/default/qr/new.php:191 storage/themes/default/qr/new.php:268
        #: storage/themes/default/user/settings.php:100
        "Address" => "",

        #: app/controllers/user/BioController.php:137
        #: app/controllers/user/BioController.php:457
        #: storage/themes/default/bio/edit.php:682
        #: storage/themes/default/bio/new.php:636
        #: storage/themes/default/pricing/checkout.php:40
        #: storage/themes/default/qr/edit.php:175
        #: storage/themes/default/qr/edit.php:254
        #: storage/themes/default/qr/new.php:201 storage/themes/default/qr/new.php:278
        #: storage/themes/default/user/settings.php:106
        "City" => "",

        #: app/controllers/user/BioController.php:138
        #: app/controllers/user/BioController.php:458
        #: storage/themes/default/bio/edit.php:686
        #: storage/themes/default/bio/new.php:640
        #: storage/themes/default/qr/edit.php:179
        #: storage/themes/default/qr/edit.php:258
        #: storage/themes/default/qr/new.php:205 storage/themes/default/qr/new.php:282
        "State" => "",

        #: app/controllers/user/BioController.php:139
        #: app/controllers/user/BioController.php:459
        #: storage/themes/default/bio/edit.php:690
        #: storage/themes/default/bio/new.php:644
        #: storage/themes/default/pricing/checkout.php:54
        #: storage/themes/default/qr/edit.php:187
        #: storage/themes/default/qr/edit.php:266
        #: storage/themes/default/qr/new.php:213 storage/themes/default/qr/new.php:290
        #: storage/themes/default/user/settings.php:120
        "Country" => "",

        #: app/controllers/user/BioController.php:140
        #: app/controllers/user/BioController.php:460
        #: storage/themes/default/bio/edit.php:625
        #: storage/themes/default/bio/new.php:579
        "Solid" => "",

        #: app/controllers/user/BioController.php:141
        #: app/controllers/user/BioController.php:461
        #: storage/themes/default/bio/edit.php:626
        #: storage/themes/default/bio/new.php:580
        "Dotted" => "",

        #: app/controllers/user/BioController.php:142
        #: app/controllers/user/BioController.php:462
        #: storage/themes/default/bio/edit.php:627
        #: storage/themes/default/bio/new.php:581
        "Dashed" => "",

        #: app/controllers/user/BioController.php:196
        #: app/controllers/user/BioController.php:527
        "Please enter a name for your profile." => "",

        #: app/controllers/user/BioController.php:201
        #: app/controllers/user/BioController.php:532
        "Please add at least one link." => "",

        #: app/controllers/user/BioController.php:206
        #: app/controllers/user/BioController.php:539 app/traits/Links.php:135
        #: app/traits/Links.php:463
        "Custom alias must be at least 3 characters." => "",

        #: app/controllers/user/BioController.php:209
        #: app/controllers/user/BioController.php:542 app/traits/Links.php:138
        #: app/traits/Links.php:467
        "Inappropriate aliases are not allowed." => "",

        #: app/controllers/user/BioController.php:212
        #: app/controllers/user/BioController.php:215
        #: app/controllers/user/BioController.php:545
        #: app/controllers/user/BioController.php:548 app/traits/Links.php:141
        #: app/traits/Links.php:144 app/traits/Links.php:470 app/traits/Links.php:473
        "That alias is taken. Please choose another one." => "",

        #: app/controllers/user/BioController.php:218
        #: app/controllers/user/BioController.php:551 app/traits/Links.php:147
        #: app/traits/Links.php:476
        "That alias is reserved. Please choose another one." => "",

        #: app/controllers/user/BioController.php:221
        #: app/controllers/user/BioController.php:554 app/traits/Links.php:150
        #: app/traits/Links.php:479
        "That is a premium alias and is reserved to only pro members." => "",

        #: app/controllers/user/BioController.php:240
        #: app/controllers/user/BioController.php:593
        "Background must be either a PNG or a JPEG (Max 1mb)." => "",

        #: app/controllers/user/BioController.php:273
        #: app/controllers/user/BioController.php:283
        #: app/controllers/user/BioController.php:644
        #: app/controllers/user/BioController.php:658
        "Image must be either a PNG or a JPEG (Max 500kb)." => "",

        #: app/controllers/user/BioController.php:346
        "Profile has been successfully created." => "",

        #: app/controllers/user/BioController.php:369
        #: app/controllers/user/BioController.php:394
        #: app/controllers/user/BioController.php:522
        #: app/controllers/user/BioController.php:714
        #: app/controllers/user/BioController.php:818
        "Profile does not exist." => "",

        #: app/controllers/user/BioController.php:377
        "Profile has been successfully deleted." => "",

        #: app/controllers/user/BioController.php:429
        #: storage/themes/default/bio/edit.php:4
        "Update Bio" => "",

        #: app/controllers/user/BioController.php:695
        "Profile has been successfully updated." => "",

        #: app/controllers/user/BioController.php:721
        "Profile has been set as default and can now be access via your profile page." => "",

        #: app/controllers/user/BioController.php:723
        "Profile has been set as default and can now be access via your profile page. Your profile setting is currently set on private." => "",

        #: app/controllers/user/BioController.php:825
        #: app/controllers/user/QRController.php:587 app/traits/Overlays.php:355
        #: storage/themes/default/bio/edit.php:337
        #: storage/themes/default/bio/index.php:44
        #: storage/themes/default/gates/media.php:29
        #: storage/themes/default/index.php:17
        #: storage/themes/default/overlay/create_coupon.php:35
        #: storage/themes/default/overlay/create_coupon.php:93
        #: storage/themes/default/overlay/edit_coupon.php:35
        #: storage/themes/default/partials/links.php:79
        #: storage/themes/default/user/affiliate.php:12
        #: storage/themes/default/user/campaigns.php:38
        #: storage/themes/default/user/campaigns.php:45
        #: storage/themes/default/user/channel.php:41
        #: storage/themes/default/user/edit.php:253
        #: storage/themes/default/user/edit.php:256
        #: storage/themes/default/user/index.php:240
        #: storage/themes/default/user/links.php:146
        #: storage/themes/default/user/settings.php:192
        #: storage/themes/default/user/settings.php:207
        #: storage/themes/default/user/settings.php:282
        "Copy" => "",

        #: app/controllers/user/BioController.php:846
        #: app/controllers/user/QRController.php:598
        "Item has been successfully duplicated." => "",

        #: app/controllers/user/CampaignsController.php:60
        #: storage/themes/default/partials/sidebar_menu.php:58
        #: storage/themes/default/teams/edit.php:60
        #: storage/themes/default/teams/index.php:168
        #: storage/themes/default/user/campaigns.php:3
        #: storage/themes/default/user/index.php:266
        #: storage/themes/default/user/links.php:172
        "Campaigns" => "",

        #: app/controllers/user/CampaignsController.php:83
        #: app/controllers/user/CampaignsController.php:131
        "Campaign name cannot be empty and must have at least 2 characters." => "",

        #: app/controllers/user/CampaignsController.php:87
        #: app/controllers/user/CampaignsController.php:135
        "You already have a campaign with that name." => "",

        #: app/controllers/user/CampaignsController.php:95
        #: app/controllers/user/CampaignsController.php:146
        "This slug is currently not available. Please choose another one." => "",

        #: app/controllers/user/CampaignsController.php:108
        "Campaign was successfully created. You may start adding links to it now." => "",

        #: app/controllers/user/CampaignsController.php:127
        "Campaign does not exist" => "",

        #: app/controllers/user/CampaignsController.php:154
        "Campaign was updated successfully." => "",

        #: app/controllers/user/CampaignsController.php:178
        #: app/controllers/user/CampaignsController.php:195
        "Campaign not found. Please try again." => "",

        #: app/controllers/user/CampaignsController.php:183
        "Campaign has been deleted." => "",

        #: app/controllers/user/CampaignsController.php:233
        #: storage/themes/default/user/campaignstats.php:3
        "Campaign Statistics" => "",

        #: app/controllers/user/ChannelsController.php:53
        #: storage/themes/default/bio/index.php:143
        #: storage/themes/default/partials/sidebar_menu.php:28
        #: storage/themes/default/pricing/index.php:60
        #: storage/themes/default/qr/index.php:140
        #: storage/themes/default/teams/edit.php:60
        #: storage/themes/default/teams/edit.php:61
        #: storage/themes/default/teams/edit.php:62
        #: storage/themes/default/teams/edit.php:63
        #: storage/themes/default/teams/index.php:168
        #: storage/themes/default/teams/index.php:169
        #: storage/themes/default/teams/index.php:170
        #: storage/themes/default/teams/index.php:171
        #: storage/themes/default/user/billing.php:84
        #: storage/themes/default/user/channels.php:3
        #: storage/themes/default/user/index.php:295
        #: storage/themes/default/user/links.php:201
        "Channels" => "",

        #: app/controllers/user/ChannelsController.php:135
        "Channel" => "",

        #: app/controllers/user/ChannelsController.php:156
        #: app/controllers/user/ChannelsController.php:193
        "Channel name cannot be empty and must have at least 2 characters." => "",

        #: app/controllers/user/ChannelsController.php:169
        "Channel was successfully created. You may start adding links to it now." => "",

        #: app/controllers/user/ChannelsController.php:188
        #: app/controllers/user/ChannelsController.php:228
        #: app/controllers/user/ChannelsController.php:309
        "Channel does not exist" => "",

        #: app/controllers/user/ChannelsController.php:202
        "Channel was updated successfully." => "",

        #: app/controllers/user/ChannelsController.php:235
        "Channel has been successfully deleted." => "",

        #: app/controllers/user/ChannelsController.php:249
        "You need to select at least 1 channel." => "",

        #: app/controllers/user/ChannelsController.php:288
        "Selected items have been added to the {c} channel." => "",

        #: app/controllers/user/ChannelsController.php:314
        "Item has been removed from the channel." => "",

        #: app/controllers/user/DashboardController.php:41
        #: app/helpers/payments/PaypalApi.php:336
        #: app/helpers/payments/PaypalApi.php:399
        "Your payment was successfully made. Thank you." => "",

        #: app/controllers/user/DashboardController.php:79
        #: storage/themes/default/partials/main_menu.php:101
        #: storage/themes/default/partials/main_menu.php:144
        #: storage/themes/default/partials/sidebar_menu.php:4
        "Dashboard" => "",

        #: app/controllers/user/DashboardController.php:99
        #: app/controllers/user/StatsController.php:79
        #: storage/themes/default/bio/edit.php:394
        #: storage/themes/default/bio/edit.php:485
        #: storage/themes/default/bio/new.php:348
        #: storage/themes/default/bio/new.php:439 storage/themes/default/index.php:635
        #: storage/themes/default/partials/sidebar_menu.php:41
        #: storage/themes/default/teams/edit.php:13
        #: storage/themes/default/teams/index.php:121
        #: storage/themes/default/user/campaigns.php:56
        #: storage/themes/default/user/channel.php:19
        #: storage/themes/default/user/index.php:8
        #: storage/themes/default/user/stats.php:14
        "Links" => "",

        #: app/controllers/user/DashboardController.php:106
        "Campaign Links" => "",

        #: app/controllers/user/DashboardController.php:163
        #: storage/themes/default/partials/sidebar_menu.php:46
        "Archived Links" => "",

        #: app/controllers/user/DashboardController.php:187
        #: storage/themes/default/partials/sidebar_menu.php:51
        "Expired Links" => "",

        #: app/controllers/user/DashboardController.php:301
        #: storage/themes/default/layouts/api.php:67
        #: storage/themes/default/layouts/auth.php:37
        #: storage/themes/default/layouts/dashboard.php:124
        #: storage/themes/default/layouts/main.php:69
        "Keyword must be more than 3 characters!" => "",

        #: app/controllers/user/DashboardController.php:317
        "Affiliate Referrals" => "",

        #: app/controllers/user/DomainsController.php:56
        #: storage/themes/default/domains/index.php:3
        #: storage/themes/default/partials/sidebar_menu.php:86
        #: storage/themes/default/pricing/index.php:59
        #: storage/themes/default/teams/edit.php:54
        #: storage/themes/default/user/billing.php:83
        "Branded Domains" => "",

        #: app/controllers/user/DomainsController.php:79
        #: storage/themes/default/domains/new.php:1
        "New Domain" => "",

        #: app/controllers/user/DomainsController.php:107
        "A valid domain name is required." => "",

        #: app/controllers/user/DomainsController.php:111
        "The domain has been already used." => "",

        #: app/controllers/user/DomainsController.php:117
        "A valid url is required for the root domain." => "",

        #: app/controllers/user/DomainsController.php:118
        "A valid url is required for the 404 page." => "",

        #: app/controllers/user/DomainsController.php:130
        "A new domain was added." => "",

        #: app/controllers/user/DomainsController.php:131
        "A customer ({e}) added a new domain to the platform. Please make sure the domain is pointed correctly and is resolving as expected. Their domain name is {d}." => "",

        #: app/controllers/user/DomainsController.php:134
        "Domain has been added successfully" => "",

        #: app/controllers/user/DomainsController.php:153
        #: app/controllers/user/DomainsController.php:179
        #: app/controllers/user/DomainsController.php:211
        "Domain not found. Please try again." => "",

        #: app/controllers/user/DomainsController.php:156
        #: storage/themes/default/domains/edit.php:1
        #: storage/themes/default/domains/index.php:43
        "Edit Domain" => "",

        #: app/controllers/user/DomainsController.php:186
        "Domain has been updated successfully." => "",

        #: app/controllers/user/DomainsController.php:216
        "Domain has been deleted." => "",

        #: app/controllers/user/ExportController.php:122
        #: app/controllers/user/ExportController.php:161
        "Please specify a range." => "",

        #: app/controllers/user/IntegrationsController.php:85
        #: storage/themes/default/integrations/slack.php:3
        #: storage/themes/default/partials/sidebar_menu.php:104
        "Slack Integration" => "",

        #: app/controllers/user/IntegrationsController.php:100
        #: storage/themes/default/integrations/zapier.php:4
        #: storage/themes/default/partials/sidebar_menu.php:106
        "Zapier Integration" => "",

        #: app/controllers/user/IntegrationsController.php:115
        #: storage/themes/default/integrations/wordpress.php:3
        #: storage/themes/default/partials/sidebar_menu.php:108
        "WordPress Integration" => "",

        #: app/controllers/user/IntegrationsController.php:133
        #: storage/themes/default/integrations/shortcuts.php:3
        #: storage/themes/default/partials/sidebar_menu.php:109
        "Shortcuts Integration" => "",

        #: app/controllers/user/IntegrationsController.php:161
        "Plugin cannot be generated. Please contact us for more information." => "",

        #: app/controllers/user/OverlayController.php:63 app/helpers/App.php:726
        #: storage/themes/default/overlay/create.php:31
        #: storage/themes/default/overlay/index.php:3
        #: storage/themes/default/overlay/index.php:63
        #: storage/themes/default/partials/sidebar_menu.php:72
        #: storage/themes/default/pricing/index.php:56
        #: storage/themes/default/teams/edit.php:40
        #: storage/themes/default/teams/index.php:148
        #: storage/themes/default/user/billing.php:80
        "CTA Overlay" => "",

        #: app/controllers/user/OverlayController.php:92
        #: storage/themes/default/overlay/create.php:3
        #: storage/themes/default/overlay/index.php:7
        #: storage/themes/default/overlay/index.php:52
        "Create a CTA Overlay" => "",

        #: app/controllers/user/OverlayController.php:140
        #: app/controllers/user/OverlayController.php:168
        "Overlay page does not exist." => "",

        #: app/controllers/user/OverlayController.php:289
        #: app/controllers/user/OverlayController.php:449
        #: storage/themes/default/overlay/create_contact.php:12
        #: storage/themes/default/overlay/create_contact.php:62
        #: storage/themes/default/overlay/create_contact.php:158
        #: storage/themes/default/overlay/create_coupon.php:12
        #: storage/themes/default/overlay/create_image.php:12
        #: storage/themes/default/overlay/create_message.php:12
        #: storage/themes/default/overlay/create_newsletter.php:12
        #: storage/themes/default/overlay/create_poll.php:12
        #: storage/themes/default/overlay/edit_contact.php:12
        #: storage/themes/default/overlay/edit_coupon.php:12
        #: storage/themes/default/overlay/edit_image.php:12
        #: storage/themes/default/overlay/edit_message.php:12
        #: storage/themes/default/overlay/edit_newsletter.php:12
        #: storage/themes/default/overlay/edit_poll.php:12
        #: storage/themes/default/pages/contact.php:21
        #: storage/themes/default/pages/contact.php:22
        #: storage/themes/default/splash/create.php:12
        #: storage/themes/default/splash/edit.php:12
        #: storage/themes/default/user/campaigns.php:20
        #: storage/themes/default/user/channels.php:79
        #: storage/themes/default/user/channels.php:121
        #: storage/themes/default/user/settings.php:28
        "Name" => "",

        #: app/controllers/user/OverlayController.php:291
        #: app/controllers/user/OverlayController.php:451
        #: storage/themes/default/gates/profile.php:132
        #: storage/themes/default/overlay/create_contact.php:76
        #: storage/themes/default/overlay/create_contact.php:166
        #: storage/themes/default/pages/contact.php:29
        #: storage/themes/default/qr/edit.php:68 storage/themes/default/qr/edit.php:98
        #: storage/themes/default/qr/edit.php:128 storage/themes/default/qr/new.php:91
        #: storage/themes/default/qr/new.php:117 storage/themes/default/qr/new.php:156
        "Message" => "",

        #: app/controllers/user/OverlayController.php:292
        #: app/controllers/user/OverlayController.php:452
        "send" => "",

        #: app/controllers/user/OverlayController.php:300
        #: app/controllers/user/OverlayController.php:460
        #: app/controllers/user/OverlayController.php:588
        #: app/controllers/user/OverlayController.php:730
        #: app/controllers/user/OverlayController.php:879
        #: app/controllers/user/OverlayController.php:1047
        #: app/controllers/user/OverlayController.php:1171
        #: app/controllers/user/OverlayController.php:1307
        #: app/controllers/user/OverlayController.php:1455
        #: app/controllers/user/OverlayController.php:1589
        #: app/controllers/user/OverlayController.php:1736
        #: app/controllers/user/OverlayController.php:1855
        "The name field cannot be empty." => "",

        #: app/controllers/user/OverlayController.php:334
        #: app/controllers/user/OverlayController.php:624
        #: app/controllers/user/OverlayController.php:925
        #: app/controllers/user/OverlayController.php:1224
        #: app/controllers/user/OverlayController.php:1480
        #: app/controllers/user/OverlayController.php:1762
        "Overlay has been successfully created." => "",

        #: app/controllers/user/OverlayController.php:351
        #: app/controllers/user/OverlayController.php:641
        #: app/controllers/user/OverlayController.php:942
        #: app/controllers/user/OverlayController.php:1241
        #: app/controllers/user/OverlayController.php:1510
        #: app/controllers/user/OverlayController.php:1779
        #: storage/themes/default/overlay/index.php:29
        #: storage/themes/default/partials/links.php:9
        #: storage/themes/default/splash/index.php:31
        #: storage/themes/default/teams/index.php:28
        #: storage/themes/default/user/campaigns.php:58
        #: storage/themes/default/user/channel.php:30
        #: storage/themes/default/user/channels.php:22
        #: storage/themes/default/user/channels.php:50
        "Edit" => "",

        #: app/controllers/user/OverlayController.php:490
        #: app/controllers/user/OverlayController.php:761
        #: app/controllers/user/OverlayController.php:1092
        #: app/controllers/user/OverlayController.php:1363
        #: app/controllers/user/OverlayController.php:1610
        #: app/controllers/user/OverlayController.php:1877
        "Overlay has been successfully updated." => "",

        #: app/controllers/user/OverlayController.php:590
        #: app/controllers/user/OverlayController.php:732
        "Please enter a valid question." => "",

        #: app/controllers/user/OverlayController.php:592
        #: app/controllers/user/OverlayController.php:734
        "A minimum of two options is required." => "",

        #: app/controllers/user/OverlayController.php:606
        #: app/controllers/user/OverlayController.php:748 app/traits/Overlays.php:272
        #: storage/themes/default/overlay/create_poll.php:48
        #: storage/themes/default/overlay/create_poll.php:111
        "Vote" => "",

        #: app/controllers/user/OverlayController.php:881
        #: app/controllers/user/OverlayController.php:1049
        #: app/controllers/user/OverlayController.php:1740
        #: app/controllers/user/OverlayController.php:1859
        "The message field cannot be empty." => "",

        #: app/controllers/user/OverlayController.php:883
        #: app/controllers/user/OverlayController.php:1051
        #: app/controllers/user/OverlayController.php:1173
        #: app/controllers/user/OverlayController.php:1309
        #: app/controllers/user/SplashController.php:111
        #: app/controllers/user/SplashController.php:210 app/traits/Links.php:83
        #: app/traits/Links.php:407 storage/themes/default/layouts/api.php:60
        #: storage/themes/default/layouts/auth.php:30
        #: storage/themes/default/layouts/dashboard.php:115
        #: storage/themes/default/layouts/main.php:62
        "Please enter a valid URL." => "",

        #: app/controllers/user/OverlayController.php:902
        #: app/controllers/user/OverlayController.php:904
        #: app/controllers/user/OverlayController.php:1070
        #: app/controllers/user/OverlayController.php:1072
        #: app/controllers/user/OverlayController.php:1185
        #: app/controllers/user/OverlayController.php:1187
        #: app/controllers/user/OverlayController.php:1321
        #: app/controllers/user/OverlayController.php:1323
        #: app/controllers/user/QRController.php:269
        #: app/controllers/user/QRController.php:470
        "Logo must be either a PNG or a JPEG (Max 500kb)." => "",

        #: app/controllers/user/OverlayController.php:908
        #: app/controllers/user/OverlayController.php:1076
        #: app/controllers/user/OverlayController.php:1191
        #: app/controllers/user/OverlayController.php:1327
        "Logo must be either a PNG or a JPEG with a recommended dimension of 100x100." => "",

        #: app/controllers/user/OverlayController.php:1175
        "You need to upload your logo and/or a background." => "",

        #: app/controllers/user/OverlayController.php:1201
        #: app/controllers/user/OverlayController.php:1203
        #: app/controllers/user/OverlayController.php:1341
        #: app/controllers/user/OverlayController.php:1343
        "Image must be either a PNG or a JPEG (Max 1mb)." => "",

        #: app/controllers/user/OverlayController.php:1207
        #: app/controllers/user/OverlayController.php:1347
        "Image must be either a PNG or a JPEG with a recommended dimension of 600x150." => "",

        #: app/controllers/user/OverlayController.php:1633
        "Overlay not found. Please try again." => "",

        #: app/controllers/user/OverlayController.php:1646
        "Overlay has been deleted." => "",

        #: app/controllers/user/OverlayController.php:1738
        #: app/controllers/user/OverlayController.php:1857
        "The coupon field cannot be empty." => "",

        #: app/controllers/user/PixelsController.php:58
        #: storage/themes/default/partials/sidebar_menu.php:79
        #: storage/themes/default/pixels/index.php:3
        #: storage/themes/default/teams/edit.php:47
        #: storage/themes/default/teams/index.php:155
        "Tracking Pixels" => "",

        #: app/controllers/user/PixelsController.php:85
        "New Pixel" => "",

        #: app/controllers/user/PixelsController.php:110
        "Pixel provider is currently not supported." => "",

        #: app/controllers/user/PixelsController.php:123
        #: app/controllers/user/PixelsController.php:194
        "Please enter valid id." => "",

        #: app/controllers/user/PixelsController.php:133
        "A pixel with this provider and tag already exists." => "",

        #: app/controllers/user/PixelsController.php:145
        "Pixel has been added successfully" => "",

        #: app/controllers/user/PixelsController.php:163
        #: app/controllers/user/PixelsController.php:189
        #: app/controllers/user/PixelsController.php:232
        "Pixel not found. Please try again." => "",

        #: app/controllers/user/PixelsController.php:166
        #: storage/themes/default/pixels/index.php:28
        "Edit Pixel" => "",

        #: app/controllers/user/PixelsController.php:208
        "Pixel has been updated successfully." => "",

        #: app/controllers/user/PixelsController.php:242
        "Pixel has been deleted." => "",

        #: app/controllers/user/QRController.php:94
        #: storage/themes/default/qr/index.php:7
        #: storage/themes/default/qr/index.php:65 storage/themes/default/qr/new.php:1
        #: storage/themes/default/teams/edit.php:20
        #: storage/themes/default/teams/index.php:128
        "Create QR" => "",

        #: app/controllers/user/QRController.php:155
        #: app/controllers/user/QRController.php:224
        #: app/controllers/user/QRController.php:417
        "Please enter a name for your QR code." => "",

        #: app/controllers/user/QRController.php:157
        #: app/controllers/user/QRController.php:222 app/helpers/QR.php:383
        #: app/helpers/QR.php:412 app/helpers/QR.php:430
        "Invalid QR format or missing data" => "",

        #: app/controllers/user/QRController.php:319
        "QR Code has been successfully generated." => "",

        #: app/controllers/user/QRController.php:386
        #: storage/themes/default/qr/edit.php:1 storage/themes/default/qr/index.php:26
        #: storage/themes/default/teams/edit.php:21
        #: storage/themes/default/teams/index.php:129
        "Edit QR" => "",

        #: app/controllers/user/QRController.php:507
        "QR Code has been successfully updated." => "",

        #: app/controllers/user/QRController.php:542
        "QR has been successfully deleted." => "",

        #: app/controllers/user/SplashController.php:58
        #: storage/themes/default/splash/index.php:3
        #: storage/themes/default/splash/index.php:64
        "Custom Splash Pages" => "",

        #: app/controllers/user/SplashController.php:84
        #: storage/themes/default/splash/create.php:1
        #: storage/themes/default/splash/edit.php:1
        #: storage/themes/default/splash/index.php:7
        #: storage/themes/default/splash/index.php:53
        "Create a Custom Splash" => "",

        #: app/controllers/user/SplashController.php:109
        #: app/controllers/user/SplashController.php:208
        "The name, title, message and link cannot be empty." => "",

        #: app/controllers/user/SplashController.php:112
        #: app/controllers/user/SplashController.php:211
        "Please enter a valid counter time in seconds." => "",

        #: app/controllers/user/SplashController.php:123
        #: app/controllers/user/SplashController.php:125
        #: app/controllers/user/SplashController.php:127
        #: app/controllers/user/SplashController.php:226
        #: app/controllers/user/SplashController.php:228
        "Avatar must be either a PNG or a JPEG (Max 300kb)." => "",

        #: app/controllers/user/SplashController.php:138
        #: app/controllers/user/SplashController.php:140
        #: app/controllers/user/SplashController.php:142
        #: app/controllers/user/SplashController.php:246
        #: app/controllers/user/SplashController.php:248
        "Banner must be either a PNG or a JPEG (Max 500kb)." => "",

        #: app/controllers/user/SplashController.php:146
        #: app/controllers/user/SplashController.php:252
        "Banner must be either a PNG or a JPEG with a recommended dimension of 980x300." => "",

        #: app/controllers/user/SplashController.php:162
        "Custom splash page has been created." => "",

        #: app/controllers/user/SplashController.php:179
        #: app/controllers/user/SplashController.php:205
        #: app/controllers/user/SplashController.php:285
        "Custom splash page does not exist." => "",

        #: app/controllers/user/SplashController.php:184
        "Update a Custom Splash" => "",

        #: app/controllers/user/SplashController.php:267
        "Custom splash page has been updated." => "",

        #: app/controllers/user/SplashController.php:302
        "Custom splash page has been deleted." => "",

        #: app/controllers/user/StatsController.php:40
        #: storage/themes/default/bio/index.php:30
        #: storage/themes/default/partials/links.php:7
        #: storage/themes/default/partials/sidebar_menu.php:24
        #: storage/themes/default/qr/index.php:29
        #: storage/themes/default/user/campaigns.php:60
        #: storage/themes/default/user/channel.php:27
        #: storage/themes/default/user/edit.php:8
        #: storage/themes/default/user/stats.php:3
        "Statistics" => "",

        #: app/controllers/user/TeamsController.php:59
        #: storage/themes/default/teams/index.php:3
        "Manage Teams" => "",

        #: app/controllers/user/TeamsController.php:83
        "This is not a valid email address" => "",

        #: app/controllers/user/TeamsController.php:86
        "This user has already an account. Please use another email." => "",

        #: app/controllers/user/TeamsController.php:90
        "This email address has been invited." => "",

        #: app/controllers/user/TeamsController.php:93
        "No permission has been assigned for this user." => "",

        #: app/controllers/user/TeamsController.php:115
        "An invite has been sent to the email." => "",

        #: app/controllers/user/ToolsController.php:37
        #: storage/themes/default/partials/sidebar_menu.php:111
        #: storage/themes/default/user/tools.php:1
        "Tools" => "",

        #: app/controllers/user/ToolsController.php:63
        "The application has been install on your slack account. You can now use the command to shorten links directly from your conversations." => "",

        #: app/controllers/user/ToolsController.php:88
        "Your Zapier URL has been updated." => "",

        #: app/helpers/App.php:715
        "Redirection" => "",

        #: app/helpers/App.php:717 storage/themes/default/user/settings.php:85
        "Frame" => "",

        #: app/helpers/App.php:718 storage/themes/default/user/settings.php:86
        "Splash" => "",

        #: app/helpers/App.php:732 storage/themes/default/partials/sidebar_menu.php:65
        #: storage/themes/default/pricing/index.php:55
        #: storage/themes/default/teams/edit.php:33
        #: storage/themes/default/teams/index.php:141
        #: storage/themes/default/user/billing.php:79
        "Custom Splash" => "",

        #: app/helpers/Captcha.php:114 app/helpers/Captcha.php:123
        #: app/helpers/Captcha.php:193 app/helpers/Captcha.php:201
        "The captcha did not validate. Please try again." => "",

        #: app/helpers/Emails.php:72
        "Please verify and approve this url" => "",

        #: app/helpers/Emails.php:106
        "Please verify your email" => "",

        #: app/helpers/Emails.php:139
        "Registration has been successful" => "",

        #: app/helpers/Emails.php:174
        "Password Reset Instructions" => "",

        #: app/helpers/Emails.php:206
        "Your email has been verified" => "",

        #: app/helpers/Emails.php:230
        "Your password was changed." => "",

        #: app/helpers/Emails.php:237
        "Your password was changed. If you did not change your password, please contact us as soon as possible." => "",

        #: app/helpers/Emails.php:255
        "You just got paid!" => "",

        #: app/helpers/Emails.php:262
        "You just got paid {amount} via PayPal for being an awesome affiliate!" => "",

        #: app/helpers/Emails.php:287
        "You have been invited to join our team" => "",

        #: app/helpers/Emails.php:317
        "Your subscription has been canceled" => "",

        #: app/helpers/Emails.php:349
        "Your trial will end soon!" => "",

        #: app/helpers/Emails.php:377
        "Admin notification" => "",

        #: app/helpers/Gate.php:39
        "Inactive Link" => "",

        #: app/helpers/Gate.php:54
        "Unsafe Link Detected" => "",

        #: app/helpers/Gate.php:68
        "Link Expired" => "",

        #: app/helpers/Gate.php:81
        "Enter your password to unlock this link" => "",

        #: app/helpers/Gate.php:82
        "The access to this link is restricted. Please enter your password to view it." => "",

        #: app/helpers/Gate.php:88 app/helpers/Gate.php:196 app/helpers/Gate.php:327
        "Adblock Detected" => "",

        #: app/helpers/Gate.php:88 app/helpers/Gate.php:196 app/helpers/Gate.php:327
        "Please disable Adblock and refresh the page again." => "",

        #: app/helpers/Gate.php:185 app/helpers/Gate.php:187
        "seconds" => "",

        #: app/helpers/Gate.php:187 storage/themes/default/layouts/dashboard.php:118
        "Continue" => "",

        #: app/helpers/Gate.php:404
        "Message sent successfully." => "",

        #: app/helpers/Gate.php:505 storage/themes/default/user/campaigns.php:21
        "List" => "",

        #: app/helpers/QR.php:78
        "An error internal server error ocurred. Please change the QR type." => "",

        #: app/helpers/QR.php:115 app/helpers/QR.php:127
        "QR data cannot be empty. Please fill the appropriate field." => "",

        #: app/helpers/QR.php:168 app/helpers/QR.php:184 app/helpers/QR.php:200
        #: app/helpers/QR.php:216
        "Invalid phone number. Please try again." => "",

        #: app/helpers/QR.php:277 app/helpers/QR.php:343
        "vCard data cannot be empty. Please fill some fields" => "",

        #: app/helpers/QR.php:389
        "Please enter the Wifi SSID" => "",

        #: app/helpers/QR.php:434 app/helpers/QR.php:441 app/helpers/QR.php:449
        "Please enter a valid wallet address" => "",

        #: app/helpers/QR.php:466
        "Please choose a valid file." => "",

        #: app/helpers/QR.php:468 app/helpers/QR.php:470
        "File must be either a PNG, JPEG, GIF or a PDF (Max 2MB)" => "",

        #: app/helpers/QrImagick.php:406
        "An error occurred" => "",

        #: app/helpers/Slack.php:104
        "You need to allow this application to install the commands on your slack account." => "",

        #: app/helpers/payments/Bank.php:48 app/traits/Payments.php:96
        #: app/traits/Payments.php:97
        "Bank Transfer" => "",

        #: app/helpers/payments/Bank.php:51 app/helpers/payments/Paypal.php:53
        #: app/helpers/payments/PaypalApi.php:55 app/helpers/payments/Stripe.php:56
        "Enable" => "",

        #: app/helpers/payments/Bank.php:53
        "Transfer payments via your bank." => "",

        #: app/helpers/payments/Bank.php:56
        "Bank Info" => "",

        #: app/helpers/payments/Bank.php:58
        "Enter the full information where your users can send payments to via their bank." => "",

        #: app/helpers/payments/Bank.php:76
        "Bank Information" => "",

        #: app/helpers/payments/Bank.php:93 app/helpers/payments/Bank.php:98
        #: app/helpers/payments/Paypal.php:95 app/helpers/payments/Paypal.php:99
        #: app/helpers/payments/PaypalApi.php:102
        #: app/helpers/payments/PaypalApi.php:106 app/helpers/payments/Stripe.php:191
        #: app/helpers/payments/Stripe.php:195 app/helpers/payments/Stripe.php:223
        #: app/helpers/payments/Stripe.php:252 app/helpers/payments/Stripe.php:255
        #: app/helpers/payments/Stripe.php:271 app/helpers/payments/Stripe.php:333
        #: app/helpers/payments/Stripe.php:345 app/helpers/payments/Stripe.php:383
        #: app/helpers/payments/Stripe.php:925 app/helpers/payments/Stripe.php:928
        #: app/helpers/payments/Stripe.php:1009
        "An error ocurred, please try again. You have not been charged." => "",

        #: app/helpers/payments/Bank.php:102 app/helpers/payments/Stripe.php:199
        #: app/helpers/payments/Stripe.php:888
        "First month" => "",

        #: app/helpers/payments/Bank.php:108 app/helpers/payments/Stripe.php:205
        #: app/helpers/payments/Stripe.php:894
        "First year" => "",

        #: app/helpers/payments/Bank.php:115 app/helpers/payments/Stripe.php:212
        #: app/helpers/payments/Stripe.php:901
        #: storage/themes/default/pricing/index.php:18
        "Lifetime" => "",

        #: app/helpers/payments/Bank.php:156
        "Your subscription is currently pending. Once we receive the money, we will activate your subscription." => "",

        #: app/helpers/payments/Paypal.php:50
        "Paypal Basic Checkout" => "",

        #: app/helpers/payments/Paypal.php:55
        "Collect payments via basic paypal checkout." => "",

        #: app/helpers/payments/Paypal.php:59 storage/themes/default/bio/edit.php:576
        #: storage/themes/default/bio/new.php:530
        "PayPal Email" => "",

        #: app/helpers/payments/Paypal.php:61
        "Payments will be sent to this address. Please make sure that you enable IPN and enable notification." => "",

        #: app/helpers/payments/Paypal.php:64
        "PayPal IPN" => "",

        #: app/helpers/payments/Paypal.php:66
        "For more info <a href=\"https://developer.paypal.com/webapps/developer/docs/classic/products/instant-payment-notification/\" target=\"_blank\">click here</a>" => "",

        #: app/helpers/payments/Paypal.php:145 app/helpers/payments/Paypal.php:248
        #: app/helpers/payments/PaypalApi.php:410
        "Your payment has been canceled." => "",

        #: app/helpers/payments/Paypal.php:154
        "Payment complete. We will upgrade your account as soon as the payment is verified." => "",

        #: app/helpers/payments/PaypalApi.php:52
        "Paypal API Payments" => "",

        #: app/helpers/payments/PaypalApi.php:57
        "Collect payments securely with PayPal API." => "",

        #: app/helpers/payments/PaypalApi.php:61
        "Client ID" => "",

        #: app/helpers/payments/PaypalApi.php:63
        "Please enter your live client ID." => "",

        #: app/helpers/payments/PaypalApi.php:66
        "Client Secret Key" => "",

        #: app/helpers/payments/PaypalApi.php:68
        "Please enter your live client secret." => "",

        #: app/helpers/payments/PaypalApi.php:74
        "You cannot enable both basic paypal and paypal api at the same time. You must choose one." => "",

        #: app/helpers/payments/PaypalApi.php:177
        #: app/helpers/payments/PaypalApi.php:242
        #: app/helpers/payments/PaypalApi.php:245
        #: app/helpers/payments/PaypalApi.php:341
        #: app/helpers/payments/PaypalApi.php:344
        #: app/helpers/payments/PaypalApi.php:358
        #: app/helpers/payments/PaypalApi.php:362
        #: app/helpers/payments/PaypalApi.php:404
        #: app/helpers/payments/PaypalApi.php:407
        "An issue occurred. You have not been charged." => "",

        #: app/helpers/payments/PaypalApi.php:300
        "An issue occurred. Please contact us for more info." => "",

        #: app/helpers/payments/Stripe.php:53
        "Stripe Payments" => "",

        #: app/helpers/payments/Stripe.php:58
        "Collect payments securely with Stripe." => "",

        #: app/helpers/payments/Stripe.php:62
        #: storage/themes/default/pricing/checkout.php:139
        "Checkout" => "",

        #: app/helpers/payments/Stripe.php:66
        "Built-in Checkout" => "",

        #: app/helpers/payments/Stripe.php:72
        "Stripe Hosted Checkout" => "",

        #: app/helpers/payments/Stripe.php:75
        "Choose between built-in checkout or Stripe hosted checkout." => "",

        #: app/helpers/payments/Stripe.php:78
        "Stripe Publishable Key" => "",

        #: app/helpers/payments/Stripe.php:80 app/helpers/payments/Stripe.php:85
        "Get your stripe keys from here once logged in <a href=\"https://dashboard.stripe.com/account/apikeys\" target=\"_blank\">click here</a>" => "",

        #: app/helpers/payments/Stripe.php:83
        "Stripe Secret Key" => "",

        #: app/helpers/payments/Stripe.php:88
        "Webhook Signature Key" => "",

        #: app/helpers/payments/Stripe.php:90
        "Webhook signature is a security measure to verify the authenticity of the data incoming from Stripe. It is highly recommended that you add this for safety measure. You can find your key after adding a webhook. <a href=\"https://dashboard.stripe.com/account/webhooks\" target=\"_blank\">Click here to find your signature key.</a>" => "",

        #: app/helpers/payments/Stripe.php:93
        "Webhook URL" => "",

        #: app/helpers/payments/Stripe.php:95
        "You can add your webhooks <a href=\"https://dashboard.stripe.com/account/webhooks\" target=\"_blank\">here</a>. For more info, please check the docs." => "",

        #: app/helpers/payments/Stripe.php:166
        "Credit or debit card" => "",

        #: app/helpers/payments/Stripe.php:372
        "Your credit card was declined. Please check your credit card and try again later." => "",

        #: app/helpers/payments/Stripe.php:432
        "You have a new Subscriber" => "",

        #: app/helpers/payments/Stripe.php:582
        "Payment failed" => "",

        #: app/middleware/Auth.php:59
        "Please upgrade to a premium package in order to continue." => "",

        #: app/middleware/CheckDomain.php:56
        "Great! Your domain is working." => "",

        #: app/middleware/CheckMaintenance.php:34
        "Offline for Maintenance" => "",

        #: app/middleware/CheckPrivate.php:40
        "Private Use" => "",

        #: app/models/Plans.php:43
        "This feature is currently unavailable. Please contact your team administrator." => "",

        #: app/models/Plans.php:46
        "Please choose a premium package to unlock this feature." => "",

        #: app/traits/Links.php:75
        "Please create a free account or login to shorten links." => "",

        #: app/traits/Links.php:77
        "You have reached your maximum links limit. Please upgrade to another plan." => "",

        #: app/traits/Links.php:80
        "This service is meant to be used internally only." => "",

        #: app/traits/Links.php:86 app/traits/Links.php:410
        "You cannot shorten URLs of this website." => "",

        #: app/traits/Links.php:89 app/traits/Links.php:413
        "This domain name or link has been blacklisted." => "",

        #: app/traits/Links.php:92 app/traits/Links.php:416
        "This URL contains blacklisted keywords." => "",

        #: app/traits/Links.php:95 app/traits/Links.php:98 app/traits/Links.php:101
        #: app/traits/Links.php:419 app/traits/Links.php:422 app/traits/Links.php:425
        "URL is suspected to contain malware and other harmful content." => "",

        #: app/traits/Links.php:104 app/traits/Links.php:428
        "Linking to executable files is not allowed." => "",

        #: app/traits/Links.php:107 app/traits/Links.php:431
        "The expiry date must be later than today." => "",

        #: app/traits/Links.php:205 app/traits/Links.php:207 app/traits/Links.php:550
        #: app/traits/Links.php:552
        "Banner must be either a PNG or a JPEG (Max 200kb)." => "",

        #: app/traits/Links.php:259 app/traits/Links.php:276 app/traits/Links.php:304
        "Link has been shortened" => "",

        #: app/traits/Links.php:286
        "You have maxed your short URLs limit. Either delete existing URLs or upgrade to a premium plan." => "",

        #: app/traits/Links.php:291 app/traits/Links.php:533
        "This URL cannot be used with this redirect method because browsers will prevent it for security reasons." => "",

        #: app/traits/Links.php:465
        "Custom alias must be less than 50 characters." => "",

        #: app/traits/Overlays.php:38
        "CTA Contact" => "",

        #: app/traits/Overlays.php:39
        "Create a contact form where users will be able to contact you via email." => "",

        #: app/traits/Overlays.php:48
        "CTA Poll" => "",

        #: app/traits/Overlays.php:49
        "Create a quick poll where users will be able to answer it upon visit." => "",

        #: app/traits/Overlays.php:58
        "CTA Message" => "",

        #: app/traits/Overlays.php:59
        "Create a small popup with a message and a link to a page or a product." => "",

        #: app/traits/Overlays.php:68
        "CTA Newsletter" => "",

        #: app/traits/Overlays.php:69
        "Create a small popup form to collect emails from users." => "",

        #: app/traits/Overlays.php:78
        "CTA Image" => "",

        #: app/traits/Overlays.php:79
        "Create a small popup with an image of your choice." => "",

        #: app/traits/Overlays.php:88
        #: storage/themes/default/overlay/create_coupon.php:90
        "Coupon" => "",

        #: app/traits/Overlays.php:89
        "Create a small popup with a coupon code that users can use." => "",

        #: app/traits/Payments.php:46 app/traits/Payments.php:80
        "PayPal" => "",

        #: app/traits/Payments.php:63
        "Credit Card" => "",

        #: app/traits/Payments.php:98
        "Transfer payments via your bank" => "",

        #: app/traits/Pixels.php:143
        "Google Ads pixel ID is not correct. Please double check." => "",

        #: app/traits/Pixels.php:154
        "LinkedIn ID is not correct. Please double check." => "",

        #: app/traits/Pixels.php:165
        "Twitter ID is not correct. Please double check." => "",

        #: app/traits/Pixels.php:176
        "AdRoll ID is not correct. Please double check." => "",

        #: app/traits/Pixels.php:187
        "Quora ID is not correct. Please double check." => "",

        #: app/traits/Pixels.php:198
        "GTM container ID is not correct. Please double check." => "",

        #: app/traits/Pixels.php:209
        "Google Analytics ID is not correct. Please double check." => "",

        #: app/traits/Pixels.php:221
        "Snapchat ID is not correct. Please double check." => "",

        #: app/traits/Pixels.php:233
        "Pinterest ID is not correct. Please double check." => "",

        #: app/traits/Pixels.php:245
        "Reddit ID is not correct. Please double check." => "",

        #: app/traits/Pixels.php:257
        "Bing ID is not correct. Please double check." => "",

        #: core/Auth.class.php:143 core/Auth.class.php:199
        "This user does not exist." => "",

        #: core/Helper.class.php:340
        "{d} {p} ago" => "",

        #: core/Helper.class.php:455
        "First" => "",

        #: core/Helper.class.php:456
        "Last" => "",

        #: core/Helper.class.php:458 core/Helper.class.php:480
        "Previous" => "",

        #: core/Helper.class.php:459 core/Helper.class.php:481
        "Next" => "",

        #: storage/themes/default/auth/2fa.php:15
        "The access code can be found on the Authenticator app. Please enter the code shown for this website. If you lost your phone or can't use the app, please contact us." => "",

        #: storage/themes/default/auth/2fa.php:22
        #: storage/themes/default/user/settings.php:286
        "2FA Access Code" => "",

        #: storage/themes/default/auth/2fa.php:31
        "Validate" => "",

        #: storage/themes/default/auth/invite.php:15
        "Join team and collaborate on everything" => "",

        #: storage/themes/default/auth/invite.php:31
        #: storage/themes/default/auth/register.php:19
        #: storage/themes/default/user/settings.php:43
        "Username" => "",

        #: storage/themes/default/auth/invite.php:40
        #: storage/themes/default/auth/login.php:33
        #: storage/themes/default/auth/register.php:39
        #: storage/themes/default/gates/password.php:22
        #: storage/themes/default/qr/edit.php:303
        #: storage/themes/default/qr/new.php:325
        #: storage/themes/default/user/billing.php:131
        #: storage/themes/default/user/settings.php:52
        "Password" => "",

        #: storage/themes/default/auth/invite.php:49
        #: storage/themes/default/auth/register.php:52
        #: storage/themes/default/auth/reset.php:30
        #: storage/themes/default/user/settings.php:59
        #: storage/themes/default/user/settings.php:234
        "Confirm Password" => "",

        #: storage/themes/default/auth/invite.php:61
        #: storage/themes/default/auth/register.php:66
        "I agree to the" => "",

        #: storage/themes/default/auth/invite.php:68
        #: storage/themes/default/auth/register.php:73
        "I agree to the terms and conditions" => "",

        #: storage/themes/default/auth/invite.php:73
        #: storage/themes/default/pages/consent.php:14
        "Accept" => "",

        #: storage/themes/default/auth/login.php:17
        "Email or username" => "",

        #: storage/themes/default/auth/login.php:26
        #: storage/themes/default/auth/login.php:104
        "Don't have an account?" => "",

        #: storage/themes/default/auth/login.php:44
        "Forgot Password?" => "",

        #: storage/themes/default/auth/login.php:51
        "Remember me" => "",

        #: storage/themes/default/auth/login.php:57
        #: storage/themes/default/auth/register.php:84
        #: storage/themes/default/partials/main_menu.php:128
        #: storage/themes/default/partials/main_menu.php:148
        "Login" => "",

        #: storage/themes/default/auth/login.php:63
        "or" => "",

        #: storage/themes/default/auth/login.php:69
        #: storage/themes/default/auth/login.php:70
        #: storage/themes/default/auth/login.php:77
        #: storage/themes/default/auth/login.php:78
        #: storage/themes/default/auth/login.php:85
        #: storage/themes/default/auth/login.php:86
        "Sign in with" => "",

        #: storage/themes/default/auth/login.php:96
        #: storage/themes/default/auth/register.php:87
        #: storage/themes/default/partials/footer.php:89
        "All Rights Reserved" => "",

        #: storage/themes/default/auth/login.php:106
        #: storage/themes/default/auth/login.php:112
        #: storage/themes/default/auth/register.php:13
        #: storage/themes/default/partials/footer.php:10
        "Start your marketing campaign now and reach your customers efficiently." => "",

        #: storage/themes/default/auth/login.php:108
        #: storage/themes/default/auth/register.php:80
        "Register" => "",

        #: storage/themes/default/auth/register.php:12
        "Create your account" => "",

        #: storage/themes/default/auth/register.php:21
        "Please enter a username" => "",

        #: storage/themes/default/auth/register.php:28
        "Email address" => "",

        #: storage/themes/default/auth/register.php:43
        "Please enter a valid password." => "",

        #: storage/themes/default/auth/register.php:56
        "Please confirm your password." => "",

        #: storage/themes/default/auth/register.php:83
        "Already have an account?" => "",

        #: storage/themes/default/auth/reset.php:21
        "New Password" => "",

        #: storage/themes/default/bio/edit.php:4
        #: storage/themes/default/bio/index.php:41
        "Default" => "",

        #: storage/themes/default/bio/edit.php:7
        #: storage/themes/default/overlay/edit_contact.php:149
        #: storage/themes/default/overlay/edit_coupon.php:81
        #: storage/themes/default/overlay/edit_message.php:110
        #: storage/themes/default/overlay/edit_newsletter.php:101
        #: storage/themes/default/overlay/edit_poll.php:99
        #: storage/themes/default/qr/edit.php:524
        #: storage/themes/default/splash/edit.php:58
        #: storage/themes/default/teams/edit.php:75
        #: storage/themes/default/user/channels.php:144
        "Update" => "",

        #: storage/themes/default/bio/edit.php:8
        #: storage/themes/default/bio/index.php:26
        "View Bio" => "",

        #: storage/themes/default/bio/edit.php:16
        #: storage/themes/default/bio/new.php:13
        "Bio Page Name" => "",

        #: storage/themes/default/bio/edit.php:20
        #: storage/themes/default/bio/new.php:17
        "Bio Page Alias" => "",

        #: storage/themes/default/bio/edit.php:24
        #: storage/themes/default/bio/new.php:29
        "Leave this field empty to generate a random alias." => "",

        #: storage/themes/default/bio/edit.php:45
        #: storage/themes/default/bio/new.php:46
        "Custom Avatar" => "",

        #: storage/themes/default/bio/edit.php:47
        #: storage/themes/default/bio/new.php:48
        "We recommend you choose a square picture with a maximum size of 300x300 and 500kb." => "",

        #: storage/themes/default/bio/edit.php:55
        #: storage/themes/default/bio/new.php:56
        "Content" => "",

        #: storage/themes/default/bio/edit.php:58
        #: storage/themes/default/bio/edit.php:76
        #: storage/themes/default/bio/new.php:59 storage/themes/default/bio/new.php:77
        "Social Links" => "",

        #: storage/themes/default/bio/edit.php:61
        #: storage/themes/default/bio/edit.php:135
        #: storage/themes/default/bio/new.php:62
        #: storage/themes/default/bio/new.php:136
        #: storage/themes/default/integrations/wordpress.php:61
        "Appearance" => "",

        #: storage/themes/default/bio/edit.php:64
        #: storage/themes/default/bio/edit.php:251
        #: storage/themes/default/bio/new.php:65
        #: storage/themes/default/bio/new.php:253 storage/themes/default/index.php:22
        "Advanced" => "",

        #: storage/themes/default/bio/edit.php:71
        #: storage/themes/default/bio/edit.php:366
        #: storage/themes/default/bio/new.php:72
        #: storage/themes/default/bio/new.php:320
        "Add Link or Content" => "",

        #: storage/themes/default/bio/edit.php:80
        #: storage/themes/default/bio/new.php:81
        #: storage/themes/default/qr/edit.php:194
        #: storage/themes/default/qr/edit.php:273
        #: storage/themes/default/qr/new.php:220 storage/themes/default/qr/new.php:297
        "Facebook" => "",

        #: storage/themes/default/bio/edit.php:81
        #: storage/themes/default/bio/edit.php:85
        #: storage/themes/default/bio/edit.php:89
        #: storage/themes/default/bio/edit.php:93
        #: storage/themes/default/bio/edit.php:97
        #: storage/themes/default/bio/edit.php:101
        #: storage/themes/default/bio/edit.php:105
        #: storage/themes/default/bio/edit.php:109
        #: storage/themes/default/bio/edit.php:113
        #: storage/themes/default/bio/edit.php:117
        #: storage/themes/default/bio/edit.php:121
        #: storage/themes/default/bio/edit.php:125
        #: storage/themes/default/bio/edit.php:129
        #: storage/themes/default/bio/edit.php:498
        #: storage/themes/default/bio/edit.php:651
        #: storage/themes/default/bio/new.php:82 storage/themes/default/bio/new.php:86
        #: storage/themes/default/bio/new.php:90 storage/themes/default/bio/new.php:94
        #: storage/themes/default/bio/new.php:98
        #: storage/themes/default/bio/new.php:102
        #: storage/themes/default/bio/new.php:106
        #: storage/themes/default/bio/new.php:110
        #: storage/themes/default/bio/new.php:114
        #: storage/themes/default/bio/new.php:118
        #: storage/themes/default/bio/new.php:122
        #: storage/themes/default/bio/new.php:126
        #: storage/themes/default/bio/new.php:130
        #: storage/themes/default/bio/new.php:452
        #: storage/themes/default/bio/new.php:605
        "Please enter a valid link" => "",

        #: storage/themes/default/bio/edit.php:84
        #: storage/themes/default/bio/new.php:85
        #: storage/themes/default/qr/edit.php:198
        #: storage/themes/default/qr/edit.php:277
        #: storage/themes/default/qr/new.php:224 storage/themes/default/qr/new.php:301
        "Twitter" => "",

        #: storage/themes/default/bio/edit.php:88
        #: storage/themes/default/bio/new.php:89
        #: storage/themes/default/qr/edit.php:202
        #: storage/themes/default/qr/edit.php:281
        #: storage/themes/default/qr/new.php:228 storage/themes/default/qr/new.php:305
        "Instagram" => "",

        #: storage/themes/default/bio/edit.php:92
        #: storage/themes/default/bio/new.php:93
        "Tiktok" => "",

        #: storage/themes/default/bio/edit.php:96
        #: storage/themes/default/bio/new.php:97
        "Linkedin" => "",

        #: storage/themes/default/bio/edit.php:100
        #: storage/themes/default/bio/new.php:101
        "Youtube" => "",

        #: storage/themes/default/bio/edit.php:104
        #: storage/themes/default/bio/new.php:105
        "Telegram" => "",

        #: storage/themes/default/bio/edit.php:108
        #: storage/themes/default/bio/new.php:109
        "Snapchat" => "",

        #: storage/themes/default/bio/edit.php:112
        #: storage/themes/default/bio/new.php:113
        "Discord" => "",

        #: storage/themes/default/bio/edit.php:116
        #: storage/themes/default/bio/new.php:117
        "Twitch" => "",

        #: storage/themes/default/bio/edit.php:120
        #: storage/themes/default/bio/new.php:121
        "Pinterest" => "",

        #: storage/themes/default/bio/edit.php:124
        #: storage/themes/default/bio/new.php:125
        "Shopify" => "",

        #: storage/themes/default/bio/edit.php:128
        #: storage/themes/default/bio/new.php:129
        "Amazon" => "",

        #: storage/themes/default/bio/edit.php:138
        #: storage/themes/default/bio/new.php:139
        "Templates" => "",

        #: storage/themes/default/bio/edit.php:192
        #: storage/themes/default/bio/new.php:193
        "Font" => "",

        #: storage/themes/default/bio/edit.php:201
        #: storage/themes/default/bio/new.php:202
        #: storage/themes/default/qr/edit.php:368
        #: storage/themes/default/qr/new.php:375
        "Single Color" => "",

        #: storage/themes/default/bio/edit.php:202
        #: storage/themes/default/bio/new.php:203
        #: storage/themes/default/qr/edit.php:369
        #: storage/themes/default/qr/new.php:376
        "Gradient Color" => "",

        #: storage/themes/default/bio/edit.php:220
        #: storage/themes/default/bio/new.php:222
        #: storage/themes/default/qr/edit.php:402
        #: storage/themes/default/qr/new.php:409
        "Gradient Start" => "",

        #: storage/themes/default/bio/edit.php:226
        #: storage/themes/default/bio/new.php:228
        #: storage/themes/default/qr/edit.php:406
        #: storage/themes/default/qr/new.php:413
        "Gradient Stop" => "",

        #: storage/themes/default/bio/edit.php:235
        #: storage/themes/default/bio/new.php:237
        "Text Color" => "",

        #: storage/themes/default/bio/edit.php:239
        #: storage/themes/default/bio/new.php:241
        "Button Color" => "",

        #: storage/themes/default/bio/edit.php:243
        #: storage/themes/default/bio/new.php:245
        "Button text Color" => "",

        #: storage/themes/default/bio/edit.php:255
        #: storage/themes/default/bio/new.php:257
        #: storage/themes/default/partials/shortener.php:131
        #: storage/themes/default/user/edit.php:38
        "Meta Title" => "",

        #: storage/themes/default/bio/edit.php:259
        #: storage/themes/default/bio/new.php:261
        #: storage/themes/default/partials/shortener.php:137
        #: storage/themes/default/user/edit.php:44
        "Meta Description" => "",

        #: storage/themes/default/bio/edit.php:263
        #: storage/themes/default/bio/new.php:265 storage/themes/default/index.php:32
        #: storage/themes/default/partials/shortener.php:71
        #: storage/themes/default/user/edit.php:306
        "Password Protection" => "",

        #: storage/themes/default/bio/edit.php:264
        #: storage/themes/default/bio/new.php:266
        #: storage/themes/default/partials/shortener.php:72
        "By adding a password, you can restrict the access." => "",

        #: storage/themes/default/bio/edit.php:267
        #: storage/themes/default/bio/new.php:269 storage/themes/default/index.php:33
        #: storage/themes/default/partials/shortener.php:75
        #: storage/themes/default/user/edit.php:309
        "Type your password here" => "",

        #: storage/themes/default/bio/edit.php:272
        #: storage/themes/default/bio/new.php:274
        #: storage/themes/default/partials/shortener.php:215
        #: storage/themes/default/user/edit.php:171
        "Targeting Pixels" => "",

        #: storage/themes/default/bio/edit.php:273
        #: storage/themes/default/bio/new.php:275
        #: storage/themes/default/partials/shortener.php:216
        #: storage/themes/default/user/edit.php:172
        "Add your targeting pixels below from the list. Please make sure to enable them in the pixels settings." => "",

        #: storage/themes/default/bio/edit.php:290
        #: storage/themes/default/bio/new.php:292
        "Custom CSS" => "",

        #: storage/themes/default/bio/edit.php:321
        #: storage/themes/default/user/index.php:224
        #: storage/themes/default/user/links.php:130
        "Download" => "",

        #: storage/themes/default/bio/edit.php:333
        #: storage/themes/default/pages/report.php:25
        #: storage/themes/default/user/index.php:236
        #: storage/themes/default/user/links.php:142
        "Short Link" => "",

        #: storage/themes/default/bio/edit.php:351
        #: storage/themes/default/overlay/edit_newsletter.php:121
        "Newsletter Emails" => "",

        #: storage/themes/default/bio/edit.php:354
        #: storage/themes/default/overlay/edit_newsletter.php:124
        "Collected {c} emails in total" => "",

        #: storage/themes/default/bio/edit.php:355
        #: storage/themes/default/overlay/edit_newsletter.php:125
        "Download as CSV" => "",

        #: storage/themes/default/bio/edit.php:376
        #: storage/themes/default/bio/edit.php:601
        #: storage/themes/default/bio/new.php:330
        #: storage/themes/default/bio/new.php:555
        "Heading" => "",

        #: storage/themes/default/bio/edit.php:388
        #: storage/themes/default/bio/edit.php:621
        #: storage/themes/default/bio/new.php:342
        #: storage/themes/default/bio/new.php:575
        "Divider" => "",

        #: storage/themes/default/bio/edit.php:400
        #: storage/themes/default/bio/new.php:354
        "HTML" => "",

        #: storage/themes/default/bio/edit.php:412
        #: storage/themes/default/bio/edit.php:647
        #: storage/themes/default/bio/edit.php:650
        #: storage/themes/default/bio/new.php:366
        #: storage/themes/default/bio/new.php:601
        #: storage/themes/default/bio/new.php:604
        "RSS Feed" => "",

        #: storage/themes/default/bio/edit.php:418
        #: storage/themes/default/bio/edit.php:700
        #: storage/themes/default/bio/new.php:372
        #: storage/themes/default/bio/new.php:654
        #: storage/themes/default/overlay/create_newsletter.php:107
        #: storage/themes/default/overlay/edit_newsletter.php:107
        #: storage/themes/default/user/settings.php:165
        "Newsletter" => "",

        #: storage/themes/default/bio/edit.php:424
        #: storage/themes/default/bio/new.php:378
        "Contact Form" => "",

        #: storage/themes/default/bio/edit.php:430
        #: storage/themes/default/bio/edit.php:655
        #: storage/themes/default/bio/new.php:384
        #: storage/themes/default/bio/new.php:609
        #: storage/themes/default/qr/edit.php:137
        #: storage/themes/default/qr/edit.php:216 storage/themes/default/qr/new.php:23
        #: storage/themes/default/qr/new.php:163 storage/themes/default/qr/new.php:240
        "vCard" => "",

        #: storage/themes/default/bio/edit.php:436
        #: storage/themes/default/bio/edit.php:695
        #: storage/themes/default/bio/new.php:390
        #: storage/themes/default/bio/new.php:649
        "Product" => "",

        #: storage/themes/default/bio/edit.php:442
        #: storage/themes/default/bio/edit.php:533
        #: storage/themes/default/bio/new.php:396
        #: storage/themes/default/bio/new.php:487
        "Youtube Video" => "",

        #: storage/themes/default/bio/edit.php:448
        #: storage/themes/default/bio/edit.php:541
        #: storage/themes/default/bio/new.php:402
        #: storage/themes/default/bio/new.php:495
        "WhatsApp Call" => "",

        #: storage/themes/default/bio/edit.php:454
        #: storage/themes/default/bio/edit.php:553
        #: storage/themes/default/bio/new.php:408
        #: storage/themes/default/bio/new.php:507
        "Spotify Embed" => "",

        #: storage/themes/default/bio/edit.php:460
        #: storage/themes/default/bio/edit.php:561
        #: storage/themes/default/bio/new.php:414
        #: storage/themes/default/bio/new.php:515
        "Apple Music Embed" => "",

        #: storage/themes/default/bio/edit.php:466
        #: storage/themes/default/bio/new.php:420
        "Paypal Button" => "",

        #: storage/themes/default/bio/edit.php:472
        #: storage/themes/default/bio/edit.php:593
        #: storage/themes/default/bio/new.php:426
        #: storage/themes/default/bio/new.php:547
        "Tiktok Embed" => "",

        #: storage/themes/default/bio/edit.php:478
        #: storage/themes/default/bio/edit.php:486
        #: storage/themes/default/bio/edit.php:526
        #: storage/themes/default/bio/edit.php:534
        #: storage/themes/default/bio/edit.php:542
        #: storage/themes/default/bio/edit.php:554
        #: storage/themes/default/bio/edit.php:562
        #: storage/themes/default/bio/edit.php:570
        #: storage/themes/default/bio/edit.php:594
        #: storage/themes/default/bio/edit.php:602
        #: storage/themes/default/bio/edit.php:622
        #: storage/themes/default/bio/edit.php:648
        #: storage/themes/default/bio/edit.php:656
        #: storage/themes/default/bio/edit.php:696
        #: storage/themes/default/bio/edit.php:701
        #: storage/themes/default/bio/edit.php:710
        #: storage/themes/default/bio/edit.php:723
        #: storage/themes/default/bio/new.php:432
        #: storage/themes/default/bio/new.php:440
        #: storage/themes/default/bio/new.php:480
        #: storage/themes/default/bio/new.php:488
        #: storage/themes/default/bio/new.php:496
        #: storage/themes/default/bio/new.php:508
        #: storage/themes/default/bio/new.php:516
        #: storage/themes/default/bio/new.php:524
        #: storage/themes/default/bio/new.php:548
        #: storage/themes/default/bio/new.php:556
        #: storage/themes/default/bio/new.php:576
        #: storage/themes/default/bio/new.php:602
        #: storage/themes/default/bio/new.php:610
        #: storage/themes/default/bio/new.php:650
        #: storage/themes/default/bio/new.php:655
        #: storage/themes/default/bio/new.php:664
        #: storage/themes/default/bio/new.php:677
        "Back" => "",

        #: storage/themes/default/bio/edit.php:483
        #: storage/themes/default/bio/new.php:437
        "Add Text" => "",

        #: storage/themes/default/bio/edit.php:488
        #: storage/themes/default/bio/new.php:442
        "FontAwesome Icon Class" => "",

        #: storage/themes/default/bio/edit.php:490
        #: storage/themes/default/bio/new.php:444
        "You can use any font from the following list:" => "",

        #: storage/themes/default/bio/edit.php:523
        #: storage/themes/default/bio/new.php:477
        "Add Link" => "",

        #: storage/themes/default/bio/edit.php:529
        #: storage/themes/default/bio/new.php:483
        "Click the button add insert an Image then choose the file to upload." => "",

        #: storage/themes/default/bio/edit.php:531
        #: storage/themes/default/bio/new.php:485
        "Add Image" => "",

        #: storage/themes/default/bio/edit.php:536
        #: storage/themes/default/bio/new.php:490
        "Link to Video" => "",

        #: storage/themes/default/bio/edit.php:537
        #: storage/themes/default/bio/new.php:491
        "Please enter a valid youtube link" => "",

        #: storage/themes/default/bio/edit.php:539
        #: storage/themes/default/bio/new.php:493
        "Add Youtube Video" => "",

        #: storage/themes/default/bio/edit.php:544
        #: storage/themes/default/bio/new.php:498
        #: storage/themes/default/qr/edit.php:81 storage/themes/default/qr/edit.php:94
        #: storage/themes/default/qr/edit.php:111
        #: storage/themes/default/qr/edit.php:124
        #: storage/themes/default/qr/new.php:102 storage/themes/default/qr/new.php:113
        #: storage/themes/default/qr/new.php:128 storage/themes/default/qr/new.php:152
        "Phone Number" => "",

        #: storage/themes/default/bio/edit.php:548
        #: storage/themes/default/bio/edit.php:572
        #: storage/themes/default/bio/new.php:502
        #: storage/themes/default/bio/new.php:526
        "Label" => "",

        #: storage/themes/default/bio/edit.php:551
        #: storage/themes/default/bio/new.php:505
        "Add WhatsApp Call" => "",

        #: storage/themes/default/bio/edit.php:556
        #: storage/themes/default/bio/new.php:510
        "Link to Spotify Song" => "",

        #: storage/themes/default/bio/edit.php:557
        #: storage/themes/default/bio/new.php:511
        "Please enter a valid spotify link" => "",

        #: storage/themes/default/bio/edit.php:559
        #: storage/themes/default/bio/new.php:513
        "Add Spotify" => "",

        #: storage/themes/default/bio/edit.php:564
        #: storage/themes/default/bio/new.php:518
        "Link to Apple Music Song" => "",

        #: storage/themes/default/bio/edit.php:565
        #: storage/themes/default/bio/new.php:519
        "Please enter a valid apple music link" => "",

        #: storage/themes/default/bio/edit.php:567
        #: storage/themes/default/bio/new.php:521
        "Add Apple Music" => "",

        #: storage/themes/default/bio/edit.php:569
        #: storage/themes/default/bio/new.php:523
        "PayPal Button" => "",

        #: storage/themes/default/bio/edit.php:591
        #: storage/themes/default/bio/new.php:545
        "Add Paypal" => "",

        #: storage/themes/default/bio/edit.php:596
        #: storage/themes/default/bio/new.php:550
        "Link to Tiktok Video" => "",

        #: storage/themes/default/bio/edit.php:597
        #: storage/themes/default/bio/new.php:551
        "Please enter a valid tiktok link" => "",

        #: storage/themes/default/bio/edit.php:599
        #: storage/themes/default/bio/new.php:553
        "Add Tiktok" => "",

        #: storage/themes/default/bio/edit.php:619
        #: storage/themes/default/bio/edit.php:645
        #: storage/themes/default/bio/edit.php:653
        #: storage/themes/default/bio/edit.php:693
        #: storage/themes/default/bio/edit.php:698
        #: storage/themes/default/bio/edit.php:707
        #: storage/themes/default/bio/edit.php:720
        #: storage/themes/default/bio/edit.php:728
        #: storage/themes/default/bio/index.php:155
        #: storage/themes/default/bio/new.php:573
        #: storage/themes/default/bio/new.php:599
        #: storage/themes/default/bio/new.php:607
        #: storage/themes/default/bio/new.php:647
        #: storage/themes/default/bio/new.php:652
        #: storage/themes/default/bio/new.php:661
        #: storage/themes/default/bio/new.php:674
        #: storage/themes/default/bio/new.php:682
        #: storage/themes/default/qr/index.php:152
        #: storage/themes/default/user/index.php:279
        #: storage/themes/default/user/index.php:307
        #: storage/themes/default/user/links.php:185
        #: storage/themes/default/user/links.php:213
        "Add" => "",

        #: storage/themes/default/bio/edit.php:631
        #: storage/themes/default/bio/new.php:585
        "Height" => "",

        #: storage/themes/default/bio/edit.php:697
        #: storage/themes/default/bio/new.php:651
        "This widget allows you to add a custom product. Click the button add below to insert a the widget" => "",

        #: storage/themes/default/bio/edit.php:705
        #: storage/themes/default/bio/new.php:659
        "Newsletter list can be downloaded on the Edit Bio page" => "",

        #: storage/themes/default/bio/edit.php:709
        #: storage/themes/default/bio/new.php:663
        #: storage/themes/default/layouts/dashboard.php:88
        #: storage/themes/default/user/affiliate.php:69
        "Contact" => "",

        #: storage/themes/default/bio/edit.php:718
        #: storage/themes/default/bio/new.php:672
        "You will receive emails here" => "",

        #: storage/themes/default/bio/index.php:23
        #: storage/themes/default/teams/edit.php:28
        #: storage/themes/default/teams/index.php:136
        "Edit Bio" => "",

        #: storage/themes/default/bio/index.php:28
        "Set as Default" => "",

        #: storage/themes/default/bio/index.php:32
        #: storage/themes/default/qr/index.php:35
        #: storage/themes/default/user/index.php:76
        #: storage/themes/default/user/links.php:24
        "Add to Channel" => "",

        #: storage/themes/default/bio/index.php:33
        #: storage/themes/default/partials/links.php:26
        #: storage/themes/default/qr/index.php:36
        "Reset Stats" => "",

        #: storage/themes/default/bio/index.php:34
        #: storage/themes/default/qr/index.php:37
        "Duplicate" => "",

        #: storage/themes/default/bio/index.php:37
        #: storage/themes/default/domains/index.php:46
        #: storage/themes/default/overlay/index.php:33
        #: storage/themes/default/partials/links.php:29
        #: storage/themes/default/pixels/index.php:31
        #: storage/themes/default/qr/index.php:40
        #: storage/themes/default/splash/index.php:35
        #: storage/themes/default/teams/index.php:30
        #: storage/themes/default/user/campaigns.php:63
        #: storage/themes/default/user/channels.php:26
        #: storage/themes/default/user/channels.php:54
        #: storage/themes/default/user/edit.php:89
        #: storage/themes/default/user/settings.php:240
        "Delete" => "",

        #: storage/themes/default/bio/index.php:54
        #: storage/themes/default/gates/media.php:14
        "Views" => "",

        #: storage/themes/default/bio/index.php:65
        #: storage/themes/default/overlay/index.php:50
        #: storage/themes/default/pixels/index.php:49
        #: storage/themes/default/qr/index.php:63
        #: storage/themes/default/splash/index.php:51
        #: storage/themes/default/user/campaigns.php:74
        "No content found. You can create some." => "",

        #: storage/themes/default/bio/index.php:79
        #: storage/themes/default/domains/index.php:63
        #: storage/themes/default/domains/new.php:42
        #: storage/themes/default/overlay/create.php:33
        #: storage/themes/default/overlay/index.php:65
        #: storage/themes/default/pixels/index.php:63
        #: storage/themes/default/pixels/new.php:42
        #: storage/themes/default/pricing/index.php:47
        #: storage/themes/default/pricing/index.php:48
        #: storage/themes/default/pricing/index.php:53
        #: storage/themes/default/pricing/index.php:54
        #: storage/themes/default/pricing/index.php:55
        #: storage/themes/default/pricing/index.php:56
        #: storage/themes/default/pricing/index.php:57
        #: storage/themes/default/pricing/index.php:58
        #: storage/themes/default/pricing/index.php:59
        #: storage/themes/default/pricing/index.php:60
        #: storage/themes/default/pricing/index.php:64
        #: storage/themes/default/qr/index.php:77
        #: storage/themes/default/splash/index.php:66
        #: storage/themes/default/teams/index.php:67
        #: storage/themes/default/user/billing.php:73
        #: storage/themes/default/user/billing.php:74
        #: storage/themes/default/user/billing.php:77
        #: storage/themes/default/user/billing.php:78
        #: storage/themes/default/user/billing.php:79
        #: storage/themes/default/user/billing.php:80
        #: storage/themes/default/user/billing.php:81
        #: storage/themes/default/user/billing.php:82
        #: storage/themes/default/user/billing.php:83
        #: storage/themes/default/user/billing.php:84
        #: storage/themes/default/user/billing.php:88
        "Unlimited" => "",

        #: storage/themes/default/bio/index.php:86
        "What are Bio Pages?" => "",

        #: storage/themes/default/bio/index.php:90
        "A bio page allows you to create a trackable and customizable landing page where you can add links to your social network pages." => "",

        #: storage/themes/default/bio/index.php:91
        "You can set a bio page as default and access them via your profile page." => "",

        #: storage/themes/default/bio/index.php:102
        #: storage/themes/default/domains/index.php:87
        #: storage/themes/default/overlay/create.php:53
        #: storage/themes/default/overlay/index.php:87
        #: storage/themes/default/pixels/index.php:85
        #: storage/themes/default/qr/index.php:99
        #: storage/themes/default/splash/index.php:87
        #: storage/themes/default/teams/index.php:90
        #: storage/themes/default/user/campaigns.php:194
        #: storage/themes/default/user/channels.php:156
        #: storage/themes/default/user/index.php:181
        #: storage/themes/default/user/links.php:87
        "Are you sure you want to delete this?" => "",

        #: storage/themes/default/bio/index.php:106
        #: storage/themes/default/domains/index.php:91
        #: storage/themes/default/overlay/create.php:57
        #: storage/themes/default/overlay/index.php:91
        #: storage/themes/default/pixels/index.php:89
        #: storage/themes/default/qr/index.php:103
        #: storage/themes/default/splash/index.php:91
        #: storage/themes/default/teams/index.php:94
        #: storage/themes/default/user/campaigns.php:198
        #: storage/themes/default/user/channels.php:160
        #: storage/themes/default/user/index.php:185
        #: storage/themes/default/user/links.php:91
        "You are trying to delete a record. This action is permanent and cannot be reversed." => "",

        #: storage/themes/default/bio/index.php:110
        #: storage/themes/default/bio/index.php:128
        #: storage/themes/default/domains/index.php:95
        #: storage/themes/default/overlay/create.php:61
        #: storage/themes/default/overlay/index.php:95
        #: storage/themes/default/pixels/index.php:93
        #: storage/themes/default/qr/index.php:107
        #: storage/themes/default/qr/index.php:125
        #: storage/themes/default/splash/index.php:95
        #: storage/themes/default/teams/index.php:98
        #: storage/themes/default/user/campaigns.php:202
        #: storage/themes/default/user/channel.php:67
        #: storage/themes/default/user/channels.php:164
        #: storage/themes/default/user/index.php:189
        #: storage/themes/default/user/index.php:206
        #: storage/themes/default/user/links.php:95
        #: storage/themes/default/user/links.php:112
        "Confirm" => "",

        #: storage/themes/default/bio/index.php:120
        #: storage/themes/default/qr/index.php:117
        #: storage/themes/default/user/index.php:198
        #: storage/themes/default/user/links.php:104
        "Are you sure you want to reset this?" => "",

        #: storage/themes/default/bio/index.php:124
        #: storage/themes/default/qr/index.php:121
        #: storage/themes/default/user/index.php:202
        #: storage/themes/default/user/links.php:108
        "You are trying to reset all statistic data for this link. This action is permanent and cannot be reversed." => "",

        #: storage/themes/default/bio/index.php:139
        #: storage/themes/default/qr/index.php:136
        #: storage/themes/default/user/index.php:291
        #: storage/themes/default/user/links.php:197
        "Add to Channels" => "",

        #: storage/themes/default/bio/new.php:5
        "Publish" => "",

        #: storage/themes/default/bio/new.php:25
        "Choose domain to generate the link with." => "",

        #: storage/themes/default/blog_single.php:23
        "Published on" => "",

        #: storage/themes/default/blog_single.php:60
        "Keep reading" => "",

        #: storage/themes/default/blog_single.php:61
        "More posts from our blog" => "",

        #: storage/themes/default/blog_single.php:64
        #: storage/themes/default/user/index.php:113
        "View all" => "",

        #: storage/themes/default/class/themeSettings.php:128
        "Color 1" => "",

        #: storage/themes/default/class/themeSettings.php:134
        "Color 2" => "",

        #: storage/themes/default/class/themeSettings.php:230
        "The custom image is not valid. Only a JPG or PNG are accepted." => "",

        #: storage/themes/default/class/themeSettings.php:232
        "Custom image must be either a PNG or a JPEG (Max 500kb)." => "",

        #: storage/themes/default/class/themeSettings.php:255
        "Settings are successfully saved." => "",

        #: storage/themes/default/domains/edit.php:9
        #: storage/themes/default/domains/index.php:18
        #: storage/themes/default/domains/new.php:11
        #: storage/themes/default/partials/shortener.php:29
        #: storage/themes/default/qr/edit.php:13 storage/themes/default/qr/new.php:41
        #: storage/themes/default/user/edit.php:265
        "Domain" => "",

        #: storage/themes/default/domains/edit.php:15
        #: storage/themes/default/domains/index.php:19
        #: storage/themes/default/domains/new.php:18
        "Domain Root" => "",

        #: storage/themes/default/domains/edit.php:17
        #: storage/themes/default/domains/new.php:20
        "Redirects to this page if someone visits the root domain above without a short alias." => "",

        #: storage/themes/default/domains/edit.php:22
        #: storage/themes/default/domains/new.php:25
        "Domain 404" => "",

        #: storage/themes/default/domains/edit.php:24
        #: storage/themes/default/domains/new.php:27
        "Redirects to this page if a short url is not found (error 404)." => "",

        #: storage/themes/default/domains/edit.php:29
        "Update Domain" => "",

        #: storage/themes/default/domains/index.php:7
        #: storage/themes/default/domains/new.php:32
        "Add Domain" => "",

        #: storage/themes/default/domains/index.php:20
        "404 Redirect" => "",

        #: storage/themes/default/domains/index.php:30
        #: storage/themes/default/integrations/zapier.php:9
        #: storage/themes/default/teams/index.php:34
        #: storage/themes/default/user/billing.php:27
        #: storage/themes/default/user/campaigns.php:32
        "Active" => "",

        #: storage/themes/default/domains/index.php:32
        "Pending DNS" => "",

        #: storage/themes/default/domains/index.php:34
        "Inactive/Disabled" => "",

        #: storage/themes/default/domains/index.php:37
        #: storage/themes/default/domains/index.php:38
        #: storage/themes/default/user/edit.php:329
        #: storage/themes/default/user/index.php:269
        #: storage/themes/default/user/links.php:175
        "None" => "",

        #: storage/themes/default/domains/index.php:61
        #: storage/themes/default/domains/new.php:40
        "Domains" => "",

        #: storage/themes/default/domains/index.php:70
        #: storage/themes/default/domains/new.php:49
        "How to setup custom domain" => "",

        #: storage/themes/default/domains/index.php:74
        "If you have a custom domain name that you want to use with our service, you can associate it to your account very easily. Once added, we will add the domain to your account and set it as the default domain name for your URLs. DNS changes could take up to 36 hours. If you are planning to serve SSL on your domain name, we recommend using cloudflare." => "",

        #: storage/themes/default/domains/index.php:76
        #: storage/themes/default/domains/new.php:55
        "To point your domain name, create an A record and set the value to " => "",

        #: storage/themes/default/domains/new.php:13
        "You will need to setup a DNS record for your domain to work. See instructions on the right side." => "",

        #: storage/themes/default/domains/new.php:53
        "If you have a custom domain name that you want to use with our service, you can associate it to your account very easily. Once added, we will add the domain to your account and set it as the default domain name for your URLs. DNS changes could take up to 36 hours." => "",

        #: storage/themes/default/errors/404.php:26
        "Error" => "",

        #: storage/themes/default/errors/404.php:28
        "The page you are looking for could not be found." => "",

        #: storage/themes/default/errors/404.php:32
        #: storage/themes/default/errors/disabled.php:32
        #: storage/themes/default/errors/expired.php:31
        "Back to home" => "",

        #: storage/themes/default/errors/disabled.php:26
        "Stop" => "",

        #: storage/themes/default/errors/disabled.php:28
        "There is a problem with this link and we have blocked it either because it is potentially malicious or contains inappropriate content that is against our terms of service. We actively monitor all links on our platform to ensure the safety of all our users. If you have any questions, feel free to contact us." => "",

        #: storage/themes/default/errors/expired.php:25
        "Oops" => "",

        #: storage/themes/default/errors/expired.php:27
        "The link you are trying to access is now expired either because the campaign ended or the link was disabled. If you have any questions, feel free to contact us." => "",

        #: storage/themes/default/gates/custom.php:12
        #: storage/themes/default/splash/edit.php:72
        "View site" => "",

        #: storage/themes/default/gates/custom.php:15
        "Seconds" => "",

        #: storage/themes/default/gates/custom.php:20
        "Powered by" => "",

        #: storage/themes/default/gates/domain.php:8
        "Custom domain working" => "",

        #: storage/themes/default/gates/domain.php:9
        "Your <strong>domain name</strong> is now successfully pointed to our server. You can now start using it from the platform and shorten branded links with your own domain name." => "",

        #: storage/themes/default/gates/domain.php:10
        "If you want to display another page instead of this page when someone accesses your root domain name, you can define that link in your settings by logging in to your account. You can also define a custom 404 error page." => "",

        #: storage/themes/default/gates/domain.php:11
        "If you have any questions, please do not hesitate to contact us." => "",

        #: storage/themes/default/gates/domain.php:16
        #: storage/themes/default/pages/bio.php:13
        #: storage/themes/default/pages/qr.php:13
        "Contact us" => "",

        #: storage/themes/default/gates/frame.php:18
        "Share" => "",

        #: storage/themes/default/gates/frame.php:19
        "Tweet" => "",

        #: storage/themes/default/gates/frame.php:20
        #: storage/themes/default/layouts/dashboard.php:130
        "Close" => "",

        #: storage/themes/default/gates/media.php:26
        "Short URL" => "",

        #: storage/themes/default/gates/media.php:33
        #: storage/themes/default/gates/media.php:34
        "Share on" => "",

        #: storage/themes/default/gates/password.php:14
        "Enter Password" => "",

        #: storage/themes/default/gates/password.php:15
        "The access to this URL is restricted. Please enter your password to view it." => "",

        #: storage/themes/default/gates/password.php:31
        "Unlock" => "",

        #: storage/themes/default/gates/profile.php:139
        #: storage/themes/default/overlay/create_contact.php:83
        #: storage/themes/default/overlay/create_contact.php:169
        #: storage/themes/default/pages/contact.php:35
        #: storage/themes/default/pages/report.php:40
        "Send" => "",

        #: storage/themes/default/gates/profile.php:147
        "Download vCard" => "",

        #: storage/themes/default/gates/splash.php:13
        "You are about to be redirected to another page." => "",

        #: storage/themes/default/gates/splash.php:24
        "Redirect me" => "",

        #: storage/themes/default/gates/splash.php:27
        #: storage/themes/default/pages/consent.php:17
        "Take me to your homepage" => "",

        #: storage/themes/default/gates/splash.php:32
        "You are about to be redirected to another page. We are not responsible for the content of that page or the consequences it may have on you." => "",

        #: storage/themes/default/index.php:15
        "Paste a long url" => "",

        #: storage/themes/default/index.php:18
        #: storage/themes/default/partials/shortener.php:18
        "Shorten" => "",

        #: storage/themes/default/index.php:27
        #: storage/themes/default/partials/shortener.php:64
        #: storage/themes/default/user/edit.php:301
        "Type your custom alias here" => "",

        #: storage/themes/default/index.php:44
        "Your link has been successfully shortened. Want to more customization options?" => "",

        #: storage/themes/default/index.php:45
        "Get started" => "",

        #: storage/themes/default/index.php:77
        "Your latest links" => "",

        #: storage/themes/default/index.php:85
        "Want more options to customize the link, QR codes, branding and advanced metrics?" => "",

        #: storage/themes/default/index.php:108
        "Latest links" => "",

        #: storage/themes/default/index.php:127
        "One short link, infinite possibilities." => "",

        #: storage/themes/default/index.php:130
        "A short link is a powerful marketing tool when you use it carefully. It is not just a link but a medium between your customer and their destination. A short link allows you to collect so much data about your customers and their behaviors." => "",

        #: storage/themes/default/index.php:144
        "Smart Targeting" => "",

        #: storage/themes/default/index.php:146
        "Target your customers to increase your reach and redirect them to a relevant page. Add a pixel to retarget them in your social media ad campaign to capture them." => "",

        #: storage/themes/default/index.php:161
        "In-Depth Analytics" => "",

        #: storage/themes/default/index.php:163
        "Share your links to your network and measure data to optimize your marketing campaign's performance. Reach an audience that fits your needs." => "",

        #: storage/themes/default/index.php:178
        "Digital Experience" => "",

        #: storage/themes/default/index.php:180
        "Use various powerful tools increase conversion and provide a non-intrusive experience to your customers without disengaging them." => "",

        #: storage/themes/default/index.php:194 storage/themes/default/index.php:240
        "Perfect for sales & marketing" => "",

        #: storage/themes/default/index.php:196 storage/themes/default/index.php:393
        "Understanding your users and customers will help you increase your conversion. Our system allows you to track everything. Whether it is the amount of clicks, the country or the referrer, the data is there for you to analyze it." => "",

        #: storage/themes/default/index.php:207
        "Redirection Tools" => "",

        #: storage/themes/default/index.php:219
        "Powerful Statistics" => "",

        #: storage/themes/default/index.php:231
        "Beautiful Profiles" => "",

        #: storage/themes/default/index.php:249 storage/themes/default/index.php:295
        #: storage/themes/default/index.php:306
        "Powerful tools that work" => "",

        #: storage/themes/default/index.php:251
        "Our product lets your target your users to better understand their behavior and provide them a better overall experience through smart re-targeting. We provide you many powerful tools to reach them better." => "",

        #: storage/themes/default/index.php:262
        #: storage/themes/default/partials/sidebar_menu.php:38
        "Link Management" => "",

        #: storage/themes/default/index.php:274
        "Privacy Control" => "",

        #: storage/themes/default/index.php:286
        "Powerful Dashboard" => "",

        #: storage/themes/default/index.php:332 storage/themes/default/index.php:336
        "New York, United States" => "",

        #: storage/themes/default/index.php:334 storage/themes/default/index.php:354
        #: storage/themes/default/index.php:374
        "Someone visited your link" => "",

        #: storage/themes/default/index.php:342 storage/themes/default/index.php:362
        #: storage/themes/default/index.php:382
        "{d} minutes ago" => "",

        #: storage/themes/default/index.php:352 storage/themes/default/index.php:356
        "Paris, France" => "",

        #: storage/themes/default/index.php:372 storage/themes/default/index.php:376
        "London, United Kingdom" => "",

        #: storage/themes/default/index.php:391
        "Optimize your marketing strategy" => "",

        #: storage/themes/default/index.php:404
        "More features than asked for" => "",

        #: storage/themes/default/index.php:417
        "Custom Landing Page" => "",

        #: storage/themes/default/index.php:419
        "Create a custom landing page to promote your product or service on forefront and engage the user in your marketing campaign." => "",

        #: storage/themes/default/index.php:434
        "CTA Overlays" => "",

        #: storage/themes/default/index.php:436
        "Use our overlay tool to display unobtrusive notifications, polls or even a contact on the target website. Great for campaigns." => "",

        #: storage/themes/default/index.php:451
        #: storage/themes/default/pricing/index.php:57
        #: storage/themes/default/user/billing.php:81
        "Event Tracking" => "",

        #: storage/themes/default/index.php:453
        "Add your custom pixel from providers such as Facebook and track events right when they are happening." => "",

        #: storage/themes/default/index.php:468
        "Team Management" => "",

        #: storage/themes/default/index.php:470
        "Invite your team members and assign them specific privileges to manage links, bundles, pages and other features." => "",

        #: storage/themes/default/index.php:485
        "Branded Domain Names" => "",

        #: storage/themes/default/index.php:487
        "Easily add your own domain name for short your links and take control of your brand name and your users' trust." => "",

        #: storage/themes/default/index.php:502
        "Robust API" => "",

        #: storage/themes/default/index.php:504
        "Use our powerful API to build custom applications or extend your own application with our powerful tools." => "",

        #: storage/themes/default/index.php:521
        "Integrations" => "",

        #: storage/themes/default/index.php:597
        "What our customers say about us" => "",

        #: storage/themes/default/index.php:630
        "Powering" => "",

        #: storage/themes/default/index.php:640
        "Serving" => "",

        #: storage/themes/default/index.php:650
        "Trusted by" => "",

        #: storage/themes/default/index.php:655
        "Happy Customers" => "",

        #: storage/themes/default/integrations/shortcuts.php:10
        "Shortcuts in an app developed by Apple and it allows you to create an automation. You can download our powerful Shortcut and you will be able to shorten links in a snap and save it directly in your account." => "",

        #: storage/themes/default/integrations/shortcuts.php:12
        "How does it work?" => "",

        #: storage/themes/default/integrations/shortcuts.php:14
        "The Shortcut works in various ways:" => "",

        #: storage/themes/default/integrations/shortcuts.php:16
        "Safari" => "",

        #: storage/themes/default/integrations/shortcuts.php:18
        "If you want shorten the current viewing page, tap the share icon at the bottom of the screen and it will shorten the current URL. It will copy the short URL directly to your clipboard so you can paste it somewhere." => "",

        #: storage/themes/default/integrations/shortcuts.php:20
        "Siri" => "",

        #: storage/themes/default/integrations/shortcuts.php:22
        "To use Siri, copy a link and ask Siri \"Shorten Link\" and it will shorten the link for you and copy it to your clipboard." => "",

        #: storage/themes/default/integrations/shortcuts.php:24
        "Manual" => "",

        #: storage/themes/default/integrations/shortcuts.php:26
        "You can also run the Shortcut by just holding a link then tap Share and you will see Shorten Link in the list." => "",

        #: storage/themes/default/integrations/shortcuts.php:32
        "How to install it?" => "",

        #: storage/themes/default/integrations/shortcuts.php:35
        "Make sure you have the Shortcuts app, if not you can download it from the App Store: " => "",

        #: storage/themes/default/integrations/shortcuts.php:35
        "Download Shortcuts" => "",

        #: storage/themes/default/integrations/shortcuts.php:36
        "Download our Shortcut" => "",

        #: storage/themes/default/integrations/shortcuts.php:38
        "After installation, you will be presented with a configuration screen where you need to enter the API URL and the API key" => "",

        #: storage/themes/default/integrations/shortcuts.php:40
        "API URL" => "",

        #: storage/themes/default/integrations/shortcuts.php:41
        "API Key" => "",

        #: storage/themes/default/integrations/shortcuts.php:44
        "After the configuration is complete, you can start shortening links directly from your device in a single tap." => "",

        #: storage/themes/default/integrations/slack.php:7
        "Connected" => "",

        #: storage/themes/default/integrations/slack.php:14
        "You can integrate this app with your Slack account and shorten directly from the Slack interface using the command line below. This Slack integration will save all of your links in your account in case you need to access them later. Please see below how to use the command." => "",

        #: storage/themes/default/integrations/slack.php:22
        "Slack Command" => "",

        #: storage/themes/default/integrations/slack.php:25
        "Example" => "",

        #: storage/themes/default/integrations/slack.php:28
        "Example with custom name" => "",

        #: storage/themes/default/integrations/slack.php:29
        "To send a custom alias, use the following parameter (ABCDXYZ). This will tell the script to choose shorten the link with the custom alias ABCDXYZ." => "",

        #: storage/themes/default/integrations/slack.php:32
        "The Slack command will return you the short link if everything goes well. In case there is an error, it will return you the original link. If you use the custom parameter and the alias is already taken, the script will automatically increment the alias by 1 - this means if you choose 'google' and 'google' is not available, the script will use google-1" => "",

        #: storage/themes/default/integrations/wordpress.php:12
        #: storage/themes/default/integrations/wordpress.php:22
        "WordPress Plugin" => "",

        #: storage/themes/default/integrations/wordpress.php:13
        "WordPress Function" => "",

        #: storage/themes/default/integrations/wordpress.php:25
        "You can easily use a shortcode to shorten links with our WordPress plugin. You just need to download it below and upload it in WordPress and that is it. There is no need to configure it as it will be already configured for you. All of your links will be saved in your account." => "",

        #: storage/themes/default/integrations/wordpress.php:27
        "Do not share this plugin with anyone you do not trust as they will have access to the full API and essentially your account." => "",

        #: storage/themes/default/integrations/wordpress.php:29
        #: storage/themes/default/integrations/wordpress.php:58
        "Instructions" => "",

        #: storage/themes/default/integrations/wordpress.php:31
        "Download the plugin below" => "",

        #: storage/themes/default/integrations/wordpress.php:32
        #: storage/themes/default/integrations/wordpress.php:61
        "Go to WordPress Admin" => "",

        #: storage/themes/default/integrations/wordpress.php:32
        "Plugins" => "",

        #: storage/themes/default/integrations/wordpress.php:32
        "Add New" => "",

        #: storage/themes/default/integrations/wordpress.php:32
        "Upload Plugin" => "",

        #: storage/themes/default/integrations/wordpress.php:33
        "Upload the plugin named linkshortenershortcode.zip and activate it. The plugin will be named Link Shortener Shortcode." => "",

        #: storage/themes/default/integrations/wordpress.php:36
        "Download Plugin" => "",

        #: storage/themes/default/integrations/wordpress.php:40
        #: storage/themes/default/integrations/wordpress.php:101
        #: storage/themes/default/user/tools.php:25
        "Examples" => "",

        #: storage/themes/default/integrations/wordpress.php:42
        #: storage/themes/default/integrations/wordpress.php:103
        "The following code will apply the shortcode \"shorturl\" to the system and you will be able to use the following format." => "",

        #: storage/themes/default/integrations/wordpress.php:45
        #: storage/themes/default/integrations/wordpress.php:106
        "You can also use the shortcode in html." => "",

        #: storage/themes/default/integrations/wordpress.php:53
        "WordPress Shortcode Function" => "",

        #: storage/themes/default/integrations/wordpress.php:56
        "You can now shorten links directly from WordPress using shortcode. If you don't want to upload a plugin, you can use this method. It is very easy to setup and it works with all versions of WordPress and with any theme. All links you will shorten will be safely stored in your account." => "",

        #: storage/themes/default/integrations/wordpress.php:60
        "Copy your unique php code below" => "",

        #: storage/themes/default/integrations/wordpress.php:61
        "Theme File Editor" => "",

        #: storage/themes/default/integrations/wordpress.php:62
        "On the right side, under Theme Files, find and click on Theme Functions (functions.php)" => "",

        #: storage/themes/default/integrations/wordpress.php:63
        "Paste the code below at the very end of the file and save" => "",

        #: storage/themes/default/integrations/wordpress.php:65
        "Your Unique Code" => "",

        #: storage/themes/default/integrations/wordpress.php:67
        "Do not share this code with anyone you do not trust as they will have access to the full API and essentially your account." => "",

        #: storage/themes/default/integrations/zapier.php:15
        "You can use Zapier to automate campaigns. By adding the URL to the zapier webhook, we will send you important information to that webhook so you can use them." => "",

        #: storage/themes/default/integrations/zapier.php:16
        #: storage/themes/default/partials/links.php:71
        "Note" => "",

        #: storage/themes/default/integrations/zapier.php:16
        "Although this tool is designed for Zapier, it can be used for any webhook system." => "",

        #: storage/themes/default/integrations/zapier.php:19
        #: storage/themes/default/integrations/zapier.php:36
        "URL Zapier Notification" => "",

        #: storage/themes/default/integrations/zapier.php:21
        "We will send a notification to this URL when you create a short URL." => "",

        #: storage/themes/default/integrations/zapier.php:24
        #: storage/themes/default/integrations/zapier.php:40
        "Views Zapier Notification" => "",

        #: storage/themes/default/integrations/zapier.php:26
        "We will send a notification to this URL when someone clicks your URL." => "",

        #: storage/themes/default/integrations/zapier.php:29
        "Save" => "",

        #: storage/themes/default/integrations/zapier.php:35
        "Sample Response" => "",

        #: storage/themes/default/invoice.php:1 storage/themes/default/invoice.php:25
        "Invoice" => "",

        #: storage/themes/default/invoice.php:29
        "Payment Date" => "",

        #: storage/themes/default/invoice.php:38
        "Bill to" => "",

        #: storage/themes/default/invoice.php:49
        "Payment To" => "",

        #: storage/themes/default/invoice.php:66 storage/themes/default/invoice.php:77
        "Subscription" => "",

        #: storage/themes/default/invoice.php:84
        #: storage/themes/default/overlay/edit_poll.php:126
        #: storage/themes/default/pricing/checkout.php:130
        "Total" => "",

        #: storage/themes/default/layouts/api.php:61
        #: storage/themes/default/layouts/auth.php:31
        #: storage/themes/default/layouts/dashboard.php:119
        #: storage/themes/default/layouts/main.php:63
        "This website uses cookies to ensure you get the best experience on our website." => "",

        #: storage/themes/default/layouts/api.php:62
        #: storage/themes/default/layouts/auth.php:32
        #: storage/themes/default/layouts/dashboard.php:120
        #: storage/themes/default/layouts/main.php:64
        "Got it!" => "",

        #: storage/themes/default/layouts/api.php:63
        #: storage/themes/default/layouts/auth.php:33
        #: storage/themes/default/layouts/dashboard.php:121
        #: storage/themes/default/layouts/main.php:65
        #: storage/themes/default/overlay/create_message.php:49
        #: storage/themes/default/overlay/create_message.php:121
        #: storage/themes/default/overlay/edit_message.php:49
        "Learn more" => "",

        #: storage/themes/default/layouts/api.php:65
        #: storage/themes/default/layouts/auth.php:35
        #: storage/themes/default/layouts/dashboard.php:122
        #: storage/themes/default/layouts/main.php:67
        "The coupon enter is not valid" => "",

        #: storage/themes/default/layouts/api.php:66
        #: storage/themes/default/layouts/auth.php:36
        #: storage/themes/default/layouts/dashboard.php:123
        #: storage/themes/default/layouts/main.php:68
        "You must select at least 1 url." => "",

        #: storage/themes/default/layouts/api.php:68
        #: storage/themes/default/layouts/auth.php:38
        #: storage/themes/default/layouts/dashboard.php:125
        #: storage/themes/default/layouts/main.php:70
        "No data is available for this request." => "",

        #: storage/themes/default/layouts/dashboard.php:28
        "You are logged in as another user" => "",

        #: storage/themes/default/layouts/dashboard.php:29
        "Return to my account" => "",

        #: storage/themes/default/layouts/dashboard.php:42
        #: storage/themes/default/partials/sidebar_menu.php:93
        "Teams" => "",

        #: storage/themes/default/layouts/dashboard.php:78
        #: storage/themes/default/partials/footer.php:74
        #: storage/themes/default/partials/main_menu.php:27
        #: storage/themes/default/partials/sidebar_menu.php:113
        #: storage/themes/default/pricing/index.php:74
        #: storage/themes/default/teams/edit.php:67
        #: storage/themes/default/teams/index.php:175
        #: storage/themes/default/user/billing.php:96
        "Developer API" => "",

        #: storage/themes/default/layouts/dashboard.php:83
        #: storage/themes/default/partials/footer.php:101
        "Report" => "",

        #: storage/themes/default/layouts/dashboard.php:116
        "The selected image is not valid. Please select a jpg or png with a maximum size of 1mb" => "",

        #: storage/themes/default/layouts/dashboard.php:127
        "Are you sure you want to proceed?" => "",

        #: storage/themes/default/layouts/dashboard.php:128
        "Proceed" => "",

        #: storage/themes/default/layouts/dashboard.php:131
        "Note that this action is permanent. Once you click proceed, you <strong>may not undo</strong> this. Click anywhere outside this modal or click <a href='#close' class='close-modal'>close</a> to close this." => "",

        #: storage/themes/default/maintenance.php:8
        "BRB" => "",

        #: storage/themes/default/maintenance.php:10
        "We are currently offline for maintenance. We will be back online as soon as we are done. It should not take long." => "",

        #: storage/themes/default/overlay/create.php:19
        #: storage/themes/default/overlay/create_contact.php:148
        #: storage/themes/default/overlay/create_coupon.php:81
        #: storage/themes/default/overlay/create_image.php:67
        #: storage/themes/default/overlay/create_message.php:110
        #: storage/themes/default/overlay/create_newsletter.php:101
        #: storage/themes/default/overlay/create_poll.php:100
        #: storage/themes/default/overlay/edit_image.php:67
        #: storage/themes/default/splash/create.php:58
        "Create" => "",

        #: storage/themes/default/overlay/create.php:40
        #: storage/themes/default/overlay/index.php:72
        "What is a CTA overlay?" => "",

        #: storage/themes/default/overlay/create.php:44
        #: storage/themes/default/overlay/index.php:76
        "An overlay page allows you to display a small non-intrusive overlay on the destination website to advertise your product or your services. You can also use this feature to send a message to your users. You can customize the message and the appearance of the overlay right from this page. As soon as you save it, the changes will be applied immediately across all your URLs using this type. Please note that some secured and sensitive websites such as google.com or facebook.com do not work with this feature. You can have unlimited overlay pages and you can choose one for each URL." => "",

        #: storage/themes/default/overlay/create_contact.php:18
        #: storage/themes/default/overlay/edit_contact.php:18
        "Send Email Address" => "",

        #: storage/themes/default/overlay/create_contact.php:19
        #: storage/themes/default/overlay/edit_contact.php:19
        "Emails from the form will be sent to this address" => "",

        #: storage/themes/default/overlay/create_contact.php:26
        #: storage/themes/default/overlay/edit_contact.php:26
        "Email Subject" => "",

        #: storage/themes/default/overlay/create_contact.php:27
        #: storage/themes/default/overlay/edit_contact.php:27
        "Something you would know where it comes from." => "",

        #: storage/themes/default/overlay/create_contact.php:32
        #: storage/themes/default/overlay/create_newsletter.php:18
        #: storage/themes/default/overlay/edit_contact.php:32
        #: storage/themes/default/overlay/edit_newsletter.php:18
        "Form Label" => "",

        #: storage/themes/default/overlay/create_contact.php:32
        #: storage/themes/default/overlay/create_contact.php:40
        #: storage/themes/default/overlay/create_contact.php:46
        #: storage/themes/default/overlay/create_message.php:33
        #: storage/themes/default/overlay/create_message.php:41
        #: storage/themes/default/overlay/create_message.php:48
        #: storage/themes/default/overlay/create_newsletter.php:18
        #: storage/themes/default/overlay/create_newsletter.php:26
        #: storage/themes/default/overlay/create_newsletter.php:32
        #: storage/themes/default/overlay/create_poll.php:53
        #: storage/themes/default/overlay/edit_message.php:33
        #: storage/themes/default/overlay/edit_message.php:41
        #: storage/themes/default/overlay/edit_message.php:48
        #: storage/themes/default/overlay/edit_newsletter.php:18
        #: storage/themes/default/overlay/edit_newsletter.php:26
        #: storage/themes/default/overlay/edit_newsletter.php:32
        #: storage/themes/default/overlay/edit_poll.php:52
        "leave empty to disable" => "",

        #: storage/themes/default/overlay/create_contact.php:33
        #: storage/themes/default/overlay/create_newsletter.php:19
        #: storage/themes/default/overlay/edit_contact.php:33
        #: storage/themes/default/overlay/edit_newsletter.php:19
        "e.g. Need help?" => "",

        #: storage/themes/default/overlay/create_contact.php:40
        #: storage/themes/default/overlay/create_newsletter.php:26
        #: storage/themes/default/overlay/edit_contact.php:40
        #: storage/themes/default/overlay/edit_newsletter.php:26
        "Form Description" => "",

        #: storage/themes/default/overlay/create_contact.php:41
        #: storage/themes/default/overlay/create_newsletter.php:27
        #: storage/themes/default/overlay/edit_contact.php:41
        #: storage/themes/default/overlay/edit_newsletter.php:27
        "(optional) Provide a description or anything you want to add to the form." => "",

        #: storage/themes/default/overlay/create_contact.php:46
        #: storage/themes/default/overlay/create_newsletter.php:32
        #: storage/themes/default/overlay/create_poll.php:53
        #: storage/themes/default/overlay/edit_contact.php:46
        #: storage/themes/default/overlay/edit_newsletter.php:32
        #: storage/themes/default/overlay/edit_poll.php:52
        "Thank You Message" => "",

        #: storage/themes/default/overlay/create_contact.php:47
        #: storage/themes/default/overlay/edit_contact.php:47
        "e.g. Thank you. We will respond asap." => "",

        #: storage/themes/default/overlay/create_contact.php:55
        #: storage/themes/default/overlay/create_newsletter.php:47
        #: storage/themes/default/overlay/create_poll.php:41
        #: storage/themes/default/overlay/edit_contact.php:55
        #: storage/themes/default/overlay/edit_contact.php:58
        #: storage/themes/default/overlay/edit_newsletter.php:47
        #: storage/themes/default/overlay/edit_poll.php:40
        "Text Labels" => "",

        #: storage/themes/default/overlay/create_contact.php:61
        #: storage/themes/default/overlay/edit_contact.php:62
        "Name Placeholder" => "",

        #: storage/themes/default/overlay/create_contact.php:63
        #: storage/themes/default/overlay/create_contact.php:70
        #: storage/themes/default/overlay/create_contact.php:77
        #: storage/themes/default/overlay/create_contact.php:84
        #: storage/themes/default/overlay/create_newsletter.php:55
        #: storage/themes/default/overlay/edit_contact.php:64
        #: storage/themes/default/overlay/edit_contact.php:71
        #: storage/themes/default/overlay/edit_contact.php:78
        #: storage/themes/default/overlay/edit_contact.php:85
        #: storage/themes/default/overlay/edit_newsletter.php:55
        "If you want to use a different language, change these." => "",

        #: storage/themes/default/overlay/create_contact.php:68
        #: storage/themes/default/overlay/edit_contact.php:69
        "Email Placeholder" => "",

        #: storage/themes/default/overlay/create_contact.php:75
        #: storage/themes/default/overlay/edit_contact.php:76
        "Message Placeholder" => "",

        #: storage/themes/default/overlay/create_contact.php:82
        #: storage/themes/default/overlay/edit_contact.php:83
        "Send Button Placeholder" => "",

        #: storage/themes/default/overlay/create_contact.php:92
        #: storage/themes/default/overlay/create_coupon.php:43
        #: storage/themes/default/overlay/create_image.php:44
        #: storage/themes/default/overlay/create_message.php:57
        #: storage/themes/default/overlay/create_newsletter.php:63
        #: storage/themes/default/overlay/create_poll.php:62
        #: storage/themes/default/overlay/edit_contact.php:93
        #: storage/themes/default/overlay/edit_coupon.php:43
        #: storage/themes/default/overlay/edit_image.php:44
        #: storage/themes/default/overlay/edit_message.php:57
        #: storage/themes/default/overlay/edit_newsletter.php:63
        #: storage/themes/default/overlay/edit_poll.php:61
        "Appearance Customization" => "",

        #: storage/themes/default/overlay/create_contact.php:98
        #: storage/themes/default/overlay/edit_contact.php:99
        "Form Background Color" => "",

        #: storage/themes/default/overlay/create_contact.php:104
        #: storage/themes/default/overlay/edit_contact.php:105
        "Form Text Color" => "",

        #: storage/themes/default/overlay/create_contact.php:110
        #: storage/themes/default/overlay/edit_contact.php:111
        "Input Background Color" => "",

        #: storage/themes/default/overlay/create_contact.php:116
        #: storage/themes/default/overlay/edit_contact.php:117
        "Input Text Color" => "",

        #: storage/themes/default/overlay/create_contact.php:122
        #: storage/themes/default/overlay/create_coupon.php:61
        #: storage/themes/default/overlay/create_message.php:87
        #: storage/themes/default/overlay/create_newsletter.php:81
        #: storage/themes/default/overlay/create_poll.php:80
        #: storage/themes/default/overlay/edit_contact.php:123
        #: storage/themes/default/overlay/edit_coupon.php:61
        #: storage/themes/default/overlay/edit_message.php:87
        #: storage/themes/default/overlay/edit_newsletter.php:81
        #: storage/themes/default/overlay/edit_poll.php:79
        "Button Background Color" => "",

        #: storage/themes/default/overlay/create_contact.php:128
        #: storage/themes/default/overlay/create_coupon.php:67
        #: storage/themes/default/overlay/create_message.php:93
        #: storage/themes/default/overlay/create_newsletter.php:87
        #: storage/themes/default/overlay/create_poll.php:86
        #: storage/themes/default/overlay/edit_contact.php:129
        #: storage/themes/default/overlay/edit_coupon.php:67
        #: storage/themes/default/overlay/edit_message.php:93
        #: storage/themes/default/overlay/edit_newsletter.php:87
        #: storage/themes/default/overlay/edit_poll.php:85
        "Button Text Color" => "",

        #: storage/themes/default/overlay/create_contact.php:134
        #: storage/themes/default/overlay/create_coupon.php:73
        #: storage/themes/default/overlay/create_image.php:56
        #: storage/themes/default/overlay/create_message.php:99
        #: storage/themes/default/overlay/create_newsletter.php:93
        #: storage/themes/default/overlay/create_poll.php:92
        #: storage/themes/default/overlay/edit_contact.php:135
        #: storage/themes/default/overlay/edit_coupon.php:73
        #: storage/themes/default/overlay/edit_image.php:56
        #: storage/themes/default/overlay/edit_message.php:99
        #: storage/themes/default/overlay/edit_newsletter.php:93
        #: storage/themes/default/overlay/edit_poll.php:91
        "Overlay Position" => "",

        #: storage/themes/default/overlay/create_contact.php:136
        #: storage/themes/default/overlay/create_coupon.php:75
        #: storage/themes/default/overlay/create_image.php:60
        #: storage/themes/default/overlay/create_message.php:103
        #: storage/themes/default/overlay/create_newsletter.php:95
        #: storage/themes/default/overlay/create_poll.php:94
        #: storage/themes/default/overlay/edit_contact.php:137
        #: storage/themes/default/overlay/edit_coupon.php:75
        #: storage/themes/default/overlay/edit_image.php:60
        #: storage/themes/default/overlay/edit_message.php:103
        #: storage/themes/default/overlay/edit_newsletter.php:95
        #: storage/themes/default/overlay/edit_poll.php:93
        "Bottom Left" => "",

        #: storage/themes/default/overlay/create_contact.php:137
        #: storage/themes/default/overlay/create_coupon.php:76
        #: storage/themes/default/overlay/create_image.php:61
        #: storage/themes/default/overlay/create_message.php:104
        #: storage/themes/default/overlay/create_newsletter.php:96
        #: storage/themes/default/overlay/create_poll.php:95
        #: storage/themes/default/overlay/edit_contact.php:138
        #: storage/themes/default/overlay/edit_coupon.php:76
        #: storage/themes/default/overlay/edit_image.php:61
        #: storage/themes/default/overlay/edit_message.php:104
        #: storage/themes/default/overlay/edit_newsletter.php:96
        #: storage/themes/default/overlay/edit_poll.php:94
        "Bottom Right" => "",

        #: storage/themes/default/overlay/create_contact.php:142
        #: storage/themes/default/overlay/create_contact.php:174
        #: storage/themes/default/overlay/create_newsletter.php:39
        #: storage/themes/default/overlay/create_newsletter.php:121
        #: storage/themes/default/overlay/edit_contact.php:143
        #: storage/themes/default/overlay/edit_contact.php:175
        #: storage/themes/default/overlay/edit_newsletter.php:39
        #: storage/themes/default/overlay/edit_newsletter.php:130
        "Webhook Notification" => "",

        #: storage/themes/default/overlay/create_contact.php:144
        #: storage/themes/default/overlay/create_newsletter.php:41
        #: storage/themes/default/overlay/edit_contact.php:145
        #: storage/themes/default/overlay/edit_newsletter.php:41
        "If you want to receive a notification directly to your app, add the url to your app's handler and as soon as there is a submission, we will send a notification to this url as well as an email to the address provided above." => "",

        #: storage/themes/default/overlay/create_contact.php:177
        #: storage/themes/default/overlay/edit_contact.php:178
        "If you add a webhook url, we will send a notification to that url with the contact form data. You will be able to integrate it with your own app or a third-party app. Below is a sample data that will be sent in <code>JSON</code> format via a <code>POST</code> request." => "",

        #: storage/themes/default/overlay/create_coupon.php:18
        #: storage/themes/default/overlay/edit_coupon.php:18
        "Coupon Code" => "",

        #: storage/themes/default/overlay/create_coupon.php:26
        #: storage/themes/default/overlay/create_message.php:27
        #: storage/themes/default/overlay/edit_coupon.php:26
        #: storage/themes/default/overlay/edit_message.php:27
        #: storage/themes/default/splash/create.php:51
        #: storage/themes/default/splash/edit.php:51
        "Custom Message" => "",

        #: storage/themes/default/overlay/create_coupon.php:27
        #: storage/themes/default/overlay/create_message.php:28
        #: storage/themes/default/overlay/edit_coupon.php:27
        #: storage/themes/default/overlay/edit_message.php:28
        #: storage/themes/default/splash/create.php:52
        #: storage/themes/default/splash/edit.php:52
        "Get a $10 discount with any purchase more than $50" => "",

        #: storage/themes/default/overlay/create_coupon.php:34
        #: storage/themes/default/overlay/create_message.php:48
        #: storage/themes/default/overlay/edit_coupon.php:34
        #: storage/themes/default/overlay/edit_message.php:48
        "Button Text" => "",

        #: storage/themes/default/overlay/create_coupon.php:49
        #: storage/themes/default/overlay/create_image.php:50
        #: storage/themes/default/overlay/create_message.php:63
        #: storage/themes/default/overlay/create_newsletter.php:69
        #: storage/themes/default/overlay/create_poll.php:68
        #: storage/themes/default/overlay/edit_coupon.php:49
        #: storage/themes/default/overlay/edit_image.php:50
        #: storage/themes/default/overlay/edit_message.php:63
        #: storage/themes/default/overlay/edit_newsletter.php:69
        #: storage/themes/default/overlay/edit_poll.php:67
        "Overlay Background Color" => "",

        #: storage/themes/default/overlay/create_coupon.php:55
        #: storage/themes/default/overlay/create_message.php:69
        #: storage/themes/default/overlay/create_newsletter.php:75
        #: storage/themes/default/overlay/create_poll.php:74
        #: storage/themes/default/overlay/edit_coupon.php:55
        #: storage/themes/default/overlay/edit_message.php:69
        #: storage/themes/default/overlay/edit_newsletter.php:75
        #: storage/themes/default/overlay/edit_poll.php:73
        "Overlay Text Color" => "",

        #: storage/themes/default/overlay/create_image.php:20
        #: storage/themes/default/overlay/edit_image.php:20
        "If you add a link here, the whole overlay will be linked to this when clicked." => "",

        #: storage/themes/default/overlay/create_image.php:27
        #: storage/themes/default/overlay/create_message.php:18
        #: storage/themes/default/overlay/edit_image.php:27
        #: storage/themes/default/overlay/edit_message.php:18
        #: storage/themes/default/qr/edit.php:475
        "Logo" => "",

        #: storage/themes/default/overlay/create_image.php:29
        #: storage/themes/default/overlay/create_message.php:20
        #: storage/themes/default/overlay/edit_image.php:29
        #: storage/themes/default/overlay/edit_message.php:20
        "Logo should be square with a maximum size of 100x100. To remove the image, click on the upload field and then cancel it." => "",

        #: storage/themes/default/overlay/create_image.php:34
        #: storage/themes/default/overlay/edit_image.php:34
        "Background Image" => "",

        #: storage/themes/default/overlay/create_image.php:36
        #: storage/themes/default/overlay/edit_image.php:36
        "Image should be rectangle with a maximum size of 600x150. To remove the image, click on the upload field and then cancel it." => "",

        #: storage/themes/default/overlay/create_image.php:58
        #: storage/themes/default/overlay/create_message.php:101
        #: storage/themes/default/overlay/edit_image.php:58
        #: storage/themes/default/overlay/edit_message.php:101
        "Top Left" => "",

        #: storage/themes/default/overlay/create_image.php:59
        #: storage/themes/default/overlay/create_message.php:102
        #: storage/themes/default/overlay/edit_image.php:59
        #: storage/themes/default/overlay/edit_message.php:102
        "Top Right" => "",

        #: storage/themes/default/overlay/create_image.php:62
        #: storage/themes/default/overlay/create_message.php:105
        #: storage/themes/default/overlay/edit_image.php:62
        #: storage/themes/default/overlay/edit_message.php:105
        "Bottom Center" => "",

        #: storage/themes/default/overlay/create_message.php:33
        #: storage/themes/default/overlay/edit_message.php:33
        "Overlay label" => "",

        #: storage/themes/default/overlay/create_message.php:41
        #: storage/themes/default/overlay/edit_message.php:41
        "Button Link" => "",

        #: storage/themes/default/overlay/create_message.php:43
        #: storage/themes/default/overlay/edit_message.php:43
        "If you remove the button text below but add a link here, the whole overlay will be linked to this when clicked." => "",

        #: storage/themes/default/overlay/create_message.php:75
        #: storage/themes/default/overlay/edit_message.php:75
        "Label Background Color" => "",

        #: storage/themes/default/overlay/create_message.php:81
        #: storage/themes/default/overlay/edit_message.php:81
        "Label Text Color" => "",

        #: storage/themes/default/overlay/create_message.php:116
        "Promo" => "",

        #: storage/themes/default/overlay/create_message.php:120
        "Your text here" => "",

        #: storage/themes/default/overlay/create_newsletter.php:33
        #: storage/themes/default/overlay/edit_newsletter.php:33
        "e.g. Thank you." => "",

        #: storage/themes/default/overlay/create_newsletter.php:53
        #: storage/themes/default/overlay/edit_newsletter.php:53
        "Button" => "",

        #: storage/themes/default/overlay/create_newsletter.php:54
        #: storage/themes/default/overlay/create_newsletter.php:114
        "Subscribe" => "",

        #: storage/themes/default/overlay/create_newsletter.php:124
        #: storage/themes/default/overlay/edit_newsletter.php:133
        "If you add a webhook url, we will send a notification to that url with the form data. You will be able to integrate it with your own app or a third-party app. Below is a sample data that will be sent in <code>JSON</code> format via a <code>POST</code> request." => "",

        #: storage/themes/default/overlay/create_poll.php:20
        #: storage/themes/default/overlay/edit_poll.php:20
        "Question" => "",

        #: storage/themes/default/overlay/create_poll.php:21
        #: storage/themes/default/overlay/edit_poll.php:21
        "e.g. What is your favorite color?" => "",

        #: storage/themes/default/overlay/create_poll.php:26
        #: storage/themes/default/overlay/edit_poll.php:26
        "Options" => "",

        #: storage/themes/default/overlay/create_poll.php:27
        #: storage/themes/default/overlay/edit_poll.php:27
        "You can add up to 10 options for each poll. To add an extra option click Add Option above. To ignore a field, leave it empty." => "",

        #: storage/themes/default/overlay/create_poll.php:36
        #: storage/themes/default/overlay/edit_poll.php:35
        "Add Option" => "",

        #: storage/themes/default/overlay/create_poll.php:47
        #: storage/themes/default/overlay/edit_poll.php:46
        "Vote Button Placeholder" => "",

        #: storage/themes/default/overlay/create_poll.php:54
        "Thanks..." => "",

        #: storage/themes/default/overlay/create_poll.php:106
        "Your question here?" => "",

        #: storage/themes/default/overlay/edit_contact.php:32
        #: storage/themes/default/overlay/edit_contact.php:40
        #: storage/themes/default/overlay/edit_contact.php:46
        "(leave empty to disable)" => "",

        #: storage/themes/default/overlay/edit_poll.php:116
        "Poll Results" => "",

        #: storage/themes/default/pages/affiliate.php:6
        "Earn {p} commission on affiliate sales" => "",

        #: storage/themes/default/pages/affiliate.php:7
        #, php-format
        "Refer customers to us and we will reward you a {p}% commission on all qualifying sales made on our website. Anyone can join the affiliate program." => "",

        #: storage/themes/default/pages/affiliate.php:9
        "View Affiliate Portal" => "",

        #: storage/themes/default/pages/affiliate.php:11
        "Join now" => "",

        #: storage/themes/default/pages/api.php:8
        #: storage/themes/default/pages/api.php:51
        "Getting Started" => "",

        #: storage/themes/default/pages/api.php:14
        #: storage/themes/default/pages/api.php:72
        "Authentication" => "",

        #: storage/themes/default/pages/api.php:20
        #: storage/themes/default/pages/api.php:114
        "Rate Limit" => "",

        #: storage/themes/default/pages/api.php:26
        #: storage/themes/default/pages/api.php:131
        "Response Handling" => "",

        #: storage/themes/default/pages/api.php:31
        #: storage/themes/default/partials/topbar_menu.php:43
        "Admin" => "",

        #: storage/themes/default/pages/api.php:54
        "An API key is required for requests to be processed by the system. Once a user registers, an API key is automatically generated for this user. The API key must be sent with each request (see full example below). If the API key is not sent or is expired, there will be an error. Please make sure to keep your API key secret to prevent abuse." => "",

        #: storage/themes/default/pages/api.php:60
        "Your API key" => "",

        #: storage/themes/default/pages/api.php:62
        "Regenerate API Key" => "",

        #: storage/themes/default/pages/api.php:62
        "If you proceed, your current applications will not work anymore. You will need to change your api key for it to work again." => "",

        #: storage/themes/default/pages/api.php:62
        #: storage/themes/default/user/settings.php:208
        #: storage/themes/default/user/settings.php:261
        "Regenerate" => "",

        #: storage/themes/default/pages/api.php:75
        "To authenticate with the API system, you need to send your API key as an authorization token with each request. You can see sample code below." => "",

        #: storage/themes/default/pages/api.php:117
        "Our API has a rate limiter to safeguard against spike in requests to maximize its stability. Our rate limiter is currently caped at {x} requests per {y} minute." => "",

        #: storage/themes/default/pages/api.php:119
        "Several headers will be sent alongside the response and these can be examined to determine various information about the request." => "",

        #: storage/themes/default/pages/api.php:134
        "All API response are returned in JSON format by default. To convert this into usable data, the appropriate function will need to be used according to the language. In PHP, the function json_decode() can be used to convert the data to either an object (default) or an array (set the second parameter to true). It is very important to check the error key as that provides information on whether there was an error or not. You can also check the header code." => "",

        #: storage/themes/default/pages/api.php:162
        "Parameter" => "",

        #: storage/themes/default/pages/api.php:216
        "Server response" => "",

        #: storage/themes/default/pages/bio.php:42
        "One link to rule them all" => "",

        #: storage/themes/default/pages/bio.php:44
        "Create beautiful profiles and add content like links, donation, videos and more for your social media users. Share a single on your social media profiles so your users can easily find all of your important links on a single page." => "",

        #: storage/themes/default/pages/bio.php:51
        #: storage/themes/default/pages/qr.php:25
        #: storage/themes/default/pages/qr.php:34
        "The new standard" => "",

        #: storage/themes/default/pages/bio.php:60
        "Track and optimize" => "",

        #: storage/themes/default/pages/bio.php:62
        "Profiles are fully trackable and you can find out exactly how many people have visited your profiles or clicked links on your profile and where they are from." => "",

        #: storage/themes/default/pages/bio.php:69
        #: storage/themes/default/pages/qr.php:43
        #: storage/themes/default/pages/qr.php:52
        "Trackable to the dot" => "",

        #: storage/themes/default/pages/consent.php:9
        "This website uses cookies to ensure you get the best experience on our website. You can learn more by visiting our cookie policy. Meanwhile if you agree, please click the button below to proceed to your destination." => "",

        #: storage/themes/default/pages/contact.php:7
        #: storage/themes/default/pages/contact.php:30
        "If you have any questions, feel free to contact us so we can help you" => "",

        #: storage/themes/default/pages/contact.php:22
        "Please enter a valid name." => "",

        #: storage/themes/default/pages/contact.php:30
        "The message is empty or too short." => "",

        #: storage/themes/default/pages/faq.php:17
        #: storage/themes/default/pages/faq.php:18
        "Quick Search" => "",

        #: storage/themes/default/pages/faq.php:57
        "Back to top" => "",

        #: storage/themes/default/pages/index.php:14
        "Last Updated" => "",

        #: storage/themes/default/pages/qr.php:27
        "QR Codes are everywhere and they are not going away. They are a great asset to your company because you can easily capture users and convert them. QR codes can be customized to match your company, brand or product." => "",

        #: storage/themes/default/pages/qr.php:45
        "The beautify of QR codes is that almost any type of data can be encoded in them. Most types of data can be tracked very easily so you will know exactly when and from where a person scanned your QR code." => "",

        #: storage/themes/default/pages/report.php:6
        "Report link" => "",

        #: storage/themes/default/pages/report.php:7
        "Please report a link that you consider risky or dangerous. We will review all cases and take measure to remove the link." => "",

        #: storage/themes/default/pages/report.php:26
        "Please enter a valid short link" => "",

        #: storage/themes/default/pages/report.php:29
        "Reason" => "",

        #: storage/themes/default/pages/report.php:31
        "Spam" => "",

        #: storage/themes/default/pages/report.php:32
        "Fraudulent" => "",

        #: storage/themes/default/pages/report.php:33
        "Malicious" => "",

        #: storage/themes/default/pages/report.php:34
        "Phishing" => "",

        #: storage/themes/default/partials/footer.php:8
        "Marketing with confidence." => "",

        #: storage/themes/default/partials/footer.php:57
        #: storage/themes/default/partials/main_menu.php:11
        #: storage/themes/default/partials/main_menu.php:52
        "Solutions" => "",

        #: storage/themes/default/partials/footer.php:67
        "Company" => "",

        #: storage/themes/default/partials/footer.php:72
        #: storage/themes/default/partials/main_menu.php:41
        #: storage/themes/default/partials/main_menu.php:121
        #: storage/themes/default/partials/topbar_menu.php:68
        "Help" => "",

        #: storage/themes/default/partials/links.php:6
        "More Info" => "",

        #: storage/themes/default/partials/links.php:11
        "Unarchive" => "",

        #: storage/themes/default/partials/links.php:13
        "Archive" => "",

        #: storage/themes/default/partials/links.php:16
        "Set Private" => "",

        #: storage/themes/default/partials/links.php:18
        "Set Public" => "",

        #: storage/themes/default/partials/links.php:22
        #: storage/themes/default/user/edit.php:10
        "Export Statistics" => "",

        #: storage/themes/default/partials/links.php:39
        "Disabled" => "",

        #: storage/themes/default/partials/links.php:42
        "Archived" => "",

        #: storage/themes/default/partials/links.php:50
        "Public" => "",

        #: storage/themes/default/partials/links.php:53
        #: storage/themes/default/user/edit.php:327
        "Campaign" => "",

        #: storage/themes/default/partials/links.php:56
        "Geo Targeted" => "",

        #: storage/themes/default/partials/links.php:59
        "Device Targeted" => "",

        #: storage/themes/default/partials/links.php:62
        "Protected" => "",

        #: storage/themes/default/partials/links.php:65
        "Expiry on" => "",

        #: storage/themes/default/partials/links.php:65
        #: storage/themes/default/user/billing.php:46
        #: storage/themes/default/user/billing.php:68
        "Expiration" => "",

        #: storage/themes/default/partials/links.php:68
        #: storage/themes/default/partials/shortener.php:112
        #: storage/themes/default/pixels/index.php:61
        #: storage/themes/default/pixels/new.php:40
        "Pixels" => "",

        #: storage/themes/default/partials/links.php:74
        #: storage/themes/default/partials/shortener.php:115
        "Parameters" => "",

        #: storage/themes/default/partials/links.php:79
        #: storage/themes/default/user/channel.php:41
        "Copied" => "",

        #: storage/themes/default/partials/links.php:84
        #: storage/themes/default/stats/partial.php:40
        "Unique Clicks" => "",

        #: storage/themes/default/partials/main_menu.php:3
        "Home" => "",

        #: storage/themes/default/partials/main_menu.php:7
        "Pricing" => "",

        #: storage/themes/default/partials/main_menu.php:17
        "Resources" => "",

        #: storage/themes/default/partials/main_menu.php:28
        "Guide on how to use our API" => "",

        #: storage/themes/default/partials/main_menu.php:42
        "Check out our frequently asked questions" => "",

        #: storage/themes/default/partials/main_menu.php:59
        "Customizable & trackable QR codes" => "",

        #: storage/themes/default/partials/main_menu.php:67
        "Convert your social media followers" => "",

        #: storage/themes/default/partials/main_menu.php:97
        #: storage/themes/default/partials/topbar_menu.php:44
        "Admin Panel" => "",

        #: storage/themes/default/partials/main_menu.php:111
        #: storage/themes/default/partials/topbar_menu.php:58
        #: storage/themes/default/user/campaigns.php:95
        #: storage/themes/default/user/settings.php:139
        "Public Profile" => "",

        #: storage/themes/default/partials/main_menu.php:117
        #: storage/themes/default/partials/topbar_menu.php:64
        #: storage/themes/default/user/affiliate.php:1
        "Affiliate" => "",

        #: storage/themes/default/partials/main_menu.php:123
        #: storage/themes/default/partials/topbar_menu.php:70
        "Log out" => "",

        #: storage/themes/default/partials/shortener.php:7
        "Paste a long link" => "",

        #: storage/themes/default/partials/shortener.php:10
        "Paste up to 10 long urls. One URL per line." => "",

        #: storage/themes/default/partials/shortener.php:41
        #: storage/themes/default/user/edit.php:275
        "Redirect" => "",

        #: storage/themes/default/partials/shortener.php:61
        "If you need a custom alias, you can enter it below." => "",

        #: storage/themes/default/partials/shortener.php:83
        #: storage/themes/default/user/edit.php:313
        "Link Expiration" => "",

        #: storage/themes/default/partials/shortener.php:84
        "Set an expiration date to disable the link." => "",

        #: storage/themes/default/partials/shortener.php:87
        #: storage/themes/default/user/edit.php:316
        "MM/DD/YYYY" => "",

        #: storage/themes/default/partials/shortener.php:94
        "This can be used to identify URLs on your account." => "",

        #: storage/themes/default/partials/shortener.php:97
        #: storage/themes/default/user/edit.php:323
        "Type your description here" => "",

        #: storage/themes/default/partials/shortener.php:104
        #: storage/themes/default/partials/shortener.php:121
        #: storage/themes/default/user/edit.php:28
        "Meta Tags" => "",

        #: storage/themes/default/partials/shortener.php:106
        #: storage/themes/default/partials/shortener.php:147
        #: storage/themes/default/user/edit.php:54
        "Geo Targeting" => "",

        #: storage/themes/default/partials/shortener.php:109
        #: storage/themes/default/partials/shortener.php:186
        #: storage/themes/default/pricing/index.php:52
        #: storage/themes/default/user/billing.php:76
        #: storage/themes/default/user/edit.php:124
        "Device Targeting" => "",

        #: storage/themes/default/partials/shortener.php:125
        #: storage/themes/default/user/edit.php:32
        "Custom Banner" => "",

        #: storage/themes/default/partials/shortener.php:126
        #: storage/themes/default/partials/shortener.php:132
        #: storage/themes/default/user/edit.php:33
        #: storage/themes/default/user/edit.php:39
        "Enter your custom meta title" => "",

        #: storage/themes/default/partials/shortener.php:138
        #: storage/themes/default/user/edit.php:45
        "Enter your custom meta description" => "",

        #: storage/themes/default/partials/shortener.php:149
        #: storage/themes/default/partials/shortener.php:188
        #: storage/themes/default/partials/shortener.php:237
        #: storage/themes/default/user/edit.php:56
        #: storage/themes/default/user/edit.php:126
        #: storage/themes/default/user/edit.php:193
        "+ Add" => "",

        #: storage/themes/default/partials/shortener.php:152
        #: storage/themes/default/user/edit.php:59
        "If you have different pages for different countries then it is possible to redirect users to that page using the same URL. Simply choose the country and enter the URL." => "",

        #: storage/themes/default/partials/shortener.php:166
        #: storage/themes/default/user/edit.php:75
        #: storage/themes/default/user/edit.php:104
        "All States" => "",

        #: storage/themes/default/partials/shortener.php:176
        #: storage/themes/default/partials/shortener.php:206
        #: storage/themes/default/user/edit.php:85
        #: storage/themes/default/user/edit.php:114
        #: storage/themes/default/user/edit.php:145
        #: storage/themes/default/user/edit.php:162
        "Type the url to redirect user to." => "",

        #: storage/themes/default/partials/shortener.php:192
        #: storage/themes/default/user/edit.php:130
        "If you have different pages for different devices (such as mobile, tablet etc) then it is possible to redirect users to that page using the same short URL. Simply choose the device and enter the URL." => "",

        #: storage/themes/default/partials/shortener.php:235
        #: storage/themes/default/user/edit.php:191
        "Parameter Builder" => "",

        #: storage/themes/default/partials/shortener.php:241
        #: storage/themes/default/user/edit.php:197
        "You can add custom parameters like UTM to the link above using this tool. Choose the parameter name and then assign a value. These will be added during redirection." => "",

        #: storage/themes/default/partials/shortener.php:247
        #: storage/themes/default/user/edit.php:204
        #: storage/themes/default/user/edit.php:219
        "Parameter name" => "",

        #: storage/themes/default/partials/shortener.php:253
        #: storage/themes/default/user/edit.php:210
        #: storage/themes/default/user/edit.php:225
        "Parameter value" => "",

        #: storage/themes/default/partials/shortener.php:262
        "Single" => "",

        #: storage/themes/default/partials/shortener.php:263
        "Multiple" => "",

        #: storage/themes/default/partials/sidebar_menu.php:34
        #: storage/themes/default/user/channels.php:40
        "My Channels" => "",

        #: storage/themes/default/partials/sidebar_menu.php:100
        "Tools & Integrations" => "",

        #: storage/themes/default/partials/stats_nav.php:3
        #: storage/themes/default/pricing/checkout.php:73
        "Summary" => "",

        #: storage/themes/default/partials/stats_nav.php:6
        "Countries & Cities" => "",

        #: storage/themes/default/partials/stats_nav.php:9
        #: storage/themes/default/stats/platforms.php:16
        #: storage/themes/default/user/campaignstats.php:85
        "Platforms" => "",

        #: storage/themes/default/partials/stats_nav.php:12
        #: storage/themes/default/stats/browsers.php:16
        "Browsers" => "",

        #: storage/themes/default/partials/stats_nav.php:15
        #: storage/themes/default/stats/languages.php:16
        "Languages" => "",

        #: storage/themes/default/partials/stats_nav.php:18
        "Referrers" => "",

        #: storage/themes/default/partials/topbar_menu.php:14
        "Dark Mode" => "",

        #: storage/themes/default/partials/topbar_menu.php:17
        "Light Mode" => "",

        #: storage/themes/default/partials/topbar_menu.php:33
        "{t} Notifications" => "",

        #: storage/themes/default/pixels/edit.php:6
        "View Links" => "",

        #: storage/themes/default/pixels/edit.php:18
        #: storage/themes/default/pixels/new.php:21
        "Pixel Name" => "",

        #: storage/themes/default/pixels/edit.php:19
        #: storage/themes/default/pixels/new.php:22
        "Shopify Campaign" => "",

        #: storage/themes/default/pixels/edit.php:24
        #: storage/themes/default/pixels/new.php:27
        "Pixel Tag" => "",

        #: storage/themes/default/pixels/edit.php:25
        #: storage/themes/default/pixels/new.php:28
        "Numerical or alphanumerical values only" => "",

        #: storage/themes/default/pixels/edit.php:29
        "Update Pixel" => "",

        #: storage/themes/default/pixels/index.php:7
        #: storage/themes/default/pixels/index.php:51
        #: storage/themes/default/pixels/new.php:1
        #: storage/themes/default/pixels/new.php:32
        "Add Pixel" => "",

        #: storage/themes/default/pixels/index.php:70
        #: storage/themes/default/pixels/new.php:49
        "What are tracking pixels?" => "",

        #: storage/themes/default/pixels/index.php:74
        #: storage/themes/default/pixels/new.php:53
        "Ad platforms such as Facebook and Adwords provide a conversion tracking tool to allow you to gather data on your customers and how they behave on your website. By adding your pixel ID from either of the platforms, you will be able to optimize marketing simply by using short URLs." => "",

        #: storage/themes/default/pixels/index.php:75
        #: storage/themes/default/pixels/new.php:54
        "More info" => "",

        #: storage/themes/default/pixels/new.php:11
        "Pixel Provider" => "",

        #: storage/themes/default/pricing/checkout.php:13
        "Payment Method" => "",

        #: storage/themes/default/pricing/checkout.php:28
        #: storage/themes/default/user/settings.php:94
        "Billing Address" => "",

        #: storage/themes/default/pricing/checkout.php:30
        #: storage/themes/default/user/settings.php:96
        "Full Name" => "",

        #: storage/themes/default/pricing/checkout.php:46
        #: storage/themes/default/user/settings.php:112
        "State/Province" => "",

        #: storage/themes/default/pricing/checkout.php:62
        #: storage/themes/default/user/settings.php:128
        "Zip/Postal code" => "",

        #: storage/themes/default/pricing/checkout.php:87
        "Subtotal" => "",

        #: storage/themes/default/pricing/checkout.php:94
        "Promo Code" => "",

        #: storage/themes/default/pricing/checkout.php:104
        "Apply promo code" => "",

        #: storage/themes/default/pricing/checkout.php:108
        "Discount" => "",

        #: storage/themes/default/pricing/checkout.php:134
        "One-time payment" => "",

        #: storage/themes/default/pricing/checkout.php:134
        "Billed" => "",

        #: storage/themes/default/pricing/checkout.php:145
        "By subscribing to this plan, you agree to our Terms & Conditions. Subscription is charged in {c}. If you have any questions, please contact us." => "",

        #: storage/themes/default/pricing/index.php:5
        "Simple Pricing" => "",

        #: storage/themes/default/pricing/index.php:7
        "Transparent pricing for everyone. Always know what you will pay." => "",

        #: storage/themes/default/pricing/index.php:21
        "Monthly" => "",

        #: storage/themes/default/pricing/index.php:25
        "Yearly" => "",

        #: storage/themes/default/pricing/index.php:42
        "Free" => "",

        #: storage/themes/default/pricing/index.php:47
        #: storage/themes/default/user/billing.php:73
        "URLs allowed" => "",

        #: storage/themes/default/pricing/index.php:48
        #: storage/themes/default/user/billing.php:74
        "Clicks per month" => "",

        #: storage/themes/default/pricing/index.php:49
        "Forever" => "",

        #: storage/themes/default/pricing/index.php:49
        "Data Retention" => "",

        #: storage/themes/default/pricing/index.php:50
        #: storage/themes/default/user/billing.php:72
        "Custom Aliases" => "",

        #: storage/themes/default/pricing/index.php:51
        #: storage/themes/default/user/billing.php:75
        "Geotargeting" => "",

        #: storage/themes/default/pricing/index.php:58
        #: storage/themes/default/teams/index.php:65
        #: storage/themes/default/user/billing.php:82
        "Team Members" => "",

        #: storage/themes/default/pricing/index.php:70
        #: storage/themes/default/user/billing.php:94
        "Campaigns & Link Rotator" => "",

        #: storage/themes/default/pricing/index.php:71
        "Multiple Domains" => "",

        #: storage/themes/default/pricing/index.php:72
        "Custom Parameters" => "",

        #: storage/themes/default/pricing/index.php:73
        #: storage/themes/default/teams/edit.php:70
        #: storage/themes/default/teams/index.php:178
        #: storage/themes/default/user/billing.php:95
        #: storage/themes/default/user/campaignstats.php:102
        #: storage/themes/default/user/stats.php:61
        "Export Data" => "",

        #: storage/themes/default/pricing/index.php:75
        #: storage/themes/default/user/billing.php:97
        "URL Customization" => "",

        #: storage/themes/default/pricing/index.php:76
        #: storage/themes/default/user/billing.php:98
        "Advertisement-Free" => "",

        #: storage/themes/default/private.php:5
        "Hello" => "",

        #: storage/themes/default/private.php:7
        "Thanks for your interest but this website is currently used privately." => "",

        #: storage/themes/default/qr/edit.php:9 storage/themes/default/qr/new.php:37
        "QR Code Name" => "",

        #: storage/themes/default/qr/edit.php:21 storage/themes/default/qr/new.php:49
        "Choose domain to generate the link with when using dynamic QR codes. Not applicable for static QR codes." => "",

        #: storage/themes/default/qr/edit.php:34 storage/themes/default/qr/edit.php:35
        #: storage/themes/default/qr/edit.php:129 storage/themes/default/qr/new.php:61
        #: storage/themes/default/qr/new.php:62
        "Your Text" => "",

        #: storage/themes/default/qr/edit.php:47 storage/themes/default/qr/new.php:72
        "Your Link" => "",

        #: storage/themes/default/qr/edit.php:64 storage/themes/default/qr/new.php:87
        "Subject" => "",

        #: storage/themes/default/qr/edit.php:65 storage/themes/default/qr/new.php:88
        #: storage/themes/default/qr/new.php:118
        "Job Application" => "",

        #: storage/themes/default/qr/edit.php:69 storage/themes/default/qr/new.php:92
        #: storage/themes/default/qr/new.php:157
        "Your message here to be sent as email" => "",

        #: storage/themes/default/qr/edit.php:90 storage/themes/default/qr/new.php:13
        #: storage/themes/default/qr/new.php:109
        "SMS & Message" => "",

        #: storage/themes/default/qr/edit.php:107 storage/themes/default/qr/new.php:27
        #: storage/themes/default/qr/new.php:124
        "SMS" => "",

        #: storage/themes/default/qr/edit.php:120 storage/themes/default/qr/new.php:29
        #: storage/themes/default/qr/new.php:148
        "Whatsapp" => "",

        #: storage/themes/default/qr/edit.php:149
        #: storage/themes/default/qr/edit.php:228
        #: storage/themes/default/qr/new.php:175 storage/themes/default/qr/new.php:252
        "Organization" => "",

        #: storage/themes/default/qr/edit.php:161
        #: storage/themes/default/qr/edit.php:240
        #: storage/themes/default/qr/new.php:187 storage/themes/default/qr/new.php:264
        "Website" => "",

        #: storage/themes/default/qr/edit.php:166
        #: storage/themes/default/qr/edit.php:245
        #: storage/themes/default/qr/new.php:192 storage/themes/default/qr/new.php:269
        "Social" => "",

        #: storage/themes/default/qr/edit.php:171
        #: storage/themes/default/qr/edit.php:250
        #: storage/themes/default/qr/new.php:197 storage/themes/default/qr/new.php:274
        "Street" => "",

        #: storage/themes/default/qr/edit.php:183
        #: storage/themes/default/qr/edit.php:262
        #: storage/themes/default/qr/new.php:209 storage/themes/default/qr/new.php:286
        "Zipcode" => "",

        #: storage/themes/default/qr/edit.php:206
        #: storage/themes/default/qr/edit.php:285
        #: storage/themes/default/qr/new.php:232 storage/themes/default/qr/new.php:309
        "Linekdin" => "",

        #: storage/themes/default/qr/edit.php:295 storage/themes/default/qr/new.php:14
        #: storage/themes/default/qr/new.php:317
        "WiFi" => "",

        #: storage/themes/default/qr/edit.php:299
        #: storage/themes/default/qr/new.php:321
        "Network SSID" => "",

        #: storage/themes/default/qr/edit.php:307
        #: storage/themes/default/qr/new.php:329
        "Encryption" => "",

        #: storage/themes/default/qr/edit.php:319
        #: storage/themes/default/qr/edit.php:323 storage/themes/default/qr/new.php:30
        #: storage/themes/default/qr/new.php:344
        "Cryptocurrency" => "",

        #: storage/themes/default/qr/edit.php:325
        #: storage/themes/default/qr/new.php:346
        "Bitcoin" => "",

        #: storage/themes/default/qr/edit.php:328
        #: storage/themes/default/qr/new.php:349
        "Ethereum" => "",

        #: storage/themes/default/qr/edit.php:331
        #: storage/themes/default/qr/new.php:352
        "Bitcoin Cash" => "",

        #: storage/themes/default/qr/edit.php:335
        #: storage/themes/default/qr/new.php:356
        "Wallet Address" => "",

        #: storage/themes/default/qr/edit.php:344
        #: storage/themes/default/qr/new.php:135
        "File Upload (Image or PDF)" => "",

        #: storage/themes/default/qr/edit.php:346
        "View File" => "",

        #: storage/themes/default/qr/edit.php:351
        #: storage/themes/default/qr/new.php:138
        "This can be used to upload an image or a PDF. Most common uses are restaurant menu, promotional poster and resume." => "",

        #: storage/themes/default/qr/edit.php:353 storage/themes/default/qr/new.php:28
        #: storage/themes/default/qr/new.php:140
        "File" => "",

        #: storage/themes/default/qr/edit.php:355
        #: storage/themes/default/qr/new.php:142
        "Acceptable file: jpg, png, gif, pdf. Max 2MB." => "",

        #: storage/themes/default/qr/edit.php:362
        #: storage/themes/default/qr/new.php:369
        "Colors" => "",

        #: storage/themes/default/qr/edit.php:383
        #: storage/themes/default/qr/new.php:390
        "Foreground" => "",

        #: storage/themes/default/qr/edit.php:411
        #: storage/themes/default/qr/new.php:418
        "Gradient Direction" => "",

        #: storage/themes/default/qr/edit.php:413
        #: storage/themes/default/qr/new.php:420
        "Vertical" => "",

        #: storage/themes/default/qr/edit.php:414
        #: storage/themes/default/qr/new.php:421
        "Horizontal" => "",

        #: storage/themes/default/qr/edit.php:415
        #: storage/themes/default/qr/new.php:422
        "Radial" => "",

        #: storage/themes/default/qr/edit.php:416
        #: storage/themes/default/qr/new.php:423
        "Diagonal" => "",

        #: storage/themes/default/qr/edit.php:423
        #: storage/themes/default/qr/new.php:430
        "Eye Color" => "",

        #: storage/themes/default/qr/edit.php:431
        #: storage/themes/default/qr/new.php:438
        "Design" => "",

        #: storage/themes/default/qr/edit.php:482
        #: storage/themes/default/qr/new.php:489
        "Matrix Style" => "",

        #: storage/themes/default/qr/edit.php:486
        #: storage/themes/default/qr/edit.php:503
        #: storage/themes/default/qr/new.php:493 storage/themes/default/qr/new.php:510
        "Square" => "",

        #: storage/themes/default/qr/edit.php:494
        #: storage/themes/default/qr/new.php:501
        "Dots" => "",

        #: storage/themes/default/qr/edit.php:499
        #: storage/themes/default/qr/new.php:506
        "Eye Style" => "",

        #: storage/themes/default/qr/edit.php:507
        #: storage/themes/default/qr/new.php:514
        "Circle" => "",

        #: storage/themes/default/qr/edit.php:518
        #: storage/themes/default/qr/new.php:525
        #: storage/themes/default/stats/partial.php:8
        #: storage/themes/default/user/index.php:134
        "QR Code" => "",

        #: storage/themes/default/qr/edit.php:529
        #: storage/themes/default/qr/new.php:536
        "You will be able to download the QR code in PDF or SVG after it has been generated." => "",

        #: storage/themes/default/qr/index.php:31
        "Download as PNG" => "",

        #: storage/themes/default/qr/index.php:32
        "Download as SVG" => "",

        #: storage/themes/default/qr/index.php:33
        "Download as PDF" => "",

        #: storage/themes/default/qr/index.php:52
        #: storage/themes/default/stats/partial.php:32
        "Scans" => "",

        #: storage/themes/default/qr/index.php:84
        "What are QR Codes?" => "",

        #: storage/themes/default/qr/index.php:88
        "A QR code is a machine-readable code consisting of an array of black and white squares, typically used for storing URLs or other information for reading by the camera on a smartphone." => "",

        #: storage/themes/default/qr/new.php:9
        "Static QR" => "",

        #: storage/themes/default/qr/new.php:9
        "Non-Trackable" => "",

        #: storage/themes/default/qr/new.php:15
        "Static vCard" => "",

        #: storage/themes/default/qr/new.php:20
        "Dynamic QR" => "",

        #: storage/themes/default/qr/new.php:20
        "Trackable" => "",

        #: storage/themes/default/qr/new.php:340
        "Crypto" => "",

        #: storage/themes/default/qr/new.php:357
        "Enter your public wallet address" => "",

        #: storage/themes/default/qr/new.php:363
        "Preview" => "",

        #: storage/themes/default/qr/new.php:482
        "Custom Logo" => "",

        #: storage/themes/default/qr/new.php:531
        "Generate QR" => "",

        #: storage/themes/default/splash/create.php:2
        #: storage/themes/default/splash/edit.php:2
        "A custom splash page is a transitional page where you can customize it however you want." => "",

        #: storage/themes/default/splash/create.php:18
        #: storage/themes/default/splash/edit.php:18
        "Counter" => "",

        #: storage/themes/default/splash/create.php:26
        #: storage/themes/default/splash/edit.php:26
        "Link to Product" => "",

        #: storage/themes/default/splash/create.php:32
        #: storage/themes/default/splash/edit.php:32
        "Custom Title" => "",

        #: storage/themes/default/splash/create.php:33
        #: storage/themes/default/splash/edit.php:33
        "Get a $10 discount" => "",

        #: storage/themes/default/splash/create.php:38
        #: storage/themes/default/splash/edit.php:38
        "Upload Banner" => "",

        #: storage/themes/default/splash/create.php:40
        #: storage/themes/default/splash/edit.php:40
        "The minimum width must be 980px and the height must be between 250 and 500. The format must be a PNG or a JPG. Maximum size is 500KB" => "",

        #: storage/themes/default/splash/create.php:45
        #: storage/themes/default/splash/edit.php:45
        "Upload Avatar" => "",

        #: storage/themes/default/splash/index.php:73
        "What is a custom splash page?" => "",

        #: storage/themes/default/splash/index.php:77
        "A custom splash page is a transitional page where you can add a banner and a logo along with a message to represent your brand or company. When creating a short link, you will be able to assign the page to your short url. Users who visit your url will briefly see the page before being redirected to their destination." => "",

        #: storage/themes/default/stats/browsers.php:19
        #: storage/themes/default/stats/countries.php:19
        #: storage/themes/default/stats/index.php:21
        #: storage/themes/default/stats/languages.php:19
        #: storage/themes/default/stats/platforms.php:19
        "Choose a date range to update stats" => "",

        #: storage/themes/default/stats/browsers.php:30
        "Top Browsers" => "",

        #: storage/themes/default/stats/countries.php:16
        #: storage/themes/default/user/campaignstats.php:51
        #: storage/themes/default/user/stats.php:36
        "Visitor Map" => "",

        #: storage/themes/default/stats/countries.php:30
        #: storage/themes/default/user/campaignstats.php:61
        #: storage/themes/default/user/stats.php:46
        "Top Countries" => "",

        #: storage/themes/default/stats/countries.php:42
        "Cities" => "",

        #: storage/themes/default/stats/countries.php:43
        "Select a region in the map above to display city data." => "",

        #: storage/themes/default/stats/index.php:36
        #: storage/themes/default/user/index.php:123
        "Recent Activity" => "",

        #: storage/themes/default/stats/index.php:59
        #: storage/themes/default/user/index.php:162
        "Direct, email or others" => "",

        #: storage/themes/default/stats/languages.php:30
        "Top Languages" => "",

        #: storage/themes/default/stats/partial.php:13
        #: storage/themes/default/user/index.php:137
        "Bio Page" => "",

        #: storage/themes/default/stats/partial.php:40
        "Unique Scans" => "",

        #: storage/themes/default/stats/partial.php:48
        "Top Country" => "",

        #: storage/themes/default/stats/partial.php:56
        "Top Referrer" => "",

        #: storage/themes/default/stats/platforms.php:30
        "Top Platforms" => "",

        #: storage/themes/default/stats/referrers.php:16
        "Top Referrers" => "",

        #: storage/themes/default/stats/referrers.php:22
        "Direct, email and others" => "",

        #: storage/themes/default/stats/referrers.php:31
        "Social Media" => "",

        #: storage/themes/default/teams/edit.php:1
        "Edit Member" => "",

        #: storage/themes/default/teams/edit.php:11
        #: storage/themes/default/teams/edit.php:12
        #: storage/themes/default/teams/index.php:119
        #: storage/themes/default/teams/index.php:120
        "Permissions" => "",

        #: storage/themes/default/teams/edit.php:14
        #: storage/themes/default/teams/index.php:122
        "Create Links" => "",

        #: storage/themes/default/teams/edit.php:15
        #: storage/themes/default/teams/index.php:123
        "Edit Links" => "",

        #: storage/themes/default/teams/edit.php:16
        #: storage/themes/default/teams/index.php:124
        "Delete Links" => "",

        #: storage/themes/default/teams/edit.php:22
        #: storage/themes/default/teams/index.php:130
        "Delete QR" => "",

        #: storage/themes/default/teams/edit.php:29
        #: storage/themes/default/teams/index.php:137
        "Delete Bio" => "",

        #: storage/themes/default/teams/edit.php:34
        #: storage/themes/default/teams/index.php:142
        "Create Splash" => "",

        #: storage/themes/default/teams/edit.php:35
        #: storage/themes/default/teams/index.php:143
        "Edit Splash" => "",

        #: storage/themes/default/teams/edit.php:36
        #: storage/themes/default/teams/index.php:144
        "Delete Splash" => "",

        #: storage/themes/default/teams/edit.php:41
        #: storage/themes/default/teams/index.php:149
        "Create Overlay" => "",

        #: storage/themes/default/teams/edit.php:42
        #: storage/themes/default/teams/index.php:150
        "Edit Overlay" => "",

        #: storage/themes/default/teams/edit.php:43
        #: storage/themes/default/teams/index.php:151
        "Delete Overlay" => "",

        #: storage/themes/default/teams/edit.php:48
        #: storage/themes/default/teams/index.php:156
        "Create Pixels" => "",

        #: storage/themes/default/teams/edit.php:49
        #: storage/themes/default/teams/index.php:157
        "Edit Pixels" => "",

        #: storage/themes/default/teams/edit.php:50
        #: storage/themes/default/teams/index.php:158
        "Delete Pixels" => "",

        #: storage/themes/default/teams/edit.php:55
        "Add Branded Domain" => "",

        #: storage/themes/default/teams/edit.php:56
        "Delete Branded Domain" => "",

        #: storage/themes/default/teams/edit.php:61
        #: storage/themes/default/teams/index.php:169
        "Create Campaigns" => "",

        #: storage/themes/default/teams/edit.php:62
        #: storage/themes/default/teams/index.php:170
        "Edit Campaigns" => "",

        #: storage/themes/default/teams/edit.php:63
        #: storage/themes/default/teams/index.php:171
        "Delete Campaigns" => "",

        #: storage/themes/default/teams/index.php:7
        #: storage/themes/default/teams/index.php:55
        #: storage/themes/default/teams/index.php:109
        "Add Member" => "",

        #: storage/themes/default/teams/index.php:34
        #: storage/themes/default/user/campaigns.php:32
        "Inactive" => "",

        #: storage/themes/default/teams/index.php:53
        "No members found. You can invite one." => "",

        #: storage/themes/default/teams/index.php:74
        "Permission" => "",

        #: storage/themes/default/teams/index.php:78
        "Create: A create event will allow your team member to shorten links, create splash pages & overlay and campaigns." => "",

        #: storage/themes/default/teams/index.php:79
        "Edit: An edit event will allow your team member to edit links, splash pages & overlay and campaigns." => "",

        #: storage/themes/default/teams/index.php:80
        "Delete: A delete event will allow your team member to delete links, splash pages & overlay and campaigns." => "",

        #: storage/themes/default/teams/index.php:162
        "Branded Domain" => "",

        #: storage/themes/default/teams/index.php:163
        "Add Custom Domain" => "",

        #: storage/themes/default/teams/index.php:164
        "Delete Custom Domain" => "",

        #: storage/themes/default/teams/index.php:185
        "Invite" => "",

        #: storage/themes/default/user/affiliate.php:6
        "Affiliate Link" => "",

        #: storage/themes/default/user/affiliate.php:19
        "Referral History" => "",

        #: storage/themes/default/user/affiliate.php:26
        "Commission" => "",

        #: storage/themes/default/user/affiliate.php:27
        "Referred On" => "",

        #: storage/themes/default/user/affiliate.php:28
        "Paid On" => "",

        #: storage/themes/default/user/affiliate.php:37
        "Approved" => "",

        #: storage/themes/default/user/affiliate.php:39
        "Paid" => "",

        #: storage/themes/default/user/affiliate.php:41
        "Rejected" => "",

        #: storage/themes/default/user/affiliate.php:43
        #: storage/themes/default/user/affiliate.php:47
        "Pending" => "",

        #: storage/themes/default/user/affiliate.php:59
        "Affiliate Rate" => "",

        #: storage/themes/default/user/affiliate.php:62
        "per qualifying sales" => "",

        #: storage/themes/default/user/affiliate.php:63
        "Minimum earning of {amount} is required for payment." => "",

        #: storage/themes/default/user/affiliate.php:66
        "Terms" => "",

        #: storage/themes/default/user/billing.php:7
        "Subscription History" => "",

        #: storage/themes/default/user/billing.php:13
        #: storage/themes/default/user/billing.php:43
        "Transaction ID" => "",

        #: storage/themes/default/user/billing.php:15
        "Since" => "",

        #: storage/themes/default/user/billing.php:16
        "Next Payment" => "",

        #: storage/themes/default/user/billing.php:17
        "Status" => "",

        #: storage/themes/default/user/billing.php:37
        "Payment History" => "",

        #: storage/themes/default/user/billing.php:45
        "Date" => "",

        #: storage/themes/default/user/billing.php:53
        "Refunded" => "",

        #: storage/themes/default/user/billing.php:54
        "Free Trial" => "",

        #: storage/themes/default/user/billing.php:70
        "Current Plan" => "",

        #: storage/themes/default/user/billing.php:101
        "Change plan" => "",

        #: storage/themes/default/user/billing.php:107
        #: storage/themes/default/user/billing.php:124
        "Cancel Membership" => "",

        #: storage/themes/default/user/billing.php:110
        "You can cancel your membership whenever your want. Upon request, your membership will be canceled right before your next payment period. This means you can still enjoy premium features until the end of your membership." => "",

        #: storage/themes/default/user/billing.php:111
        #: storage/themes/default/user/billing.php:140
        "Cancel membership" => "",

        #: storage/themes/default/user/billing.php:128
        "We respect your decision and we are sorry to see you go. If you want to share anything with us, please use the box below and we will do our best to improve our service." => "",

        #: storage/themes/default/user/billing.php:135
        "Reason for cancellation" => "",

        #: storage/themes/default/user/campaigns.php:7
        #: storage/themes/default/user/campaigns.php:76
        #: storage/themes/default/user/campaigns.php:117
        "Create a Campaign" => "",

        #: storage/themes/default/user/campaigns.php:22
        "Rotator" => "",

        #: storage/themes/default/user/campaigns.php:23
        "Quick Stats" => "",

        #: storage/themes/default/user/campaigns.php:49
        "views" => "",

        #: storage/themes/default/user/campaigns.php:50
        "links" => "",

        #: storage/themes/default/user/campaigns.php:88
        "Campaign List Disabled" => "",

        #: storage/themes/default/user/campaigns.php:92
        "To create a list page for the campaign, you need a default bio page and public profile settings." => "",

        #: storage/themes/default/user/campaigns.php:94
        "Default Bio" => "",

        #: storage/themes/default/user/campaigns.php:102
        "What is a campaign?" => "",

        #: storage/themes/default/user/campaigns.php:106
        "A campaign can be used to group links together for various purpose. You can use the dedicated rotator link where a random link will be chosen and redirected to among the group. You will also be able to view aggregated statistics for a campaign." => "",

        #: storage/themes/default/user/campaigns.php:123
        #: storage/themes/default/user/campaigns.php:162
        "Campaign Name" => "",

        #: storage/themes/default/user/campaigns.php:123
        #: storage/themes/default/user/campaigns.php:162
        #: storage/themes/default/user/channels.php:79
        #: storage/themes/default/user/channels.php:121
        "required" => "",

        #: storage/themes/default/user/campaigns.php:127
        #: storage/themes/default/user/campaigns.php:166
        "Rotator Slug" => "",

        #: storage/themes/default/user/campaigns.php:127
        #: storage/themes/default/user/campaigns.php:166
        "optional" => "",

        #: storage/themes/default/user/campaigns.php:129
        #: storage/themes/default/user/campaigns.php:168
        "If you want to set a custom alias for the rotator link, you can fill this field." => "",

        #: storage/themes/default/user/campaigns.php:133
        #: storage/themes/default/user/campaigns.php:172
        "Access" => "",

        #: storage/themes/default/user/campaigns.php:134
        #: storage/themes/default/user/campaigns.php:173
        "Disabling this option will deactivate the rotator link." => "",

        #: storage/themes/default/user/campaigns.php:143
        "Create Campaign" => "",

        #: storage/themes/default/user/campaigns.php:156
        #: storage/themes/default/user/campaigns.php:182
        #: storage/themes/default/user/channels.php:115
        "Update Campaign" => "",

        #: storage/themes/default/user/campaignstats.php:7
        #: storage/themes/default/user/stats.php:7
        "Export Stats" => "",

        #: storage/themes/default/user/campaignstats.php:16
        "Traffic Distribution" => "",

        #: storage/themes/default/user/campaignstats.php:73
        "Browser" => "",

        #: storage/themes/default/user/campaignstats.php:107
        #: storage/themes/default/user/stats.php:66
        "Choose a range to export data as CSV. Exported data will including information like date, city and country, os, browser, referer and language." => "",

        #: storage/themes/default/user/campaignstats.php:112
        #: storage/themes/default/user/links.php:76
        #: storage/themes/default/user/stats.php:71
        "Export" => "",

        #: storage/themes/default/user/channel.php:33
        "Remove from channel" => "",

        #: storage/themes/default/user/channel.php:59
        "Are you sure you want to remove this item from this channel?" => "",

        #: storage/themes/default/user/channel.php:63
        "You are trying to remove an item from a channel." => "",

        #: storage/themes/default/user/channels.php:7
        #: storage/themes/default/user/channels.php:102
        "Create Channel" => "",

        #: storage/themes/default/user/channels.php:12
        "Starred Channels" => "",

        #: storage/themes/default/user/channels.php:32
        #: storage/themes/default/user/channels.php:60
        "View Channel" => "",

        #: storage/themes/default/user/channels.php:73
        "Create a Channel" => "",

        #: storage/themes/default/user/channels.php:87
        #: storage/themes/default/user/channels.php:129
        "Badge Color" => "",

        #: storage/themes/default/user/channels.php:92
        #: storage/themes/default/user/channels.php:134
        "Star Channel" => "",

        #: storage/themes/default/user/channels.php:93
        #: storage/themes/default/user/channels.php:135
        "Starred channels will show up in the sidebar navigation for quick access." => "",

        #: storage/themes/default/user/edit.php:20
        "URL" => "",

        #: storage/themes/default/user/edit.php:289
        "Alias" => "",

        #: storage/themes/default/user/index.php:12
        #: storage/themes/default/user/index.php:22
        "Today" => "",

        #: storage/themes/default/user/index.php:34
        "Recent Clicks" => "",

        #: storage/themes/default/user/index.php:48
        "We are currently manually approving links. As soon as the link is approved, you will be able to start using it." => "",

        #: storage/themes/default/user/index.php:63
        #: storage/themes/default/user/links.php:11
        "Select All" => "",

        #: storage/themes/default/user/index.php:66
        #: storage/themes/default/user/links.php:14
        "Unarchive Selected" => "",

        #: storage/themes/default/user/index.php:68
        #: storage/themes/default/user/links.php:16
        "Archive Selected" => "",

        #: storage/themes/default/user/index.php:73
        #: storage/themes/default/user/index.php:262
        #: storage/themes/default/user/links.php:21
        #: storage/themes/default/user/links.php:168
        "Add to Campaign" => "",

        #: storage/themes/default/user/index.php:80
        #: storage/themes/default/user/links.php:28
        "Delete Selected" => "",

        #: storage/themes/default/user/index.php:88
        #: storage/themes/default/user/links.php:36
        "Search for links" => "",

        #: storage/themes/default/user/index.php:107
        "No links found. You can create some." => "",

        #: storage/themes/default/user/index.php:216
        #: storage/themes/default/user/links.php:122
        "Short Link Info" => "",

        #: storage/themes/default/user/index.php:251
        #: storage/themes/default/user/links.php:157
        "Done" => "",

        #: storage/themes/default/user/links.php:43
        "Sort Results" => "",

        #: storage/themes/default/user/links.php:45
        "Latest" => "",

        #: storage/themes/default/user/links.php:46
        "Oldest" => "",

        #: storage/themes/default/user/links.php:47
        "Most Popular" => "",

        #: storage/themes/default/user/links.php:48
        "Less Popular" => "",

        #: storage/themes/default/user/links.php:74
        "Export Links" => "",

        #: storage/themes/default/user/links.php:75
        "This tool allows you to generate a list of urls in CSV format. Some basic data such clicks will be included as well." => "",

        #: storage/themes/default/user/settings.php:5
        "You have used a social network to login. Please note that in this case you don't have a password set." => "",

        #: storage/themes/default/user/settings.php:9
        "You have used a social network to login. You will need to choose a username." => "",

        #: storage/themes/default/user/settings.php:20
        "Avatar" => "",

        #: storage/themes/default/user/settings.php:22
        "By default, we will use the Gravatar associated to your email. Uploaded avatars must be square with the width ranging from 200-500px with a maximum size of 500kb." => "",

        #: storage/themes/default/user/settings.php:37
        "Please note that if you change your email, you will need to activate your account again." => "",

        #: storage/themes/default/user/settings.php:45
        "A username is required for your public profile to be visible." => "",

        #: storage/themes/default/user/settings.php:54
        #: storage/themes/default/user/settings.php:61
        "Leave blank to keep current one." => "",

        #: storage/themes/default/user/settings.php:68
        "Default Domain" => "",

        #: storage/themes/default/user/settings.php:81
        "Default Redirection" => "",

        #: storage/themes/default/user/settings.php:140
        "Public profile will be activated only when this option is public." => "",

        #: storage/themes/default/user/settings.php:152
        "Media Gateway" => "",

        #: storage/themes/default/user/settings.php:153
        "If enabled, special pages will be automatically created for your media URLs (e.g. youtube, vimeo, dailymotion...)." => "",

        #: storage/themes/default/user/settings.php:166
        "If enabled, you will receive occasional newsletters from us." => "",

        #: storage/themes/default/user/settings.php:175
        "Save Settings" => "",

        #: storage/themes/default/user/settings.php:184
        #: storage/themes/default/user/settings.php:272
        "Two-Factor Authentication (2FA)" => "",

        #: storage/themes/default/user/settings.php:186
        "2FA is an enhanced level security for your account. Each time you login, an extra step where you will need to enter a unique code will be required to gain access to your account. To enable 2FA, please click the button below and download the <strong>Google Authenticator</strong> app from Apple Store or Play Store." => "",

        #: storage/themes/default/user/settings.php:189
        "View QR" => "",

        #: storage/themes/default/user/settings.php:192
        #: storage/themes/default/user/settings.php:282
        "Secret Key" => "",

        #: storage/themes/default/user/settings.php:195
        "Important" => "",

        #: storage/themes/default/user/settings.php:197
        "You need to scan the code above with the app. You need to backup the QR code by saving it and save the key somewhere safe in case you lose your phone. You will not be able to login if you can't provide the code, in that case you will need to contact us. If you disable 2FA and re-enable it, you will need to scan a new code." => "",

        #: storage/themes/default/user/settings.php:198
        "Disable 2FA" => "",

        #: storage/themes/default/user/settings.php:200
        "Activate 2FA" => "",

        #: storage/themes/default/user/settings.php:206
        #: storage/themes/default/user/settings.php:251
        "Developer API Key" => "",

        #: storage/themes/default/user/settings.php:213
        #: storage/themes/default/user/settings.php:226
        "Delete your account" => "",

        #: storage/themes/default/user/settings.php:214
        #: storage/themes/default/user/settings.php:231
        "We respect your privacy and as such you can delete your account permanently and remove all your data from our server. Please note that this action is permanent and cannot be reversed." => "",

        #: storage/themes/default/user/settings.php:215
        "Delete Permanently" => "",

        #: storage/themes/default/user/settings.php:256
        "If you regenerate your key, the current key will be revoked and your applications might stop working until you update the api key with the new one." => "",

        #: storage/themes/default/user/settings.php:278
        "You need to scan the code above with the app then enter the 6-digit number that you see in the app to activate 2FA. It is highly recommended to backup the unique key somewhere safe." => "",

        #: storage/themes/default/user/settings.php:293
        "Activate" => "",

        #: storage/themes/default/user/tools.php:7
        #: storage/themes/default/user/tools.php:18
        "Quick Shortener" => "",

        #: storage/themes/default/user/tools.php:8
        #: storage/themes/default/user/tools.php:38
        "Bookmarklet" => "",

        #: storage/themes/default/user/tools.php:9
        #: storage/themes/default/user/tools.php:57
        "Full-Page Script" => "",

        #: storage/themes/default/user/tools.php:20
        "This tool allows you to quickly shorten any URL in any page without using any fancy method. This is perhaps the quickest and the easiest method available for you to shorten URLs across all platforms. This method will generate a unique short URL for you that you will be able to access anytime from your dashboard." => "",

        #: storage/themes/default/user/tools.php:22
        "Use your quick URL below to shorten any URL by adding the URL after /q/?u=. <strong>For security reasons, you need to be logged in and using the remember me feature.</strong> Check out the examples below to understand how to use this method." => "",

        #: storage/themes/default/user/tools.php:28
        #: storage/themes/default/user/tools.php:47
        "Notes" => "",

        #: storage/themes/default/user/tools.php:30
        "Please note that this method does not return anything. It simply redirects the user to the redirection page. However if you need the actual short URL, you can always get it from your dashboard." => "",

        #: storage/themes/default/user/tools.php:40
        "You can use our bookmarklet tool to instantaneously shorten any site you are currently viewing and if you are logged in on our site, it will be automatically saved to your account for future access. Simply drag the following link to your bookmarks bar or copy the link and manually add it to your favorites." => "",

        #: storage/themes/default/user/tools.php:42
        "Drag me to your Bookmark Bar" => "",

        #: storage/themes/default/user/tools.php:42
        "Shorten URL" => "",

        #: storage/themes/default/user/tools.php:44
        "If you can't drag the link above, use your browser's bookmark editor to create a new bookmark and add the URL below as the link." => "",

        #: storage/themes/default/user/tools.php:49
        "Please note that for secured sites that use SSL, the widget will not pop up due to security issues. In that case, the user will be redirected our site where you will be able to view your short URL." => "",

        #: storage/themes/default/user/tools.php:59
        "This script allows you to shorten all (or select) URLs on your website very easily. All you need to do is to copy and paste the code below at the end of your page. You can customize the selector as you wish to target URLs in a specific selector. Note you can just  copy the code below because everything is already for you." => "",

        #: storage/themes/default/user/tools.php:63
        "Choosing a different domain" => "",

        #: storage/themes/default/user/tools.php:64
        "By default, the script uses the default domain on the platform however you can define a custom domain name to shorten links with. You need to make sure the domain is exactly the same as the domain added in the account, including the schema (http/https" => "",

        #: storage/themes/default/user/tools.php:68
        "Choosing custom selectors" => "",

        #: storage/themes/default/user/tools.php:69
        "By default, this script shortens all URLs in a page. If you want to target specific URLs then you can add a selector parameter. You can see an example below where the script will only shorten URLs having a class named mylink or all direct link in the .content container or all links in the .comments container" => "",

        #: storage/themes/default/user/tools.php:73
        "Excluding domain names" => "",

        #: storage/themes/default/user/tools.php:74
        "You can exclude domain names if you wish. You can add an exclude parameter to exclude domain names. The example below shortens all URLs but excludes URLs from google.com or apple.com" => "",

        #: storage/themes/default/user/tools.php:78
        "Restricting domain names" => "",

        #: storage/themes/default/user/tools.php:79
        "You can restrict domain names by adding an include parameter to restrict domain names. The example below shortens all URLs within the include domain name." => "",

        "If you change the domain name, the QR code will change!" => "",

        "Your password is too long. Passwords must be between 8 to 64 characters." => "",
        "Order Confirmation" => "",        
        "Transparent" => "",
        "Animation" => "",
        "Shake" => "",
        "Scale" => "",
        "Jello" => "",
        "Vibrate" => "",
        "Wobble" => "",
        "View Stats" => "",
        "Get Verified" => "",
        "You already requested a verification. As soon as we verify the document, we will let you know." => "",
        "Please upload a document so we can verify you." => "",
        "Please fill everything so we can verify you." => "",
        "Document must be either a PDF or a JPG (Max 2MB)." => "",
        "Document must be either a PDF or a JPG (Max 2MB)" => "",
        "Thank you. We will process your document as soon as possible and verify you." => "",
        "You cannot shorten URLs at the moment. Please upgrade to another plan." => "",
        "Display Avatar" => "",
        "Line Messenger" => "",
        "Add a heading with different sizes" => "",
        "Add a text body to your page" => "",
        "Separate your content with a line" => "",
        "Add a trackable button to a link" => "",
        "Add custom HTML code. Script codes are not accepted" => "",
        "Upload an image" => "",
        "Add a downloadable vCard" => "",
        "Generate a PayPal button to accept payments" => "",
        "Add button that will open whatsapp" => "",
        "Widgets" => "",
        "Add a dynamic RSS feed widget" => "",
        "Add a newsletter form to store emails" => "",
        "Add a contact form to receive emails" => "",
        "Add a widget to a product on your site" => "",
        "Embed a Youtube video" => "",
        "Embed a Spotify music widget" => "",
        "Embed an Apple music widget" => "",
        "TikTok Embed" => "",
        "Embed a tiktok video" => "",
        "Click the button add insert an Image then choose the file to upload" => "",
        "Choose domain to generate the link with" => "",
        "Leave this field empty to generate a random alias" => "",
        "By adding a password, you can restrict the access" => "",
        "Remove Logo" => "",
        "Verified Account" => "",
        "Language Targeted" => "",
        "Language Targeting" => "",
        "If you have different pages for different languages then it is possible to redirect users to that page using the same URL. Simply choose the language and enter the URL." => "",
        "Verified" => "",
        "Margin" => "",
        "Update Channel" => "",
        "items" => "",
        "Order Date" => "",
        "Order No" => "",
        "Need Help?" => "",
        "If you have different pages for different languages then it is possible to redirect users to that page using the same short URL. Simply choose the language and enter the URL." => "",
        "Sort By" => "",
        "Newest" => "",
        "Results Per Page" => "",
        "Older than" => "",
        "Filter" => "",
        "Company Name" => "",
        "You can get your account verified and this provides you several benefits. After we verify we your account, you will get a verified checkmark on your Bio Pages and your links will have a trusted status." => "",
        "All we need from you is a document that matches your name and address. Documents can be a national card, company bill or any other official document." => "",
        "Upload Document" => "",
        "2MB max, PDF or JPG" => "",
        "Submit" => "",
        "Verifications" => "",
    ]
];