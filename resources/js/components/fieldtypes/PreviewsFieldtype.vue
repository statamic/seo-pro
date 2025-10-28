<script setup>
import { Field } from '@statamic/cms/ui';
import { computed } from 'vue';

const hostname = 'seo-pro-sandbox.test';
const url = 'https://seo-pro-sandbox.test/dance';
const title = 'Compiled title goes here';
const description = 'Aute sit ut consectetur. Lorem anim voluptate nisi in excepteur amet consectetur reprehenderit exercitation velit excepteur tempor cupidatat. Incididunt et ex id aute est elit mollit eu nostrud ex aliqua do. Culpa ad magna mollit non nulla. Aute incididunt est eiusmod eiusmod sunt veniam. Do et anim et labore ut eu do minim quis aliqua laboris. Enim incididunt quis est. Magna commodo aliquip in do ullamco fugiat id aute officia sint.';
const imageUrl = 'https://ichef.bbci.co.uk/food/ic/food_16x9_160/recipes/healthier_flapjack_10498_16x9.jpg';

const googleUrlComponents = computed(() => {
	const urlObject = new URL(url);

	const origin = urlObject.origin; // "https://google.com"

	const pathSegments = urlObject.pathname
		.split('/')
		.filter(segment => segment.length > 0); // ["foo", "bar"]

	return [origin, ...pathSegments];
});
</script>

<template>
	<div class="flex flex-col gap-4">
		<Field :label="__('Google Preview')">
			<div class="bg-white dark:!bg-[#1f1f1f] max-w-[652px] border rounded-lg p-4 flex">
				<div>
					<a class="flex flex-row items-center mb-1.5" :href="url" target="_blank">
						<!-- TODO: Favicon -->
						<div class="size-[28px] bg-[#f3f5f6] !border !border-[#d2d2d2] dark:!border-[#5c5f5e] rounded-[50%] mr-3"></div>
						<div>
							<div class="text-[#202124] dark:text-[#dadce0] text-sm" v-text="hostname" />
							<div class="text-[#4d5156] dark:text-[#bdc1c6] text-xs">
								<span v-text="googleUrlComponents[0]" />
								<span v-for="(component, index) in googleUrlComponents.slice(1)" :key="index" v-text="' › ' + component" />
							</div>
						</div>
					</a>

					<a class="block !text-[#1a0dab] dark:!text-[#99c3ff] text-xl mb-1 truncate max-w-xl" :href="url" target="_blank" v-text="title" />
					<div class="text-[#1f1f1f] dark:text-[#bfbfbf] text-sm line-clamp-2" v-text="description" />
				</div>
				<a v-if="imageUrl" class="block shrink-0 !pl-[20px]" :href="url" target="_blank">
					<div class="size-[92px]">
						<img class="rounded-[8px] size-full object-cover" :src="imageUrl">
					</div>
				</a>
			</div>
		</Field>

		<Field :label="__('X (Twitter) Preview')">
			<a class="block max-w-[663px] max-h-[347px] rounded-[16px] border border-[#CFD9DE] relative overflow-hidden" :href="url" target="_blank">
				<img class="size-full object-cover" :src="imageUrl" />
				<div class="absolute bottom-[12px] left-[12px] right-[12px]">
					<div class="bg-[#000000C4] text-white text-[13px] px-2 inline-flex rounded-[4px] truncate max-w-xl" v-text="title" />
				</div>
			</a>
		</Field>

		<Field :label="__('Facebook Preview')">
			<a class="block max-w-[680px] border rounded-lg overflow-hidden" :href="url" target="_blank">
				<div class="w-full h-[354px]">
					<img class="size-full object-cover" :src="imageUrl" />
				</div>
				<div class="bg-[#F2F4F7] dark:bg-[#1C1C1D] px-4 py-3">
					<div class="uppercase text-[#65686C] dark:text-[#B0B3B8] text-[.8125rem] mb-[8px]" v-text="hostname" />
					<div class="text-[#080809] dark:text-[#E2E5E9] text-[1.0625rem] font-semibold truncate max-w-xl" v-text="title" />
				</div>
			</a>
		</Field>
	</div>
</template>