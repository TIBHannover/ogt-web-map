const mix = require('laravel-mix');

//mix.setResourceRoot('');
//mix.setResourcePath('')
//mix.setPublicPath('');
//mix.browserSync('');
//__webpack_public_path__ = '';

if (['production', 'test'].includes(process.env.MIX_APP_ENV)) {
    mix.webpackConfig({
        output: {
            //chunkFilename: 'ogt/js/[name].js',
            publicPath: '/ogt/',
        },
    });
}

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

mix.js('resources/js/app.js', 'public/js')
    .vue()
    .postCss('resources/css/app.css', 'public/css', [
        //
    ]);

mix.js('./node_modules/leaflet/dist/leaflet.js', 'public/js')
    .postCss('./node_modules/leaflet/dist/leaflet.css', 'public/css', [
        //
    ]);

if (mix.inProduction()) {
    mix.version();
}
