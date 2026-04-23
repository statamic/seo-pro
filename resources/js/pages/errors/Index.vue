<script setup>
import { Head, Link } from '@statamic/cms/inertia';
import { Header, Button, Listing, DocsCallout } from '@statamic/cms/ui';
import { ref } from 'vue';

defineProps({
	blueprint: Object,
	columns: Array,
	filters: Array,
});

const items = ref(null);
const page = ref(null);
const perPage = ref(null);

function requestComplete({ items: newItems, parameters }) {
	items.value = newItems;
	page.value = parameters.page;
	perPage.value = parameters.perPage;
}
</script>

<template>
	<Head :title="__('seo-pro::messages.errors')" />

	<Header :title="__('seo-pro::messages.errors')" icon="warning-diamond" />

	<Listing
		ref="listing"
		:url="cp_url(`seo-pro/errors`)"
		:columns
		:allow-presets="false"
		:allow-customizing-columns="false"
		:filters
		sort-column="last_hit_at"
		sort-direction="desc"
		preferences-prefix="seo-pro.errors"
		push-query
		@request-completed="requestComplete"
	>
		<template #cell-url="{ row: error }">
			<a class="title-index-field" :href="error.url" target="_blank" rel="noopener noreferrer" v-text="error.url" />
		</template>
		<template #cell-actions="{ row: error }">
			<div class="flex justify-end">
				<Button size="xs" icon="moved" :href="error.create_redirect_url" :text="__('seo-pro::messages.create_redirect')" />
			</div>
		</template>
	</Listing>

	<DocsCallout :topic="__('seo-pro::messages.errors')" url="https://statamic.com/addons/statamic/seo-pro/docs" />
</template>