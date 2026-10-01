<script setup>
import { Head } from '@statamic/cms/inertia';
import { Header, Button, Listing, DropdownItem, DocsCallout } from '@statamic/cms/ui';
import StatusIndicator from '../../components/dead-links/StatusIndicator.vue';
import { ref, useTemplateRef, getCurrentInstance } from 'vue';

const props = defineProps({
	blueprint: Object,
	columns: Array,
	filters: Array,
	canManage: Boolean,
	recheckAllUrl: String,
});

const instance = getCurrentInstance();
const { $axios } = instance.appContext.config.globalProperties;

const rechecking = ref(false);
const listing = useTemplateRef('listing');

function recheckAll() {
	rechecking.value = true;

	$axios.post(props.recheckAllUrl)
		.finally(() => {
			rechecking.value = false;
			listing.refresh();
		});
}
</script>

<template>
	<Head :title="__('seo-pro::messages.dead_links')" />

	<Header :title="__('seo-pro::messages.dead_links')" icon="external-link">
		<Button
			v-if="canManage"
			:text="__('seo-pro::messages.recheck_all')"
			:loading="rechecking"
			@click="recheckAll"
		/>
	</Header>

	<Listing
		ref="listing"
		:url="cp_url(`seo-pro/dead-links`)"
		:action-url="cp_url(`seo-pro/dead-links/actions`)"
		:columns
		:allow-presets="false"
		:filters
		sort-column="consecutive_failures"
		sort-direction="desc"
		preferences-prefix="seo-pro.dead-links"
		push-query
	>
		<template #cell-url="{ row: link }">
			<a class="title-index-field" :href="link.url" target="_blank" rel="noopener noreferrer">
				<StatusIndicator :status="link.status" />
				<span v-text="link.url" />
			</a>
		</template>
		<template #cell-status="{ row: link }">
			<StatusIndicator :status="link.status" show-label :show-dot="false" />
		</template>
		<template #prepended-row-actions="{ row: link }">
			<DropdownItem
				v-for="reference in link.references"
				:key="reference.edit_url ?? reference.title"
				:text="__('Edit :title', { title: reference.title })"
				:href="reference.edit_url"
				icon="edit"
			/>
		</template>
	</Listing>

	<DocsCallout :topic="__('seo-pro::messages.dead_links')" url="https://statamic.com/addons/statamic/seo-pro/docs" />
</template>
