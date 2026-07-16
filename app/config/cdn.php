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
        'version' => '4.13.3',
        'js' => [
            assets('vendor/jodit/jodit.min.js'),
            assets('editor-adapter.js')
        ],
        'css' => [
            assets('vendor/jodit/jodit.min.css')
        ]
    ],
    'simpleeditor' => [
        'version' => '4.13.3',
        'js' => [
            assets('vendor/jodit/jodit.min.js'),
            assets('editor-adapter.js')
        ],
        'css' => [
            assets('vendor/jodit/jodit.min.css')
        ]
    ],
    'datetimepicker' => [
        'version' => '1.0.10',
        'js' => [
            assets('frontend/libs/datepicker/datepicker.min.js')
        ],
        'css' => [
            assets('frontend/libs/datepicker/datepicker.min.css')
        ]
    ],
    'codeeditor' => [
        'version' => '1.44.0',
        'js' => [assets('frontend/libs/ace-builds/ace.js')]
    ],
    'spectrum' => [
        'version' => '1.8.1',
        'js' => [assets('frontend/libs/spectrum/spectrum.min.js')],
        'css' => [assets('frontend/libs/spectrum/spectrum.min.css')]
    ],
    'autocomplete' => [
        'version' => '2.0.4',
        'js' => [assets('frontend/libs/devbridge-autocomplete/jquery.autocomplete.min.js')]
    ],
    "daterangepicker" => [
        "version" => "3.1.0",
        "css" => [assets('frontend/libs/daterangepicker/daterangepicker.min.css')],
        "js" => [
            assets('frontend/libs/moment/moment.min.js'),
            assets('frontend/libs/daterangepicker/daterangepicker.min.js')
          ]
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
