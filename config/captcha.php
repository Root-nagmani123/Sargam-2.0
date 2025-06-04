<?php
// Config file for captcha settings
return [
    'default'   => [
        'length'    => 5,
        'width'     => 200,
        'height'    => 36,
        'quality'   => 100,
        'math'      => false,  //Enable Math Captcha
        'expire'    => 300,    //Captcha expiration
        'lines' => 0,             // no lines
        'fontColors' => ['#000000'], // black text
    ],
];
