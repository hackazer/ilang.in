<?php
/**
 * ====================================================================================
 *                           GemFramework (c) GemPixel
 * ----------------------------------------------------------------------------------
 *  This software is packaged with an exclusive framework owned by GemPixel Inc as such
 *  distribution or modification of this framework is not allowed before prior consent
 *  from GemPixel administrators. If you find that this framework is packaged in a 
 *  software not distributed by GemPixel or authorized parties, you must not use this
 *  software and contact GemPixel at https://gempixel.com/contact to inform them of this
 *  misuse otherwise you risk of being prosecuted in courts.
 * ====================================================================================
 *
 * @package CDNs
 * @author GemPixel (http://gempixel.com)
 * @copyright 2020 GemPixel
 * @license http://gempixel.com/license
 * @link http://gempixel.com  
 * @since 1.0
 */

return [
    'editor' => [
        'version' => '4.13.5',
        'js' => [
            assets('vendor/jodit/jodit.min.js'),
            assets('editor-adapter.js')
        ],
        'css' => [
            assets('vendor/jodit/jodit.min.css')
        ]
    ],
    'simpleeditor' => [
        'version' => '4.13.5',
        'js' => [
            assets('vendor/jodit/jodit.min.js'),
            assets('editor-adapter.js')
        ],
        'css' => [
            assets('vendor/jodit/jodit.min.css')
        ]
    ],
    'airdatepicker' => [
        'version' => '3.6.0',
        'js' => [
            assets('frontend/libs/air-datepicker/air-datepicker.js'),
            assets('date-picker.min.js')
        ],
        'css' => [
            assets('frontend/libs/air-datepicker/air-datepicker.css')
        ]
    ],
    'codeeditor' => [
        'version' => '1.44.0',
        'js' => [assets('frontend/libs/ace-builds/ace.js')]
    ],
    'coloris' => [
        'version' => '0.25.0',
        'js' => [
            assets('frontend/libs/coloris/coloris.min.js'),
            assets('color-picker.min.js')
        ],
        'css' => [assets('frontend/libs/coloris/coloris.min.css')]
    ],
    'autocomplete' => [
        'version' => '2.0.4',
        'js' => [assets('frontend/libs/devbridge-autocomplete/jquery.autocomplete.min.js')]
    ],
    "hljs" => [
        "version" => "11.11.1",
        "js" => [assets('frontend/libs/highlight.js/highlight.min.js')],
        "css" => [assets('frontend/libs/highlight.js/night-owl.min.css')]
    ],
    'blockadblock' => [
        'version' => '3.2.1',
        'js' => [assets('frontend/libs/blockadblock/blockadblock.min.js')]
    ]
];
