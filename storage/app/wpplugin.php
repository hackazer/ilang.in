<?php

/*
Plugin Name: Link Shortener Shortcode
Plugin URI: __URL__
Description: Shorten links directly using [shorturl] shortcode
Version: 1.0
Author: __AUTHOR__
Author URI: __URL__
*/

// This code simply registers the shortcode "shorturl". You can change it if you want something else 
add_shortcode("shorturl", "pus_shortcode_shorten_url");

// Function to send the request
function pus_shortcode_shorten_url($atts, $content){
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "__API__");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['url' => $content]));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer __KEY__",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    if($object = json_decode($response)){
        if(isset($object->shorturl)){
            return $object->shorturl;
        }
    }

    return $content;
}