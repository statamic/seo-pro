<script setup>
import { Icon, EmptyStateMenu, EmptyStateItem, DocsCallout } from '@statamic/cms/ui';
import { useArchitecturalBackground } from '@statamic/cms/inertia';

useArchitecturalBackground();

defineProps({
	createUrl: String,
});
</script>

<template>
	<header class="py-8 mt-8 text-center">
		<h1 class="text-[25px] font-medium antialiased flex justify-center items-center gap-2 sm:gap-3">
			<Icon name="moved" class="size-5 text-gray-500" />
			{{ __('seo-pro::messages.redirects') }}
		</h1>
	</header>

	<EmptyStateMenu :heading="__('seo-pro::messages.redirects_description')">
		<EmptyStateItem
			:href="createUrl"
			icon="moved"
			:heading="__('seo-pro::messages.create_a_redirect')"
			:description="__('seo-pro::messages.create_a_redirect_description')"
		/>
	</EmptyStateMenu>

	<DocsCallout :topic="__('seo-pro::messages.redirects')" url="https://statamic.com/addons/statamic/seo-pro/docs" />
</template>
