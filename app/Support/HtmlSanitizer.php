<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Sanitises user/DB-authored rich text (notice descriptions, chat templates,
 * WYSIWYG output) before it is echoed raw with {!! !!}.
 *
 * Strips <script>, event-handler attributes (onclick, onerror, …),
 * javascript:/data: URLs, and any tag/attribute not on the allowlist —
 * while preserving the formatting a rich-text editor legitimately produces.
 *
 * Backed by ezyang/htmlpurifier (already in vendor).
 */
class HtmlSanitizer
{
    private static ?HTMLPurifier $purifier = null;

    public static function clean(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        return self::purifier()->purify($html);
    }

    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier !== null) {
            return self::$purifier;
        }

        $config = HTMLPurifier_Config::createDefault();

        // Allowlist: common rich-text formatting only. No <script>, <iframe>,
        // <form>, <object>, inline event handlers, or style attributes.
        $config->set('HTML.Allowed',
            'p,br,b,strong,i,em,u,s,sub,sup,'
            . 'ul,ol,li,blockquote,pre,code,hr,'
            . 'h1,h2,h3,h4,h5,h6,'
            . 'a[href|title|target|rel],'
            . 'span,div,'
            . 'table,thead,tbody,tr,th,td'
        );

        // Force safe link behaviour and block javascript:/data: schemes.
        $config->set('HTML.TargetBlank', true);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('Output.FlashCompat', false);

        // Cache compiled definitions in the app's writable storage.
        $cacheDir = storage_path('framework/cache/htmlpurifier');
        if (! is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }
        if (is_dir($cacheDir) && is_writable($cacheDir)) {
            $config->set('Cache.SerializerPath', $cacheDir);
        } else {
            $config->set('Cache.DefinitionImpl', null); // no disk cache fallback
        }

        return self::$purifier = new HTMLPurifier($config);
    }
}
