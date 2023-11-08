import SeoProFieldtype from './components/fieldtypes/SeoProFieldtype.vue';
import SourceFieldtype from './components/fieldtypes/SourceFieldtype.vue';
import StatusIcon from './components/reporting/StatusIcon.vue';

Statamic.$components.register('seo_pro-fieldtype', SeoProFieldtype);
Statamic.$components.register('seo_pro_source-fieldtype', SourceFieldtype);
Statamic.$components.register('seo-status-icon', StatusIcon);
