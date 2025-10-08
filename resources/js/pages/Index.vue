<script setup>
// import { Link } from '@inertiajs/vue3';
// import Head from '@/pages/layout/Head.vue';
import {Header, CardPanel, Heading, Subheading, DocsCallout, Icon} from '@statamic/cms/ui';

defineProps({
	canViewReports: Boolean,
	canEditSiteDefaults: Boolean,
	canEditSectionDefaults: Boolean,
});
</script>

<template>
<!--	<Head :title="__('SEO Pro')" />-->

	<Header :title="__('SEO Pro')" /> <!-- TODO: icon -->

	<CardPanel>
		<div class="flex flex-wrap starting-style-transition-children">
			<!-- TODO: Make these Inertia Links -->
			<a
				v-if="canViewReports"
				:href="cp_url('seo-pro/reports/index')"
				class="group w-full items-start rounded-md border border-transparent p-4 hover:bg-gray-100 dark:hover:border-dark-400 dark:hover:bg-dark-575 md:flex lg:w-1/2"
			>
				<Icon name="chart-monitoring-indicator" class="size-6 text-gray-400 mt-1 me-4" />
				<div class="mb-4 flex-1 md:mb-0 md:me-6">
					<Heading size="lg" :text="__('seo-pro::messages.reports')" />
					<Subheading v-text="__('seo-pro::messages.seo_reports_description')" />
				</div>
			</a>

			<a
				v-if="canEditSiteDefaults"
				:href="cp_url('seo-pro/site-defaults/edit')"
				class="group w-full items-start rounded-md border border-transparent p-4 hover:bg-gray-100 dark:hover:border-dark-400 dark:hover:bg-dark-575 md:flex lg:w-1/2"
			>
				<Icon name="earth" class="size-6 text-gray-400 mt-1 me-4" />
				<div class="mb-4 flex-1 md:mb-0 md:me-6">
					<Heading size="lg" :text="__('seo-pro::messages.site_defaults')" />
					<Subheading v-text="__('seo-pro::messages.site_defaults_description')" />
				</div>
			</a>

			<a
				v-if="canEditSectionDefaults"
				:href="cp_url('seo-pro/section-defaults')"
				class="group w-full items-start rounded-md border border-transparent p-4 hover:bg-gray-100 dark:hover:border-dark-400 dark:hover:bg-dark-575 md:flex lg:w-1/2"
			>
				<Icon name="folder" class="size-6 text-gray-400 mt-1 me-4" />
				<div class="mb-4 flex-1 md:mb-0 md:me-6">
					<Heading size="lg" :text="__('seo-pro::messages.section_defaults')" />
					<Subheading v-text="__('seo-pro::messages.section_defaults_description')" />
				</div>
			</a>
		</div>
	</CardPanel>

	<DocsCallout :topic="__('SEO Pro')" url="https://statamic.com/addons/statamic/seo-pro/docs" />
</template>