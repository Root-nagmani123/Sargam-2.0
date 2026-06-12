const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix compiles the app JS bundle and concatenates + minifies the global,
 | always-loaded admin stylesheets into a single request (public/css/app-global.css).
 |
 | NOTE on workflow: the global CSS sources still live in public/css and can be
 | edited directly. After editing any file in the GLOBAL_CSS list below, run
 | `npm run dev` (or `npm run watch`) to regenerate the bundle. pre_header loads
 | the bundle, so un-built edits to those files will not appear until you rebuild.
 |
 | All files in GLOBAL_CSS are verified to contain no relative url() references,
 | so concatenating them into public/css is path-safe.
 */

// Order matters: tokens first (defines CSS variables the rest consume).
const GLOBAL_CSS = [
    'public/css/tokens.css',
    'public/css/custom.css',
    'public/css/admin-header.css',
    'public/css/spacing-system.css',
    'public/css/breadcrumb.css',
    'public/css/sidebar-menu-enhanced.css',
];

mix.js('resources/js/app.js', 'public/js')
    .styles(GLOBAL_CSS, 'public/css/app-global.css');

// Minify in production builds (npm run prod).
if (mix.inProduction()) {
    mix.options({
        cssNano: {
            preset: ['default', { discardComments: { removeAll: true } }],
        },
    });
}
