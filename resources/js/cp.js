import SeoProFieldtype from './components/fieldtypes/SeoProFieldtype.vue';
import SourceFieldtype from './components/fieldtypes/SourceFieldtype.vue';
import StatusIcon from './components/reporting/StatusIcon.vue';
import IndexScore from './components/reporting/IndexScore.vue';
import Report from './components/reporting/Report.vue';
import Index from './pages/Index.vue';
import SectionDefaultsIndex from './pages/section-defaults/Index.vue';

Statamic.booting(() => {
    Statamic.$components.register('seo_pro-fieldtype', SeoProFieldtype);
    Statamic.$components.register('seo_pro_source-fieldtype', SourceFieldtype);

    Statamic.$components.register('seo-pro-status-icon', StatusIcon);
    Statamic.$components.register('seo-pro-report', Report);
    Statamic.$components.register('seo-pro-index-score', IndexScore);

    Statamic.$inertia.register('seo-pro::Index', Index);
    Statamic.$inertia.register('seo-pro::SectionDefaults/Index', SectionDefaultsIndex);
});
