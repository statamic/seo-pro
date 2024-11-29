<template>
<div class="flex flex-col relative bg-gray-100 dar:bg-dark-800 h-full overflow-scroll">
    <header class="flex items-center sticky top-0 inset-x-0 bg-white dark:bg-dark-550 shadow dark:shadow-dark px-8 py-2 z-1 h-13">
        <h1 class="flex-1 flex items-center text-xl">
            Link Suggestion
        </h1>

        <button
            type="button"
            class="btn-close"
            @click="cancelSuggestion"
            v-html="'&times'"
        />
    </header>

    <div class="flex-1 overflow-auto">
        <div class="px-2">
            <div class="publish-fields @container">
                <div class="form-group publish-field blueprint-section-field-w-1/2" v-if="fieldConfig">
                    <div class="field-inner">
                        <label class="publish-field-label">
                            <span class="rtl:ml-1 ltr:mr-1 v-popper--has-tooltip">Field to Update</span>
                        </label>
                    </div>

                    <pre>{{ fieldDisplayName }}</pre>
                </div>

                <div class="form-group publish-field link-fieldtype w-full">
                    <div class="field-inner">
                        <label class="publish-field-label">
                            <span class="rtl:ml-1 ltr:mr-1 v-popper--has-tooltip">Link Target</span>
                            <i class="required rtl:ml-1 ltr:mr-1">*</i>
                        </label>
                    </div>

                    <link-fieldtype
                        :meta="relationshipMeta"
                        :config="relationshipMeta"
                        :value="[]"
                        @input="updateLinkTarget"
                    ></link-fieldtype>
                </div>

                <div class="form-group publish-field" v-if="sections.length > 0">
                    <div class="field-inner">
                        <label class="publish-field-label">
                            <span class="rtl:ml-1 ltr:mr-1 v-popper--has-tooltip">Page Section</span>
                        </label>
                    </div>

                    <select-input
                        :options="this.sections"
                        :value="section"
                        @input="updateSection"
                        placeholder="Select a section"
                    ></select-input>
                </div>

                <div class="form-group publish-field w-full">
                    <div class="field-inner">
                        <label class="publish-field-label">
                            <span class="rtl:ml-1 ltr:mr-1 v-popper--has-tooltip">Link Text</span>
                            <i class="required rtl:ml-1 ltr:mr-1">*</i>
                        </label>
                    </div>

                    <p class="suggestion-phrase border rounded p-3">
                        <span
                            v-for="word in suggestionWords"
                            v-text="word.text"
                            class="cursor-pointer select-none" :class="{ 'font-bold text-blue': word.isActive, 'text-blue': word.isPreActive, 'text-red-500': word.willBeRemoved }"
                            @mouseover="previewRange(word, $event)"
                            @mouseleave="removePreviewWord"
                            @click="updateRange(word, $event)"
                            @dblclick="resetRange"
                        ></span>
                    </p>
                </div>

                <div class="form-group publish-field w-full" v-if="suggestAutoLink">
                    <div class="field-inner">
                        <label class="publish-field-label">
                            <input type="checkbox" v-model="doAutoLink" />
                            <span class="rtl:mr-1 ltr:ml-1 v-popper--has-tooltip"> Save as Automatic Link</span>
                        </label>
                    </div>
                </div>

                <div class="form-group publish-field w-full" v-if="hasLinkData && !checkingLink && canInsertLink === false">
                    <p class="p-3">SEO Pro is unable to automatically insert this link. Would you like to <a :href="editUrl" class="text-blue cursor-pointer font-bold">edit the entry instead</a>?</p>
                </div>
            </div>
        </div>

        <div class="p-6">
            <button
                class="btn-primary w-full"
                :class="{ 'opacity-50': false }"
                :disabled="!canSubmitLink"
                @click="saveLink"
                v-text="__('Save')" />
        </div>
    </div>
</div>
</template>

<style scoped>
.suggestion-phrase {
    overflow-wrap: anywhere;
}
</style>

<script>
import HandlesRequestErrors from './../HandlesRequestErrors.vue';

export default {
    mixins: [HandlesRequestErrors],

    props: [
        'suggestion',
        'entryId',
        'editUrl',
    ],

    computed: {

        fieldDisplayName() {
            if (! this.fieldConfig) {
                return '';
            }

            if (! this.fieldConfig.field_names) {
                return '';
            }

            return this.fieldConfig.field_names[this.fieldConfig.field_names.length - 1];
        },

        canSubmitLink() {
            if (this.checkingLink || this.canInsertLink === false) {
                return false;
            }

            return this.hasLinkData;
        },

        hasLinkData() {
            if (this.linkTarget.trim().length === 0) {
                return false;
            }

            return this.getRangeText().trim().length !== 0;
        },

    },

    data() {
        return {
            relationshipMeta: {
                entry: {
                    meta: {
                        itemDataUrl: cp_url('fieldtypes/relationship/data'),
                        baseSelectionsUrl: cp_url('fieldtypes/relationship'),
                        filtersUrl: cp_url('fieldtypes/relationship/filters')
                    },
                    config: {
                        type: 'entries',
                        collections: this.collections,
                        select_across_sites: true,
                        max_items: 1,
                    }
                },
                handle: 'entry',
                required: true,
                initialOption: 'url',
                initialSelectedEntries: [],
                initialUrl: '',
            },
            fieldConfig: null,
            sections: [],
            section: null,
            collections: [],
            isSaving: false,
            linkTarget: '',
            suggestionWords: [],
            previewWord: null,
            suggestAutoLink: false,
            doAutoLink: false,
            autoLinkEntry: null,
            checkingLink: false,
            canInsertLink: false,
        };
    },

    methods: {

        getFieldDetails() {
            this.$axios.get(cp_url(`seo-pro/links/field-details/${this.entryId}/${this.suggestion.context.field_handle}`)).then(response => {
                this.fieldConfig = response.data;
            }).catch(err => this.handleAxiosError(err));
        },

        cancelSuggestion() {
            this.$emit('closed');
        },

        splitWords(suggestion) {
            const words = suggestion.context.context.split(' ');

            return words.map((word, index) => ({
                text: word + (index < words.length - 1 ? ' ' : ''),
                index: index,
                isActive: false,
                isPreActive: false,
                willBeRemoved: false,
            }));
        },

        getStartOfSelection() {
            let startOfSelection = -1;

            for (let i = 0; i < this.suggestionWords.length; i++) {
                if (this.suggestionWords[i].isActive === false) {
                    continue;
                }

                startOfSelection = this.suggestionWords[i].index;
                break;
            }

            return startOfSelection;
        },

        getEndOfSelection() {
            let endOfSelection = -1;

            for (let i = 0; i < this.suggestionWords.length; i++) {
                if (this.suggestionWords[i].isActive) {
                    endOfSelection = i;

                    if (
                        i + 1 < this.suggestionWords.length &&
                        this.suggestionWords[i + 1].isActive === false
                    ) {
                        break;
                    }
                }
            }

            return endOfSelection;
        },

        removePreviewWord() {
            this.previewWord = null;
            this.resetPreview();
        },

        clearPreviewStateNow() {
            for (let i = 0; i < this.suggestionWords.length; i++) {
                this.suggestionWords[i].isPreActive = false;
                this.suggestionWords[i].willBeRemoved = false;
            }
        },

        resetPreview: _.debounce(function () {
            if (this.previewWord) {
                return;
            }

            this.clearPreviewStateNow();
        }, 250),

        previewRange(word, event) {
            this.previewWord = null;
            this.clearPreviewStateNow();

            this.previewWord = word;

            const startOfSelection = this.getStartOfSelection(),
                endOfSelection = this.getEndOfSelection();

            if (startOfSelection === -1) {
                return;
            }

            if (event.shiftKey) {
                for (let i = 0; i <= word.index; i++) {
                    if (this.suggestionWords[i].isActive) {
                        this.suggestionWords[i].willBeRemoved = true;
                    }
                }
                return;
            }

            if (word.index > startOfSelection) {
                for (let i = 0; i < this.suggestionWords.length; i++) {
                    if (
                        i > word.index &&
                        word.index < endOfSelection &&
                        this.suggestionWords[i].isActive
                    ) {
                        this.suggestionWords[i].willBeRemoved = true;
                        continue;
                    }

                    if (this.suggestionWords[i].isActive || i < startOfSelection) {
                        continue;
                    }

                    this.suggestionWords[i].isPreActive = (i < word.index + 1);
                }
            } else {
                for (let i = word.index; i < this.suggestionWords.length; i++) {
                    if (this.suggestionWords[i].isActive) {
                        continue;
                    }

                    if (i >= startOfSelection) {
                        break;
                    }

                    this.suggestionWords[i].isPreActive = true;
                }
            }
        },

        updateRange(word, event) {
            const startOfSelection = this.getStartOfSelection();

            if (event.shiftKey) {
                for (let i = 0; i <= word.index; i++) {
                    this.suggestionWords[i].isActive = false;
                }

                return;
            }

            if (startOfSelection === -1) {
                this.suggestionWords[word.index].isActive = true;

                return;
            }

            if (word.index > startOfSelection) {
                for (let i = 0; i < this.suggestionWords.length; i++) {
                    if (i < startOfSelection) {
                        continue;
                    }

                    this.suggestionWords[i].isActive = (i < word.index + 1);
                }
            } else {
                for (let i = word.index; i < this.suggestionWords.length; i++) {
                    if (this.suggestionWords[i].isActive) {
                        break;
                    }

                    this.suggestionWords[i].isActive = true;
                }
            }

            this.checkLink();
        },

        highlightPhrase(phrase) {
            const phraseWords = phrase.split(' '),
                phraseLength = phraseWords.length;

            for (let i = 0; i < this.suggestionWords.length - phraseLength + 1; i++) {
                let match = true;

                for (let j = 0; j < phraseLength; j++) {
                    if (this.suggestionWords[i + j].text.trim().toLowerCase() !== phraseWords[j].toLowerCase()) {
                        match = false;
                        break;
                    }
                }

                if (match) {
                    for (let j = 0; j < phraseLength; j++) {
                        this.suggestionWords[i + j].isActive = true;
                    }

                    break;
                }
            }
        },

        resetRange() {
            if (! this.suggestion) {
                return;
            }

            for (let i = 0; i < this.suggestionWords.length; i++) {
                this.suggestionWords[i].isActive = false;
            }

            this.highlightPhrase(this.suggestion.phrase);
        },

        getRangeText() {
            const activeWords = [];

            for (let i = 0; i < this.suggestionWords.length; i++) {
                if (this.suggestionWords[i].isActive === false) {
                    continue;
                }

                activeWords.push(this.suggestionWords[i].text.trim());
            }

            return activeWords.join(' ');
        },

        updateLinkTarget(val) {
            this.sections = [];

            if (val && val.startsWith('entry::')) {
                const entryId = val.substring(7);

                if (this.suggestion.entry.toLowerCase() !== entryId.toLowerCase()) {
                    this.suggestAutoLink = true;
                    this.doAutoLink = true;
                    this.autoLinkEntry = entryId;

                }

                this.$axios.get(cp_url(`seo-pro/links/${entryId}/sections`)).then(response => {
                    const newSections = [{
                        value: '--none--',
                        label: '-- None --'
                    }];

                    response.data.forEach(section => {
                        newSections.push({
                            value: section.id,
                            label: section.text
                        })
                    });

                    this.sections = newSections;
                });
            } else {
                this.suggestAutoLink = false;
                this.doAutoLink = false;
                this.autoLinkEntry = null;
            }
            this.linkTarget = val;
            this.checkLink();
        },

        updateSection(val) {
            this.section = val;

            this.checkLink();
        },

        getReplacement() {
            return {
                entry: this.entryId,
                phrase: this.getRangeText(),
                section: this.section,
                target: this.linkTarget,
                field: this.suggestion.context.field_handle,
                auto_link: this.doAutoLink,
                auto_link_entry: this.autoLinkEntry,
            };
        },

        checkLink() {
            if (this.getRangeText().trim().length === 0) {
                return;
            }

            this.checkingLink = true;

            this.$axios.post(cp_url(`seo-pro/links/check`), this.getReplacement()).then(response => {
                this.checkingLink = false;
                this.canInsertLink = response.data.can_replace;
            }).catch(err => {
                this.checkingLink = false;
                this.canInsertLink = false;
                this.handleAxiosError(err);
            });
        },

        saveLink() {
            this.isSaving = true;

            this.$axios.post(cp_url(`seo-pro/links`), this.getReplacement()).then(response => {
                this.$toast.success('Entry updated');
                this.isSaving = false;

                this.$emit('saved');
            }).catch(err => {
                this.isSaving = false;
                this.handleAxiosError(err);
            });
        },

    },

    created() {
        this.section = '--none--';
        this.relationshipMeta.initialUrl = this.suggestion.uri;
        this.linkTarget = this.suggestion.uri;
        this.suggestionWords = this.splitWords(this.suggestion);
        this.highlightPhrase(this.suggestion.phrase);

        this.getFieldDetails();

        this.checkLink();
    }
}
</script>
