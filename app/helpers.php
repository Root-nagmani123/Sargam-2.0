<?php
use Illuminate\Support\Facades\Crypt;


function encryptString($plaintext) {
    return Crypt::encryptString($plaintext);
} 

function decryptString($ciphertext) {
    return Crypt::decryptString($ciphertext);
}

function view_file_link($path) {
    return $path ? asset('storage/' . $path) : null;
}

function format_date($date) {
    return \Carbon\Carbon::parse($date)->format('d/m/Y');
}
