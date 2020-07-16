let mix = require('laravel-mix');

mix
    .js('resources/js/cp.js', 'resources/dist/js')
    .setPublicPath('resources/dist');

// mix.js('SeoPro/resources/assets/src/js/scripts.js', 'SeoPro/resources/assets/js');
// mix.js('SeoPro/resources/assets/src/js/fieldtype.js', 'SeoPro/resources/assets/js');
