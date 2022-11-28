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

/* required for Leaflet layer control icon */
//mix.setResourceRoot(process.env.ASSET_URL ?? '');

/* set reverse proxy sub-path required for generated links to resources */
if (['production', 'testing'].includes(process.env.APP_ENV)) {
    // not required for local development for envs (local / production)
    // mix.setResourceRoot(process.env.ASSET_URL ?? '');
    mix.webpackConfig({
        output: {
            publicPath: process.env.MIX_PROXY_PATH + '/',
        },
    });
}

mix.js('resources/js/app.js', 'public/js')
    .vue()
    .postCss('resources/css/app.css', 'public/css', [
        //
    ]);
    //.sourceMaps(); // excluded for now

mix.js('./node_modules/leaflet/dist/leaflet.js', 'public/js')
    .postCss('./node_modules/leaflet/dist/leaflet.css', 'public/css', [
        //
    ]);

if (mix.inProduction()) {
    mix.version();
}
