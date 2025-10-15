<script setup>
import { Head, Link } from '@statamic/cms/inertia';
import { DateFormatter } from '@statamic/cms';
import { Header, Button, Listing, DropdownItem, DocsCallout } from '@statamic/cms/ui';
import IndexScore from '../../components/reporting/IndexScore.vue';

defineProps({
	columns: Array,
	listingUrl: String,
	createReportUrl: String,
	canDeleteReports: Boolean,
});

const formatDate = (date) => {
	return new DateFormatter().options('datetime').date(date).toString();
};
</script>

<template>
	<Head :title="__('seo-pro::messages.reports')" />

	<div class="max-w-5xl mx-auto">
		<Header :title="__('seo-pro::messages.reports')">
			<Button variant="primary" :href="createReportUrl" :text="__('seo-pro::messages.generate_report')" />
		</Header>

		<Listing
			:columns
			:url="listingUrl"
			:allow-search="false"
			:allow-presets="false"
			:allow-customizing-columns="false"
			:preferences-prefix="`seo_pro.reports`"
		>
			<template #cell-site_score="{ row: report }">
				<div class="flex items-center">
					<IndexScore
						:id="report.id"
						:initial-status="report.status"
						:initial-score="report.score"
					/>
				</div>

				<resource-deleter :ref="`deleter_${report.id}`" :route="report.delete_url" :resource="report" reload />
			</template>
			<template #cell-generated="{ row: report }">
				<Link :href="report.url">{{ formatDate(report.date) }}</Link>
			</template>
			<template #cell-actionable_pages="{ row: report }">
				<Link :href="report.url">{{ report.pages_actionable ?? 'N/A' }}</Link>
			</template>
			<template #cell-total_pages_crawled="{ row: report }">
				<Link :href="report.url">{{ report.pages_crawled }}</Link>
			</template>
			<template v-if="canDeleteReports" #prepended-row-actions="{ row: report }">
				<DropdownItem
					:text="__('seo-pro::messages.view_report')"
					:href="report.url"
					icon="eye"
				/>
				<DropdownItem
					:text="__('seo-pro::messages.delete_report')"
					icon="trash"
					variant="destructive"
					@click="$refs[`deleter_${report.id}`].confirm()"
				/>
			</template>
		</Listing>

		<DocsCallout :topic="__('SEO Pro')" url="https://statamic.com/addons/statamic/seo-pro/docs" />
	</div>
</template>