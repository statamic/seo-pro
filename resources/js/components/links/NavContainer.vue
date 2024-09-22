<template>
    <div>
        <div class="tabs-container">
            <div class="publish-tabs tabs" v-if="entry">
                <a v-for="item in navItems" :href="getUrl(item.url)" class="tab-button" :class="{'active': activeTab === item.url}">{{ item.text }}</a>
            </div>
        </div>
        <div class="card p-0 mt-6">
            <slot></slot>
        </div>
    </div>
</template>

<script>
export default {
    props: [
        'activeTab',
        'entry'
    ],
    data() {
        return {
            navItems: [
                { url: 'suggestions', text: 'Suggestions' },
                { url: 'related', text: 'Related Content' },
                { url: 'internal', text: 'Internal Links' },
                { url: 'external', text: 'External Links' },
                { url: 'inbound', text: 'Inbound Internal Links' },
            ]
        }
    },
    methods: {
        getUrl(suffix) {
            return cp_url('seo-pro/links/' + this.entry.id + '/' + suffix)
        }
    }
}
</script>