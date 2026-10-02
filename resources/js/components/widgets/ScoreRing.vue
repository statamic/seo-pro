<script setup>
import { computed, useId } from 'vue';
import { Heading } from '@statamic/cms/ui';
import { NumberFormatter } from '@statamic/cms';
import { Link } from '@statamic/cms/inertia';

const props = defineProps({
	score: Number,
	href: String,
	// Smaller ring in the wide layout when there is no rules table next to it.
	compact: Boolean,
});

const R = 16.6;
const CIRCUMFERENCE = 2 * Math.PI * R;
// Gap between the arc and the track, in px.
const GAP = 3;

const id = useId();

const tone = computed(() => {
	if (props.score < 70) return 'red';
	if (props.score < 90) return 'amber';
	return 'green';
});

const strokeColor = computed(() => ({
	red: 'text-red-500',
	amber: 'text-amber-400 dark:text-amber-300!',
	green: 'text-green-500',
})[tone.value]);

const hoverColor = computed(() => ({
	red: 'text-red-100/60 dark:text-red-500/7!',
	amber: 'text-amber-100/60 dark:text-amber-300/7!',
	green: 'text-green-100/60 dark:text-green-500/7!',
})[tone.value]);

const formatted = computed(() => NumberFormatter.format(props.score / 100, 'percent'));

// Short piece at the end of the arc that is drawn again without the mask,
// so the mask only cuts the gap in front of the arc's start (97–99%).
const tail = computed(() => Math.min(props.score, 2));

// One SVG per layout, switched by container query. The line is 10px thick in
// every size, and the gap depends on both size and stroke width, which CSS
// alone can't switch.
const rings = computed(() => [
	{ px: 128, stroke: 2.8, class: '@2xl/widget:hidden!' },
	props.compact
		? { px: 144, stroke: 2.5, class: 'hidden @2xl/widget:block!' }
		: { px: 176, stroke: 2.045, class: 'hidden @2xl/widget:block!' },
].map((ring, i) => {
	const gap = GAP * 36 / ring.px;

	return {
		...ring,
		mask: `${id}-mask-${i}`,
		// Cuts the track at the end of the arc, 3px wider than the stroke on each side.
		cutWidth: ring.stroke + 2 * gap,
		// Drop the rest of the track when less than half a stroke of it would
		// remain visible between the cut and the arc's start.
		trackVisible: props.score <= 0
			|| (100 - props.score) / 100 * CIRCUMFERENCE - ring.stroke - gap >= ring.stroke / 2,
		innerR: R - ring.stroke / 2 - gap,
	};
}));
</script>

<template>
	<Link
		:href
		:aria-label="__('seo-pro::messages.widget.score_aria', { score: formatted })"
		class="group relative block size-32 rounded-full focus-visible:outline-offset-4"
		:class="compact ? '@2xl/widget:size-36!' : '@2xl/widget:size-44!'"
	>
		<svg
			v-for="ring in rings"
			:key="ring.px"
			class="size-full -rotate-90"
			:class="ring.class"
			viewBox="0 0 36 36"
			fill="none"
			aria-hidden="true"
		>
			<circle
				cx="18"
				cy="18"
				:r="ring.innerR"
				fill="currentColor"
				class="opacity-0 transition-opacity duration-150 motion-reduce:transition-none group-hover:opacity-100! group-focus-visible:opacity-100!"
				:class="hoverColor"
			/>
			<mask v-if="score > 0" :id="ring.mask" maskUnits="userSpaceOnUse" x="0" y="0" width="36" height="36">
				<rect width="36" height="36" fill="white" />
				<circle
					cx="18"
					cy="18"
					:r="R"
					fill="none"
					stroke="black"
					:stroke-width="ring.cutWidth"
					stroke-linecap="round"
					pathLength="100"
					stroke-dasharray="0.1 100"
					:stroke-dashoffset="0.1 - score"
				/>
			</mask>
			<circle
				v-if="ring.trackVisible"
				cx="18"
				cy="18"
				:r="R"
				stroke="currentColor"
				:stroke-width="ring.stroke"
				class="text-gray-200 dark:text-gray-700"
				:mask="score > 0 ? `url(#${ring.mask})` : null"
			/>
			<circle
				v-if="score >= 100"
				cx="18"
				cy="18"
				:r="R"
				stroke="currentColor"
				:stroke-width="ring.stroke"
				:class="strokeColor"
			/>
			<g v-else-if="score > 0" :class="strokeColor">
				<circle
					cx="18"
					cy="18"
					:r="R"
					stroke="currentColor"
					:stroke-width="ring.stroke"
					stroke-linecap="round"
					pathLength="100"
					:stroke-dasharray="`${score} 100`"
					:mask="`url(#${ring.mask})`"
				/>
				<circle
					cx="18"
					cy="18"
					:r="R"
					stroke="currentColor"
					:stroke-width="ring.stroke"
					stroke-linecap="round"
					pathLength="100"
					:stroke-dasharray="`${tail} 100`"
					:stroke-dashoffset="tail - score"
				/>
			</g>
		</svg>
		<div class="absolute inset-0 flex items-center justify-center" aria-hidden="true">
			<Heading size="2xl" class="normal-nums" :text="formatted" />
		</div>
	</Link>
</template>
