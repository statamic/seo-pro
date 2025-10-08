import SeoProFieldtype from './components/fieldtypes/SeoProFieldtype.vue';
import SourceFieldtype from './components/fieldtypes/SourceFieldtype.vue';
import StatusIcon from './components/reporting/StatusIcon.vue';
import IndexScore from './components/reporting/IndexScore.vue';
import Report from './components/reporting/Report.vue';

Statamic.booted(() => {
    Statamic.$components.register('seo_pro-fieldtype', SeoProFieldtype);
    Statamic.$components.register('seo_pro_source-fieldtype', SourceFieldtype);

    Statamic.$components.register('seo-pro-status-icon', StatusIcon);
    Statamic.$components.register('seo-pro-report', Report);
    Statamic.$components.register('seo-pro-index-score', IndexScore);
});
