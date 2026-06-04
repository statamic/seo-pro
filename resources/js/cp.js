import Index from './pages/Index.vue';
import ErrorsIndex from './pages/errors/Index.vue';
import RedirectsIndex from './pages/redirects/Index.vue';
import RedirectsEmpty from './pages/redirects/Empty.vue';
import RedirectsCreate from './pages/redirects/Create.vue';
import RedirectsEdit from './pages/redirects/Edit.vue';
import ReportsIndex from './pages/reports/Index.vue';
import ReportsEmpty from './pages/reports/Empty.vue';
import ReportsShow from './pages/reports/Show.vue';
import SectionDefaultsIndex from './pages/section-defaults/Index.vue';
import SectionDefaultsEdit from './pages/section-defaults/Edit.vue';
import SiteDefaultsEdit from './pages/site-defaults/Edit.vue';
import SeoProFieldtype from './components/fieldtypes/SeoProFieldtype.vue';
import PreviewsFieldtype from "./components/fieldtypes/PreviewsFieldtype.vue";
import SourceFieldtype from './components/fieldtypes/SourceFieldtype.vue';
import RedirectSourceFieldtype from './components/fieldtypes/RedirectSourceFieldtype.vue';
import SeoProWidget from "./components/widgets/SeoProWidget.vue";
import RecentErrorsWidget from "./components/widgets/RecentErrorsWidget.vue";

Statamic.booting(() => {
    Statamic.$inertia.register('seo-pro::Index', Index);
    Statamic.$inertia.register('seo-pro::Errors/Index', ErrorsIndex);
    Statamic.$inertia.register('seo-pro::Redirects/Index', RedirectsIndex);
    Statamic.$inertia.register('seo-pro::Redirects/Empty', RedirectsEmpty);
    Statamic.$inertia.register('seo-pro::Redirects/Create', RedirectsCreate);
    Statamic.$inertia.register('seo-pro::Redirects/Edit', RedirectsEdit);
    Statamic.$inertia.register('seo-pro::Reports/Index', ReportsIndex);
    Statamic.$inertia.register('seo-pro::Reports/Empty', ReportsEmpty);
    Statamic.$inertia.register('seo-pro::Reports/Show', ReportsShow);
    Statamic.$inertia.register('seo-pro::SectionDefaults/Index', SectionDefaultsIndex);
    Statamic.$inertia.register('seo-pro::SectionDefaults/Edit', SectionDefaultsEdit);
    Statamic.$inertia.register('seo-pro::SiteDefaults/Edit', SiteDefaultsEdit);

    Statamic.$components.register('seo_pro-fieldtype', SeoProFieldtype);
    Statamic.$components.register('seo_pro_previews-fieldtype', PreviewsFieldtype);
    Statamic.$components.register('seo_pro_source-fieldtype', SourceFieldtype);
    Statamic.$components.register('redirect_source-fieldtype', RedirectSourceFieldtype);

    Statamic.$components.register('seo-pro-widget', SeoProWidget);
    Statamic.$components.register('seo-pro-recent-errors-widget', RecentErrorsWidget);
});
