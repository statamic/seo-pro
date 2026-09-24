<script setup>
import { Icon, EmptyStateMenu, EmptyStateItem, DocsCallout } from '@statamic/cms/ui';
import { useArchitecturalBackground } from '@statamic/cms/inertia';

useArchitecturalBackground();
</script>

<template>
	<header class="py-8 mt-8 text-center">
		<h1 class="text-[25px] font-medium antialiased flex justify-center items-center gap-2 sm:gap-3">
			<Icon name="external-link" class="size-5 text-gray-500" />
			{{ __('seo-pro::messages.dead_links') }}
		</h1>
	</header>

	<EmptyStateMenu :heading="__('seo-pro::messages.no_dead_links_found')">
		<EmptyStateItem
			icon="sync"
			:heading="__('seo-pro::messages.no_dead_links_found')"
			:description="__('seo-pro::messages.no_dead_links_found_description')"
		/>
	</EmptyStateMenu>

	<DocsCallout :topic="__('seo-pro::messages.dead_links')" url="https://statamic.com/addons/statamic/seo-pro/docs" />
</template>
