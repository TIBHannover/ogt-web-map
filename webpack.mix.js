const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/ogt/js')
    .vue()
    .postCss('resources/css/app.css', 'public/ogt/css', [
        //
    ])
    .sourceMaps();

mix.js('./node_modules/leaflet/dist/leaflet.js', 'public/ogt/js')
    .postCss('./node_modules/leaflet/dist/leaflet.css', 'public/ogt/css', [
        //
    ]);

if (mix.inProduction()) {
    mix.version();
}
