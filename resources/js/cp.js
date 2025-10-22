import Index from './pages/Index.vue';
import ReportsIndex from './pages/reports/Index.vue';
import ReportsEmpty from './pages/reports/Empty.vue';
import ReportsShow from './pages/reports/Show.vue';
import SectionDefaultsIndex from './pages/section-defaults/Index.vue';
import SiteDefaultsEdit from './pages/site-defaults/Edit.vue';
import SeoProFieldtype from './components/fieldtypes/SeoProFieldtype.vue';
import SourceFieldtype from './components/fieldtypes/SourceFieldtype.vue';
import SeoProWidget from "./components/widgets/SeoProWidget.vue";

Statamic.booting(() => {
    Statamic.$inertia.register('seo-pro::Index', Index);
    Statamic.$inertia.register('seo-pro::Reports/Index', ReportsIndex);
    Statamic.$inertia.register('seo-pro::Reports/Empty', ReportsEmpty);
    Statamic.$inertia.register('seo-pro::Reports/Show', ReportsShow);
    Statamic.$inertia.register('seo-pro::SectionDefaults/Index', SectionDefaultsIndex);
    Statamic.$inertia.register('seo-pro::SiteDefaults/Edit', SiteDefaultsEdit);

    Statamic.$components.register('seo_pro-fieldtype', SeoProFieldtype);
    Statamic.$components.register('seo_pro_source-fieldtype', SourceFieldtype);

    Statamic.$components.register('seo-pro-widget', SeoProWidget);
});
