let mix = require('laravel-mix');

mix
    .js('resources/js/cp.js', 'resources/dist/js')
    .setPublicPath('resources/dist');
