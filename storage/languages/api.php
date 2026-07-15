<?php
/**
 * English language for the API
 */
return [
    /**
     * Language Information
     */
    "code" => "en",
    "region" => "en_US",
    "name" => "API",
    "author" => "GemPixel",
    "link" => "https://gempixel.com",
    "date" => "29/01/2022",
    "rtl" => false,
    /**
     * Language Data
     */
    "data" => [
        #: app/config/api.php:30
        "Account" => "",
        #: app/config/api.php:34
        "Get Account" => "",
        #: app/config/api.php:37
        "To get information on the account, you can send a request to this endpoint and it will return data on the account." => "",
        #: app/config/api.php:54
        "Update Account" => "",
        #: app/config/api.php:57
        "To update information on the account, you can send a request to this endpoint and it will update data on the account." => "",
        #: app/config/api.php:72
        "Branded Domains" => "",
        #: app/config/api.php:76
        "List all branded domains" => "",
        #: app/config/api.php:79
        "To get your branded domains codes via the API, you can use this endpoint. You can also filter data (See table for more info)." => "",
        #: app/config/api.php:81 app/config/api.php:173 app/config/api.php:21
        #: app/config/api.php:255 app/config/api.php:477 app/config/api.php:571
        "(optional) Per page data result" => "",
        #: app/config/api.php:82 app/config/api.php:174 app/config/api.php:21
        #: app/config/api.php:256 app/config/api.php:478 app/config/api.php:572
        "(optional) Current page request" => "",
        #: app/config/api.php:111
        "Create a Branded Domain" => "",
        #: app/config/api.php:114
        "A domain can be added using this endpoint. Please make sure the domain is correctly pointed to our server." => "",
        #: app/config/api.php:116
        "(required) Branded domain including http or https" => "",
        #: app/config/api.php:117 app/config/api.php:136
        "(optional) Root redirect when someone visits your domain" => "",
        #: app/config/api.php:118 app/config/api.php:137
        "(optional) Custom 404 redirect" => "",
        #: app/config/api.php:131
        "Update a Domain" => "",
        #: app/config/api.php:134
        "To update a branded domain, you need to send a valid data in JSON via a PUT request. The data must be sent as the raw body of your request as shown below. The example below shows all the parameters you can send but you are not required to send all (See table for more info)." => "",
        #: app/config/api.php:149
        "Delete Domain" => "",
        #: app/config/api.php:152
        "To delete a domain, you need to send a DELETE request." => "",
        #: app/config/api.php:164
        "Custom Splash" => "",
        #: app/config/api.php:168
        "List all custom splash" => "",
        #: app/config/api.php:171
        "To get custom splash pages via the API, you can use this endpoint. You can also filter data (See table for more info)." => "",
        #: app/config/api.php:204
        "CTA Overlays" => "",
        #: app/config/api.php:208
        "List all cta overlays" => "",
        #: app/config/api.php:211
        "To get cta overlays via the API, you can use this endpoint. You can also filter data (See table for more info)." => "",
        #: app/config/api.php:246
        "Links" => "",
        #: app/config/api.php:250
        "List all Links" => "",
        #: app/config/api.php:253
        "To get your links via the API, you can use this endpoint. You can also filter data (See table for more info)." => "",
        #: app/config/api.php:257
        "(optional) Sort data between date or click" => "",
        #: app/config/api.php:294
        "Get a single link" => "",
        #: app/config/api.php:297
        "To get details for a single link via the API, you can use this endpoint." => "",
        #: app/config/api.php:335
        "Shorten a Link" => "",
        #: app/config/api.php:338
        "To shorten a link, you need to send a valid data in JSON via a POST request. The data must be sent as the raw body of your request as shown below. The example below shows all the parameters you can send but you are not required to send all (See table for more info)." => "",
        #: app/config/api.php:340 app/config/api.php:400
        "(required) Long URL to shorten." => "",
        #: app/config/api.php:341 app/config/api.php:401
        "(optional) Custom alias instead of random alias." => "",
        #: app/config/api.php:342
        "(optional) Redirection type [direct, frame, splash], only <i>id</i> for custom splash page or <i>overlay-id</i> for cta pages" => "",
        #: app/config/api.php:343 app/config/api.php:403
        "(optional) Password protection" => "",
        #: app/config/api.php:344 app/config/api.php:404
        "(optional) Custom Domain" => "",
        #: app/config/api.php:345 app/config/api.php:405
        "(optional) Expiration for the link example 2021-09-28 23:11:16" => "",
        #: app/config/api.php:346 app/config/api.php:406
        "(optional) Geo targeting data" => "",
        #: app/config/api.php:347 app/config/api.php:407
        "(optional) Device targeting data" => "",
        #: app/config/api.php:348
        "(optional) Meta title" => "",
        #: app/config/api.php:349
        "(optional) Meta description" => "",
        #: app/config/api.php:395
        "Update a Link" => "",
        #: app/config/api.php:398
        "To update a link, you need to send a valid data in JSON via a PUT request. The data must be sent as the raw body of your request as shown below. The example below shows all the parameters you can send but you are not required to send all (See table for more info)." => "",
        #: app/config/api.php:402
        "(optional) Redirection type [direct, frame, splash]" => "",
        #: app/config/api.php:453
        "Delete a Link" => "",
        #: app/config/api.php:456
        "To delete a link, you need to send a DELETE request." => "",
        #: app/config/api.php:468
        "Pixels" => "",
        #: app/config/api.php:472
        "List all pixels" => "",
        #: app/config/api.php:475
        "To get your pixels codes via the API, you can use this endpoint. You can also filter data (See table for more info)." => "",
        #: app/config/api.php:509
        "Create a Pixel" => "",
        #: app/config/api.php:512
        "A pixel can be created using this endpoint. You need to send the pixel type and the tag." => "",
        #: app/config/api.php:515
        "(required) Custom name for your pixel" => "",
        #: app/config/api.php:516 app/config/api.php:535
        "(required) The tag for the pixel" => "",
        #: app/config/api.php:529
        "Update a Pixel" => "",
        #: app/config/api.php:532
        "To update a pixel, you need to send a valid data in JSON via a PUT request. The data must be sent as the raw body of your request as shown below. The example below shows all the parameters you can send but you are not required to send all (See table for more info)." => "",
        #: app/config/api.php:534
        "(optional) Custom name for your pixel" => "",
        #: app/config/api.php:547
        "Delete Pixel" => "",
        #: app/config/api.php:550
        "To delete a pixel, you need to send a DELETE request." => "",
        #: app/config/api.php:562
        "QR Codes" => "",
        #: app/config/api.php:566
        "List all QR codes" => "",
        #: app/config/api.php:569
        "To get your QR codes via the API, you can use this endpoint. You can also filter data (See table for more info)." => "",
        #: app/config/api.php:603
        "Get a single QR Code" => "",
        #: app/config/api.php:606
        "To get details for a single QR code via the API, you can use this endpoint." => "",
        #: app/config/api.php:642
        "Create a QR Code" => "",
        #: app/config/api.php:645
        "To create a QR Code, you need to send a valid data in JSON via a POST request. The data must be sent as the raw body of your request as shown below. The example below shows all the parameters you can send but you are not required to send all (See table for more info)." => "",
        #: app/config/api.php:647
        "(required) text | vcard | link | email | phone | sms | wifi" => "",
        #: app/config/api.php:648 app/config/api.php:672
        "(required) Data to be embedded inside the QR code. The data can be string or array depending on the type" => "",
        #: app/config/api.php:649 app/config/api.php:673
        "(optional) RGB color e.g. rgb(255,255,255)" => "",
        #: app/config/api.php:650 app/config/api.php:674
        "(optional) RGB color e.g. rgb(0,0,0)" => "",
        #: app/config/api.php:651 app/config/api.php:675
        "(optional) Path to the logo either png or jpg" => "",
        #: app/config/api.php:667
        "Update a QR Code" => "",
        #: app/config/api.php:670
        "To update a QR Code, you need to send a valid data in JSON via a PUT request. The data must be sent as the raw body of your request as shown below. The example below shows all the parameters you can send but you are not required to send all (See table for more info)." => "",
        #: app/config/api.php:690
        "Delete a QR Code" => "",
        #: app/config/api.php:693
        "To delete a QR code, you need to send a DELETE request." => "",
        #: app/config/api.php:705
        "Plans" => "",
        #: app/config/api.php:706 app/config/api.php:864
        "This endpoint is only accessible by users with admin privileges." => "",
        #: app/config/api.php:709
        "List all plans" => "",
        #: app/config/api.php:712
        "Get a list of all plans on the platform." => "",
        #: app/config/api.php:842
        "Subscribe a user to a plan" => "",
        #: app/config/api.php:845
        "To subscribe a user to plan, send a PUT request to this endpoint with the plan id and user id. The type of subscription and the expiration date will need to be specified. If the expiration date is not specified, the date will be adjusted according to the type." => "",
        #: app/config/api.php:847
        "monthly | yearly | lifetime" => "",
        #: app/config/api.php:848
        "(optional) Expiration date of the plan e.g. " => "",
        #: app/config/api.php:863
        "Users" => "",
        #: app/config/api.php:867
        "List all users" => "",
        #: app/config/api.php:870
        "Get a list of all users on the platform. Data can be filtered by sending a filter parameter in the url." => "",
        #: app/config/api.php:872
        "admin | free | pro" => "",
        #: app/config/api.php:873
        "Search a user by email" => "",
        #: app/config/api.php:905
        "List a single user" => "",
        #: app/config/api.php:908
        "Get data for a single user." => "",
        #: app/config/api.php:927
        "Create a user" => "",
        #: app/config/api.php:930
        "To create a user, use this endpoint and send the following information as JSON." => "",
        #: app/config/api.php:932
        "(required) User's username. Needs to be valid." => "",
        #: app/config/api.php:933
        "(required) User's email. Needs to be valid." => "",
        #: app/config/api.php:934
        "(required) User's password. Minimum 5 characters." => "",
        #: app/config/api.php:935
        "(optional) Premium plan. This can be found in the admin panel." => "",
        #: app/config/api.php:936
        "(optional) Membership expiration example 2020-12-26 12:00:00" => "",
        #: app/config/api.php:956
        "Delete a user" => "",
        #: app/config/api.php:959
        "To delete a user, use this endpoint." => "",
        #: app/config/api.php:968
        "Login a user" => "",
        #: app/config/api.php:971
        "This endpoint will generate a unique link that will allow the user to automatically login to the platform. SSO login urls are valid for 1 hour and they can be used a single time." => "",
    ]
];