<?php
use Illuminate\Support\Facades\Crypt;


function encryptString($plaintext) {
    return Crypt::encryptString($plaintext);
} 

function decryptString($ciphertext) {
    return Crypt::decryptString($ciphertext);
}