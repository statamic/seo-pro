<script setup>
import { Head, Link, router } from '@statamic/cms/inertia';
import { DateFormatter } from '@statamic/cms';
import { Header, Button, Listing, DropdownItem, DocsCallout } from '@statamic/cms/ui';
import IndexScore from '../../components/reporting/IndexScore.vue';

defineProps({
	columns: Array,
	listingUrl: String,
	createUrl: String,
	canDelete: Boolean,
});

const requestCompleted = ({ items }) => {
	if (items.length === 0) router.reload();
};

const formatDate = (date) => {
	return new DateFormatter().options('datetime').date(date).toString();
};
</script>

<template>
	<Head :title="__('seo-pro::messages.reports')" />

	<div class="max-w-5xl mx-auto">
		<Header :title="__('seo-pro::messages.reports')" icon="chart-monitoring-indicator">
			<Button variant="primary" :href="createUrl" :text="__('seo-pro::messages.generate_report')" />
		</Header>

		<Listing
			ref="listing"
			:columns
			:url="listingUrl"
			:allow-search="false"
			:allow-presets="false"
			:allow-customizing-columns="false"
			:preferences-prefix="`seo_pro.reports`"
			@request-completed="requestCompleted"
		>
			<template #cell-site_score="{ row: report }">
				<Link :href="report.url">
					<IndexScore
						:id="report.id"
						:initial-status="report.status"
						:initial-score="report.score"
					/>
				</Link>

				<resource-deleter
					:ref="`deleter_${report.id}`"
					:route="report.delete_url"
					:resource="report"
					:resource-title="__('seo-pro::messages.report')"
					@deleted="$refs.listing.refresh()"
				/>
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
			<template #prepended-row-actions="{ row: report }">
				<DropdownItem
					:text="__('seo-pro::messages.view_report')"
					:href="report.url"
					icon="eye"
				/>
				<DropdownItem
					v-if="canDelete"
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