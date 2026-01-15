<script setup>
import { Icon, EmptyStateMenu, EmptyStateItem, DocsCallout } from '@statamic/cms/ui';
import { onMounted, onUnmounted } from 'vue';
import { useArchitecturalBackground } from "@statamic/cms/inertia";

useArchitecturalBackground();

defineProps({
	createUrl: String,
});

const className = 'bg-architectural-lines';
const id = 'content-card';

function add() {
	document.getElementById(id).classList.add(className);
}

function remove() {
	document.getElementById(id).classList.remove(className);
}

onMounted(() => add());
onUnmounted(() => remove());
</script>

<template>
	<header class="py-8 mt-8 text-center">
		<h1 class="text-[25px] font-medium antialiased flex justify-center items-center gap-2 sm:gap-3">
			<Icon name="chart-monitoring-indicator" class="size-5 text-gray-500" />
			{{ __('seo-pro::messages.seo_reports') }}
		</h1>
	</header>

	<EmptyStateMenu :heading="__('seo-pro::messages.first_report')">
		<EmptyStateItem
			:href="createUrl"
			icon="chart-monitoring-indicator"
			:heading="__('seo-pro::messages.generate_report')"
			:description="__('seo-pro::messages.seo_reports_description')"
		/>
	</EmptyStateMenu>

	<DocsCallout :topic="__('SEO Pro')" url="https://statamic.com/addons/statamic/seo-pro/docs" />
</template>