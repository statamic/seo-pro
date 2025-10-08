<script setup>
import { ref } from 'vue';
// import { Link } from '@inertiajs/vue3';
// import Head from '@/pages/layout/Head.vue';
import { Header, Subheading, Panel, DocsCallout, Icon } from '@statamic/cms/ui';

defineProps(['collections', 'taxonomies']);
</script>

<template>
<!--	<Head :title="__('seo-pro::messages.section_defaults')" />-->

	<Header :title="__('seo-pro::messages.section_defaults')" />

	<section class="space-y-6 starting-style-transition-children">
		<template v-if="collections.length">
			<Subheading size="lg" class="mb-2" :text="__('Collections')" />
			<Panel>
				<table class="data-table">
					<thead>
						<tr>
							<th>{{ __('Collection') }}</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="collection in collections" :key="collection.handle">
							<td>
								<div class="flex items-center gap-2">
									<Icon name="collections" class="text-gray-500 me-1" />
									<a :href="cp_url(`seo-pro/section-defaults/collections/${collection.handle}/edit`)" v-text="__(collection.title)" />
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</Panel>
		</template>

		<template v-if="taxonomies.length">
			<Subheading size="lg" class="mb-2" :text="__('Taxonomies')" />
			<Panel>
				<table class="data-table">
					<thead>
						<tr>
							<th>{{ __('Taxonomy') }}</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="taxonomy in taxonomies" :key="taxonomy.handle">
							<td>
								<div class="flex items-center gap-2">
									<Icon name="taxonomies" class="text-gray-500 me-1" />
									<a :href="cp_url(`seo-pro/section-defaults/taxonomies/${taxonomy.handle}/edit`)" v-text="__(taxonomy.title)" />
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</Panel>
		</template>
	</section>

	<DocsCallout :topic="__('SEO Pro')" url="https://statamic.com/addons/statamic/seo-pro/docs" />
</template>