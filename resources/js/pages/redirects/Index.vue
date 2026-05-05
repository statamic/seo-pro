<script setup>
import { Head, Link } from '@statamic/cms/inertia';
import { Header, Button, Listing, DropdownItem, DocsCallout, Dropdown, DropdownMenu } from '@statamic/cms/ui';
import StatusIndicator from "../../components/redirects/StatusIndicator.vue";
import ImportRedirectsModal from "../../components/redirects/ImportRedirectsModal.vue";
import { ref, useTemplateRef } from 'vue';

defineProps({
	blueprint: Object,
	columns: Array,
	filters: Array,
	canCreate: Boolean,
	createUrl: String,
});

const items = ref(null);
const page = ref(null);
const perPage = ref(null);
const showImportModal = ref(false);
const listing = useTemplateRef('listing');

function requestComplete({ items: newItems, parameters }) {
	items.value = newItems;
	page.value = parameters.page;
	perPage.value = parameters.perPage;
}
</script>

<template>
	<Head :title="__('seo-pro::messages.redirects')" />

	<Header :title="__('seo-pro::messages.redirects')" icon="moved">
		<Dropdown>
			<template #trigger>
				<Button :text="__('seo-pro::messages.import_export')" />
			</template>
			<DropdownMenu>
				<DropdownItem :text="__('seo-pro::messages.export')" icon="download" :href="cp_url('seo-pro/redirects/export')" target="_blank" />
				<DropdownItem :text="__('seo-pro::messages.import')" icon="upload" @click="showImportModal = true" />
			</DropdownMenu>
		</Dropdown>
		<Button v-if="canCreate" :href="createUrl" :text="__('seo-pro::messages.create_redirect')" variant="primary" />
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
			<Link v-if="redirect.editable" class="title-index-field" :href="redirect.edit_url">
				<StatusIndicator v-if="!isColumnVisible('status')" :status="redirect.status" />
				<span v-text="redirect.source" />
			</Link>
			<span v-else class="title-index-field">
				<StatusIndicator v-if="!isColumnVisible('status')" :status="redirect.status" />
				<span v-text="redirect.source" />
			</span>

			<resource-deleter
				v-if="redirect.deletable"
				:ref="`deleter_${redirect.id}`"
				:resource="redirect"
				@deleted="listing.refresh()"
			/>
		</template>
		<template #cell-status="{ row: redirect }">
			<StatusIndicator :status="redirect.status" show-label :show-dot="false" />
		</template>
		<template #prepended-row-actions="{ row: redirect }">
			<DropdownItem v-if="redirect.editable" :text="__('Edit')" :href="redirect.edit_url" icon="edit" />
			<DropdownItem
				v-if="redirect.deletable"
				:text="__('Delete')"
				icon="trash"
				variant="destructive"
				@click="$refs[`deleter_${redirect.id}`].confirm()"
			/>
		</template>
	</Listing>

	<DocsCallout :topic="__('seo-pro::messages.redirects')" url="https://statamic.com/addons/statamic/seo-pro/docs" />

	<ImportRedirectsModal
		v-if="showImportModal"
		@closed="showImportModal = false"
		@imported="listing.refresh()"
	/>
</template>
