<script setup>
import { Head, usePoll } from '@statamic/cms/inertia';
import { DateFormatter } from '@statamic/cms';
import { Header, Button, DocsCallout, Icon, Panel, Card, Description, Listing, Badge, DropdownItem, Heading } from '@statamic/cms/ui';
import StatusIcon from "../../components/reporting/StatusIcon.vue";
import { computed, ref, watch } from 'vue';
import PageDetailsModal from "../../components/reporting/PageDetailsModal.vue";

const props = defineProps({
	report: Object,
	createReportUrl: String,
	pagesUrl: String,
});

const selectedPage = ref(null);

const isGenerating = computed(() => ['pending', 'generating'].includes(props.report.status));

const isCachedHeaderReady = computed(() => {
	return props.report.date
		&& props.report.pages_crawled
		&& props.report.score;
});

const formatRelativeDate = (value) => {
	const isToday = new Date(value * 1000) < new Date().setUTCHours(0, 0, 0, 0);

	return !isToday
		? __('Today')
		: DateFormatter.format(value * 1000, {
			month: 'long',
			day: 'numeric',
			year: 'numeric',
		});
};

const actionablePageResults = (page) => {
	return page.results
		.filter(result => result.status !== 'pass')
		.map(result => result.actionable_pill)
		.filter((value, index, self) => self.indexOf(value) === index);
};

if (isGenerating.value) {
	const { start, stop } = usePoll(2000);

	watch(
		() => isGenerating.value,
		() => (isGenerating.value ? start() : stop())
	);
}
</script>

<template>
	<Head :title="__('seo-pro::messages.reports')" />

	<div class="max-w-5xl mx-auto">
		<Header :title="__('seo-pro::messages.reports')" icon="chart-monitoring-indicator">
			<Button variant="primary" :href="createReportUrl" :text="__('seo-pro::messages.generate_report')" />
		</Header>

		<Panel v-if="isCachedHeaderReady" :heading="__('seo-pro::messages.summary')">
			<Card class="flex flex-col gap-y-4">
				<div class="flex items-center justify-between">
					<div>
						<Description class="mb-1" :text="__('seo-pro::messages.generated')" />
						<div class="text-lg">{{ formatRelativeDate(report.date) }}</div>
					</div>
					<div>
						<Description class="mb-1" :text="__('seo-pro::messages.actionable_pages')" />
						<div class="text-lg">{{ report.pages_actionable || 'N/A' }}</div>
					</div>
					<div>
						<Description class="mb-1" :text="__('seo-pro::messages.total_pages_crawled')" />
						<div class="text-lg">{{ report.pages_crawled }}</div>
					</div>
					<div>
						<Description class="mb-1" :text="__('seo-pro::messages.site_score')" />
						<div class="text-lg" :class="{ 'text-red-500': report.score < 70, 'text-orange': report.score < 90, 'text-green-600': report.score >= 90 }">{{ report.score }}%</div>
					</div>
				</div>

				<div class="bg-gray-300 dark:bg-dark-650 h-4 w-full rounded-2xl mr-2">
					<div class="h-4 rounded-2xl" :style="`width: ${report.score}%`" :class="{ 'bg-red-500': report.score < 70, 'bg-orange': report.score < 90, 'bg-green-600': report.score >= 90 }" />
				</div>

				<table class="data-table">
					<tbody>
						<tr v-for="item in report.results">
							<td class="w-8 text-center text-pretty">
								<StatusIcon :status="item.status" />
							</td>
							<td class="!pl-0">{{ item.description }}</td>
							<td class="text-right text-pretty">
								<Description :text="item.comment" />
							</td>
						</tr>
					</tbody>
				</table>
			</Card>
		</Panel>

		<Panel v-if="isGenerating">
			<Card class="flex flex-col items-center justify-center py-12">
				<Icon class="mb-4 size-5" name="loading" />
				<Description :text="__('seo-pro::messages.report_is_being_generated')" />
			</Card>
		</Panel>

		<template v-else>
			<Heading class="mb-4" :text="__('seo-pro::messages.page_details')" />

			<Listing
				:url="pagesUrl"
				:allow-search="false"
				:allow-presets="false"
				:allow-customizing-columns="false"
			>
				<template #cell-status="{ row: page }">
					<div class="flex items-center">
						<StatusIcon :status="page.status" class="inline-block w-5" />
						{{ __('seo-pro::messages.rules.'+page.status) }}
					</div>
				</template>
				<template #cell-url="{ row: page }">
					<a @click.prevent="selectedPage = page.id" v-text="page.url" />
				</template>
				<template #cell-actionable="{ row: page }">
					<a @click.prevent="selectedPage = page.id" class="flex gap-x-2">
						<Badge
							v-for="badge in actionablePageResults(page)"
							:key="`${page.id}_actionable_pill_${badge}`"
							:text="badge"
							pill
						/>
					</a>

					<PageDetailsModal
						v-if="selectedPage === page.id"
						:page
						@closed="selectedPage = null"
					/>
				</template>
				<template #prepended-row-actions="{ row: page }">
					<DropdownItem v-if="page.url" icon="eye" target="_blank" :href="page.url" :text="__('Visit URL')" />
					<DropdownItem v-if="page.edit_url" icon="edit" target="_blank" :href="page.edit_url" :text="__('Edit')" />
				</template>
			</Listing>
		</template>

		<DocsCallout :topic="__('SEO Pro')" url="https://statamic.com/addons/statamic/seo-pro/docs" />
	</div>
</template>
