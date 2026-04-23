<script setup>
import { Head, Link } from '@statamic/cms/inertia';
import { Header, Button, Listing, DropdownItem, DocsCallout } from '@statamic/cms/ui';
import StatusIndicator from "../../components/redirects/StatusIndicator.vue";
import { ref } from 'vue';

defineProps({
	blueprint: Object,
	columns: Array,
	filters: Array,
	createUrl: String,
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
	<Head :title="__('seo-pro::messages.redirects')" />

	<Header :title="__('seo-pro::messages.redirects')" icon="moved">
		<Button :href="createUrl" :text="__('seo-pro::messages.create_redirect')" variant="primary" />
	</Header>

	<Listing
		ref="listing"
		:url="cp_url(`seo-pro/redirects`)"
		:columns
		:allow-presets="false"
		sort-column="source"
		sort-direction="asc"
		preferences-prefix="seo-pro.redirects"
		:filters
		push-query
		@request-completed="requestComplete"
	>
		<template #cell-source="{ row: redirect, isColumnVisible }">
			<Link class="title-index-field" :href="redirect.edit_url">
				<StatusIndicator v-if="!isColumnVisible('status')" :status="redirect.status" />
				<span v-text="redirect.source" />
			</Link>

			<resource-deleter
				:ref="`deleter_${redirect.id}`"
				:resource="redirect"
				@deleted="$refs.listing.refresh()"
			/>
		</template>
		<template #cell-status="{ row: redirect }">
			<StatusIndicator :status="redirect.status" show-label :show-dot="false" />
		</template>
		<template #prepended-row-actions="{ row: redirect }">
			<DropdownItem :text="__('Edit')" :href="redirect.edit_url" icon="edit" />
			<DropdownItem
				:text="__('Delete')"
				icon="trash"
				variant="destructive"
				@click="$refs[`deleter_${redirect.id}`].confirm()"
			/>
		</template>
	</Listing>

	<DocsCallout :topic="__('seo-pro::messages.redirects')" url="https://statamic.com/addons/statamic/seo-pro/docs" />
</template>