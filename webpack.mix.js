const mix = require('laravel-mix');

//mix.setResourceRoot('ogt');
//mix.setResourcePath('')

//mix.setPublicPath('public/ogt');
//mix.setPublicPath('https://test.service.tib.eu/ogt');


/*
mix.browserSync({
    proxy: 'https://test.service.tib.eu/ogt',
});

//mix.browserSync('https://test.service.tib.eu/ogt');
*/
__webpack_public_path__ = 'https://test.service.tib.eu/ogt';

mix.webpackConfig({
    output: {
        chunkFilename: 'ogt/js/[name].js',
    },
    module: {
        rules: [
            {
                test: /\.(png)$/i,
                dependency: {not: ['url']},
                use: [
                    {
                        loader: 'url-loader',
                        options: {
                            publicPath: '/ogt/',
                        },
                    },
                ],
            },
        ],
    },
});

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
//.sourceMaps();

mix.js('./node_modules/leaflet/dist/leaflet.js', 'public/js')
    .postCss('./node_modules/leaflet/dist/leaflet.css', 'public/css', [
        //
    ]);

/*
if (mix.inProduction()) {
    mix.version();
}
*/
