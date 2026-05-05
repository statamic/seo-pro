<script setup>
import {DateFormatter} from '@statamic/cms';
import { computed } from 'vue';
import {
	Widget,
	Icon,
	Listing,
	ListingTableHead as TableHead,
	ListingTableBody as TableBody,
	ListingPagination as Pagination,
	Button,
	Skeleton,
	Description
} from '@statamic/cms/ui';

const props = defineProps({
	columns: { type: Array, default: () => [] },
	errorsUrl: { type: String },
	filters: { type: Array, default: () => [] },
	initialPerPage: { type: Number, default: 5 },
	showTableHeader: { type: Boolean, default: false },
});

const widgetProps = computed(() => ({
	title: __('seo-pro::messages.recent_errors'),
	icon: 'warning-diamond',
	href: props.errorsUrl,
}));

function formatDate(value) {
	return DateFormatter.format(value, { relative: 'hour' }).toString();
}
</script>

<template>
	<Listing
		:url="errorsUrl"
		:columns
		:per-page="initialPerPage"
		sort-column="last_hit_at"
		sort-direction="desc"
		:show-pagination-totals="false"
		:show-pagination-page-links="false"
		:show-pagination-per-page-selector="false"
		:filters
	>
		<template #initializing>
			<Widget v-bind="widgetProps">
				<div class="flex flex-col justify-between px-4 py-3">
					<Skeleton v-for="i in initialPerPage" class="h-[1.25rem] mb-[0.25rem] w-full" />
				</div>
			</Widget>
		</template>
		<template #default="{ items }">
			<Widget v-bind="widgetProps">
				<Description v-if="!items.length" class="flex-1 flex items-center justify-center">
					{{ __('seo-pro::messages.no_errors_reported') }}
				</Description>
				<div class="px-4 py-3">
					<table class="w-full widget-table">
						<TableHead :sr-only="!props.showTableHeader" />
						<TableBody>
							<template #cell-url="{ row: error }">
								<a class="title-index-field" :href="error.url" target="_blank" rel="noopener noreferrer" v-text="error.url" />
							</template>
							<template #cell-last_hit_at="{ row: error }">
								<div
									class="text-end font-inter tabular-nums text-xs whitespace-nowrap text-gray-600 dark:text-gray-400 antialiased"
									v-text="formatDate(error.last_hit_at.date)"
								/>
							</template>
						</TableBody>
					</table>
				</div>
				<template #actions>
					<Pagination />
					<Button :href="errorsUrl" size="sm">
						{{ __('seo-pro::messages.view_all') }}
					</Button>
				</template>
			</Widget>
		</template>
	</Listing>
</template>
