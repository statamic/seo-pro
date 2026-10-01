<script setup>
import { Widget, Button, Badge, Description, Heading, HoverCard, Icon, Table, TableRows, TableRow, TableCell } from '@statamic/cms/ui';
import { Link } from '@statamic/cms/inertia';
import ScoreRing from './ScoreRing.vue';

defineProps({
	icon: String,
	title: String,
	reportsUrl: String,
	createUrl: String,
	showRules: Boolean,
	report: Object,
});
</script>

<template>
	<Widget :title :href="reportsUrl" :icon>
		<template #actions>
			<!-- "Reports" rather than "View All" (Recent Errors widget): the title doesn't say what "all" would be. -->
			<Button :href="reportsUrl" size="sm" :text="__('seo-pro::messages.reports')" />
		</template>

		<!--
			Narrow: one column (date, ring, rules, buttons).
			Wide (@2xl/widget): ring on the left, everything else in a column on the right.
			The right column is "contents" when narrow, so its children share the outer gap.
			The date is rendered twice so the DOM order matches the visual order in both
			layouts (date before score for screen readers).
			The "!" modifiers are needed because the core's utilities layer wins over addon-utilities.
		-->
		<div
			class="px-4 pt-4 pb-3 text-sm flex flex-col gap-4"
			:class="{ '@2xl/widget:flex-row! @2xl/widget:items-center! @2xl/widget:gap-x-10!': report }"
		>
			<template v-if="report">
				<div class="@2xl/widget:hidden!">
					<Heading class="normal-nums" :text="report.date" />
					<p class="mt-0.5 text-xs text-pretty normal-nums text-gray-500 dark:text-gray-400" v-text="report.freshness.text" />
				</div>

				<div
					class="flex items-center justify-center my-2 @2xl/widget:mt-0! @2xl/widget:mb-1! @2xl/widget:w-64! @2xl/widget:shrink-0!"
					:class="{ '@2xl/widget:py-4!': !showRules }"
				>
					<ScoreRing :score="report.score" :href="report.url" :compact="!showRules" />
				</div>

				<div class="contents @2xl/widget:flex! @2xl/widget:flex-col! @2xl/widget:gap-4! @2xl/widget:flex-1! @2xl/widget:min-w-0!">
					<div class="hidden @2xl/widget:block!">
						<Heading class="normal-nums" :text="report.date" />
						<p class="mt-0.5 text-xs text-pretty normal-nums text-gray-500 dark:text-gray-400" v-text="report.freshness.text" />
					</div>

					<div v-if="showRules && (report.openRules.length || report.passed)">
						<div class="flex items-center gap-1 mb-2">
							<Heading class="normal-nums ps-px" :text="report.rulesChecked" />
							<HoverCard side="bottom" :offset="8">
								<template #trigger>
									<Button size="sm" variant="ghost" icon="info" icon-only class="-my-1" :aria-label="__('seo-pro::messages.widget.hint_aria')" />
								</template>
								<Description class="max-w-[20rem]">
									<p class="text-xs text-pretty" v-text="__('seo-pro::messages.widget.hint_body')" />
								</Description>
							</HoverCard>
						</div>

						<!-- Open rules link to the report filtered by that rule; the last row sums up the passed ones. -->
						<Table class="[&_table]:table-fixed border rounded-xl border-gray-200 dark:border-gray-700">
							<TableRows>
								<TableRow v-for="rule in report.openRules" :key="rule.handle" class="group/row">
									<TableCell
										class="py-2.5! group-first/row:rounded-t-xl group-last/row:rounded-b-xl"
										:class="{ 'group-hover/row:bg-gray-50 dark:group-hover/row:bg-gray-900!': rule.url }"
									>
										<component
											:is="rule.url ? Link : 'div'"
											:href="rule.url"
											class="flex items-center gap-3 w-full px-2.5"
											:class="{ '-my-2.5 py-2.5': rule.url }"
										>
											<span class="flex flex-1 min-w-0 items-center gap-2">
												<Icon v-if="rule.status === 'fail'" name="x" class="size-4 shrink-0 text-red-600 dark:text-red-400!" aria-hidden="true" />
												<Icon v-else name="alert-warning-exclamation-mark" class="size-4 shrink-0 text-amber-500 dark:text-amber-300!" aria-hidden="true" />
												<span class="sr-only">{{ __(`seo-pro::messages.rules.${rule.status}`) }}:</span>
												<span class="truncate antialiased text-gray-900 dark:text-gray-200!" v-text="rule.label" />
											</span>
											<Badge pill :text="rule.badge" />
										</component>
									</TableCell>
								</TableRow>

								<TableRow v-if="report.passed" class="group/row">
									<TableCell class="py-2.5! group-first/row:rounded-t-xl group-last/row:rounded-b-xl group-hover/row:bg-gray-50 dark:group-hover/row:bg-gray-900!">
										<Link
											v-if="report.openRules.length"
											:href="report.url"
											class="flex items-center gap-2 w-full px-2.5 -my-2.5 py-2.5"
										>
											<Icon name="checkmark" class="size-4 shrink-0 text-green-600 dark:text-green-400!" aria-hidden="true" />
											<span class="sr-only">{{ __('seo-pro::messages.rules.pass') }}:</span>
											<span class="truncate antialiased text-gray-900 dark:text-gray-200!" v-text="report.passed" />
										</Link>
										<Link
											v-else
											:href="report.url"
											class="flex items-center gap-2 w-full px-2.5 -my-2.5 py-2.5 antialiased font-bold text-green-600! dark:text-green-400!"
										>
											<Icon name="checkmark" class="size-4 shrink-0" aria-hidden="true" />
											<span class="truncate" v-text="__('seo-pro::messages.widget.all_passed')" />
										</Link>
									</TableCell>
								</TableRow>
							</TableRows>
						</Table>
					</div>

					<div class="flex flex-wrap items-center gap-2 mb-1">
						<Button variant="default" size="sm" :text="__('seo-pro::messages.view_report')" :href="report.url" />
						<Button v-if="report.freshness.stale" variant="ghost" size="sm" icon="plus" :text="__('seo-pro::messages.widget.new_report')" :href="createUrl" />
					</div>
				</div>
			</template>

			<div v-else class="flex flex-col items-center gap-3 px-8 py-6 text-center">
				<p class="max-w-sm text-pretty text-gray-600 dark:text-gray-300" v-text="__('seo-pro::messages.report_no_results_text')" />
				<Button variant="primary" size="sm" :href="createUrl" :text="__('seo-pro::messages.generate_your_first_report')" />
			</div>
		</div>
	</Widget>
</template>
