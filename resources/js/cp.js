import SeoProFieldtype from './components/fieldtypes/SeoProFieldtype.vue';
import SourceFieldtype from './components/fieldtypes/SourceFieldtype.vue';
import StatusIcon from './components/reporting/StatusIcon.vue';
import IndexScore from './components/reporting/IndexScore.vue';
import Report from './components/reporting/Report.vue';
import Suggestions from './components/links/SuggestionsListing.vue';
import Listing from './components/links/Listing.vue';
import RelatedContent from './components/links/RelatedContent.vue';
import InternalLinkListing from './components/links/InternalLinkListing.vue';
import ExternalLinkListing from './components/links/ExternalLinkListing.vue';
import CollectionBehaviorListing from './components/links/config/CollectionBehaviorListing.vue';
import SiteConfigListing from './components/links/config/SiteConfigListing.vue';
import AutomaticLinksListing from './components/links/AutomaticLinksListing.vue';
import LinkDashboard from './components/links/LinkDashboard.vue';

Statamic.$components.register('seo_pro-fieldtype', SeoProFieldtype);
Statamic.$components.register('seo_pro_source-fieldtype', SourceFieldtype);

Statamic.$components.register('seo-pro-status-icon', StatusIcon);
Statamic.$components.register('seo-pro-report', Report);
Statamic.$components.register('seo-pro-index-score', IndexScore);
Statamic.$components.register('seo-pro-link-dashboard', LinkDashboard);

Statamic.$components.register('seo-pro-link-listing', Listing);
Statamic.$components.register('seo-pro-collection-behavior-listing', CollectionBehaviorListing);
Statamic.$components.register('seo-pro-site-config-listing', SiteConfigListing);
Statamic.$components.register('seo-pro-automatic-links-listing', AutomaticLinksListing);
