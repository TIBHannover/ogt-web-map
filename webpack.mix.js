const mix = require("laravel-mix");
const webpack = require("webpack");

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
mix.setResourceRoot(process.env.ASSET_URL || "");

/* set reverse proxy sub-path required for generated links to resources */
if (["production", "testing"].includes(process.env.APP_ENV)) {
    mix.webpackConfig({
        output: {
            publicPath: process.env.MIX_PROXY_PATH + "/",
        },
    });
}

mix.webpackConfig({
    plugins: [
        new webpack.DefinePlugin({
            __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: JSON.stringify(false),
            __VUE_OPTIONS_API__: JSON.stringify(true),
            __VUE_PROD_DEVTOOLS__: JSON.stringify(false),
        }),
    ],
});

mix.js("resources/js/app.js", "public/js")
    .vue({
        options: {
            compilerOptions: {
                //isCustomElement: (tag) => ['v-main', 'v-app', 'v-icon', 'v-button', 'v-container', 'v-btn', 'v-list', 'v-list-item-title', 'v-list-item', 'v-subheader', 'v-divider', 'v-list-item-subtitle', 'v-list-item-group', 'v-list-item-action', 'v-list-item-icon', 'v-list-item-content', 'v-navigation-drawer'].includes(tag),
            },
        },
    })
    .postCss("resources/css/app.css", "public/css", [
        //
    ]);
//.sourceMaps(); // excluded for now

mix.js("./node_modules/leaflet/dist/leaflet.js", "public/js").postCss(
    "./node_modules/leaflet/dist/leaflet.css",
    "public/css",
    [
        //
    ],
);

if (mix.inProduction()) {
    mix.version();
}
