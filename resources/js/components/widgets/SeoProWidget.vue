<script setup>
import { Widget, Button, Description } from '@statamic/cms/ui';

defineProps({
	icon: String,
	reportsUrl: String,
	createReportUrl: String,
	report: Object,
});
</script>

<template>
	<Widget :title="__('SEO Pro')" :icon>
		<template #actions>
			<Button :href="reportsUrl" variant="primary" :text="__('seo-pro::messages.reports')" />
		</template>

		<div v-if="report" class="flex flex-col items-center justify-center gap-4" style="min-height: 159px">
			<h2
				class="!text-4xl leading-tight font-light"
				:class="{
					'text-red-500': report.score < 70,
					'text-yellow-500': report.score > 70 && report.score < 90,
					'text-green-500': report.score > 90,
				}"
             >
				{{ report.score }}%
			</h2>
			<Description :text="__('seo-pro::messages.latest_report_score')" />
		</div>

		<div v-else class="flex flex-col items-center justify-center gap-4" style="min-height: 159px">
			<Description :text="__('seo-pro::messages.report_no_results_text')" />
			<Button :href="reportsUrl" :text="__('seo-pro::messages.generate_your_first_report')" />
		</div>
	</Widget>
</template>