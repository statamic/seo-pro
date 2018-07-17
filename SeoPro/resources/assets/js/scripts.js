/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "/";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 4);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */,
/* 1 */,
/* 2 */
/***/ (function(module, exports, __webpack_require__) {

var __vue_script__, __vue_template__
var __vue_styles__ = {}
__vue_script__ = __webpack_require__(13)
if (Object.keys(__vue_script__).some(function (key) { return key !== "default" && key !== "__esModule" })) {
  console.warn("[vue-loader] SeoPro/resources/assets/src/js/Report/RelativeDate.vue: named exports in *.vue files are ignored.")}
__vue_template__ = __webpack_require__(14)
module.exports = __vue_script__ || {}
if (module.exports.__esModule) module.exports = module.exports.default
var __vue_options__ = typeof module.exports === "function" ? (module.exports.options || (module.exports.options = {})) : module.exports
if (__vue_template__) {
__vue_options__.template = __vue_template__
}
if (!__vue_options__.computed) __vue_options__.computed = {}
Object.keys(__vue_styles__).forEach(function (key) {
var module = __vue_styles__[key]
__vue_options__.computed[key] = function () { return module }
})
if (false) {(function () {  module.hot.accept()
  var hotAPI = require("vue-hot-reload-api")
  hotAPI.install(require("vue"), false)
  if (!hotAPI.compatible) return
  var id = "_v-72cc20fa/RelativeDate.vue"
  if (!module.hot.data) {
    hotAPI.createRecord(id, module.exports)
  } else {
    hotAPI.update(id, module.exports, __vue_template__)
  }
})()}

/***/ }),
/* 3 */
/***/ (function(module, exports, __webpack_require__) {

var __vue_script__, __vue_template__
var __vue_styles__ = {}
__vue_script__ = __webpack_require__(15)
if (Object.keys(__vue_script__).some(function (key) { return key !== "default" && key !== "__esModule" })) {
  console.warn("[vue-loader] SeoPro/resources/assets/src/js/Report/StatusIcon.vue: named exports in *.vue files are ignored.")}
__vue_template__ = __webpack_require__(16)
module.exports = __vue_script__ || {}
if (module.exports.__esModule) module.exports = module.exports.default
var __vue_options__ = typeof module.exports === "function" ? (module.exports.options || (module.exports.options = {})) : module.exports
if (__vue_template__) {
__vue_options__.template = __vue_template__
}
if (!__vue_options__.computed) __vue_options__.computed = {}
Object.keys(__vue_styles__).forEach(function (key) {
var module = __vue_styles__[key]
__vue_options__.computed[key] = function () { return module }
})
if (false) {(function () {  module.hot.accept()
  var hotAPI = require("vue-hot-reload-api")
  hotAPI.install(require("vue"), false)
  if (!hotAPI.compatible) return
  var id = "_v-0510716b/StatusIcon.vue"
  if (!module.hot.data) {
    hotAPI.createRecord(id, module.exports)
  } else {
    hotAPI.update(id, module.exports, __vue_template__)
  }
})()}

/***/ }),
/* 4 */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(5);


/***/ }),
/* 5 */
/***/ (function(module, exports, __webpack_require__) {

Vue.component('seo-reports', __webpack_require__(6));

/***/ }),
/* 6 */
/***/ (function(module, exports, __webpack_require__) {

var __vue_script__, __vue_template__
var __vue_styles__ = {}
__vue_script__ = __webpack_require__(7)
if (Object.keys(__vue_script__).some(function (key) { return key !== "default" && key !== "__esModule" })) {
  console.warn("[vue-loader] SeoPro/resources/assets/src/js/Report/Reports.vue: named exports in *.vue files are ignored.")}
__vue_template__ = __webpack_require__(21)
module.exports = __vue_script__ || {}
if (module.exports.__esModule) module.exports = module.exports.default
var __vue_options__ = typeof module.exports === "function" ? (module.exports.options || (module.exports.options = {})) : module.exports
if (__vue_template__) {
__vue_options__.template = __vue_template__
}
if (!__vue_options__.computed) __vue_options__.computed = {}
Object.keys(__vue_styles__).forEach(function (key) {
var module = __vue_styles__[key]
__vue_options__.computed[key] = function () { return module }
})
if (false) {(function () {  module.hot.accept()
  var hotAPI = require("vue-hot-reload-api")
  hotAPI.install(require("vue"), false)
  if (!hotAPI.compatible) return
  var id = "_v-c39f85e2/Reports.vue"
  if (!module.hot.data) {
    hotAPI.createRecord(id, module.exports)
  } else {
    hotAPI.update(id, module.exports, __vue_template__)
  }
})()}

/***/ }),
/* 7 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
// <template>
//
//     <div>
//
//         <div class="flex items-center mb-3">
//             <h1 class="flex-1">{{ title }}</h1>
//             <a href=""
//                 v-if="showingReport"
//                 @click.prevent="currentReportId = null"
//                 class="btn btn-default mr-2">
//                 &larr; {{ translate('addons.SeoPro::messages.back_to_reports') }}
//             </a>
//             <a href=""
//                 @click.prevent="generateReport"
//                 class="btn btn-primary"
//                 v-text="translate('addons.SeoPro::messages.generate_report')">
//             </a>
//         </div>
//
//         <seo-report-listing
//             v-if="showingListing"
//             @report-selected="selectReport"
//         ></seo-report-listing>
//
//         <seo-report
//             v-if="showingReport"
//             :id="currentReportId"
//         ></seo-report>
//
//     </div>
//
// </template>
//
//
// <script>
/* harmony default export */ __webpack_exports__["default"] = ({

    components: {
        SeoReport: __webpack_require__(8),
        SeoReportListing: __webpack_require__(18)
    },

    data: function data() {
        return {
            currentReportId: null
        };
    },


    computed: {
        showingListing: function showingListing() {
            return !this.currentReportId;
        },
        showingReport: function showingReport() {
            return !this.showingListing;
        },
        title: function title() {
            return this.showingListing ? 'SEO Reports' : 'SEO Report';
        }
    },

    methods: {
        selectReport: function selectReport(id) {
            this.currentReportId = id;
        },
        generateReport: function generateReport() {
            var _this = this;

            this.loading = true;
            this.currentReportId = null;

            // this.$nextTick(() => {
            this.$http.post(cp_url('addons/seo-pro/reports')).then(function (response) {
                _this.currentReportId = response.data;
                _this.loading = false;
            });
            // });
        }
    }
    // </script>

});

/***/ }),
/* 8 */
/***/ (function(module, exports, __webpack_require__) {

var __vue_script__, __vue_template__
var __vue_styles__ = {}
__vue_script__ = __webpack_require__(9)
if (Object.keys(__vue_script__).some(function (key) { return key !== "default" && key !== "__esModule" })) {
  console.warn("[vue-loader] SeoPro/resources/assets/src/js/Report/Report.vue: named exports in *.vue files are ignored.")}
__vue_template__ = __webpack_require__(17)
module.exports = __vue_script__ || {}
if (module.exports.__esModule) module.exports = module.exports.default
var __vue_options__ = typeof module.exports === "function" ? (module.exports.options || (module.exports.options = {})) : module.exports
if (__vue_template__) {
__vue_options__.template = __vue_template__
}
if (!__vue_options__.computed) __vue_options__.computed = {}
Object.keys(__vue_styles__).forEach(function (key) {
var module = __vue_styles__[key]
__vue_options__.computed[key] = function () { return module }
})
if (false) {(function () {  module.hot.accept()
  var hotAPI = require("vue-hot-reload-api")
  hotAPI.install(require("vue"), false)
  if (!hotAPI.compatible) return
  var id = "_v-1dc23694/Report.vue"
  if (!module.hot.data) {
    hotAPI.createRecord(id, module.exports)
  } else {
    hotAPI.update(id, module.exports, __vue_template__)
  }
})()}

/***/ }),
/* 9 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
// <template>
//
//     <div>
//
//         <div v-if="loading" class="card loading">
//             <span class="icon icon-circular-graph animation-spin"></span>
//             {{ translate('addons.SeoPro::messages.report_is_being_generated')}}
//         </div>
//
//         <div v-if="!loading">
//
//             <div class="card text-sm text-grey flex items-center justify-between">
//                 <div>
//                     {{ translate('addons.SeoPro::messages.generated') }}:
//                     <relative-date :date="report.date"></relative-date>
//                     <span class="mx-1">&bull;</span>
//                     {{ translate_choice('cp.pages', 2) }}:
//                     {{ report.pages.length }}
//                 </div>
//                 <div class="text-xl leading-none"
//                     :class="{
//                         'text-red': report.score < 70,
//                         'text-yellow-dark': report.score < 90,
//                         'text-green': report.score >= 90 }">
//                     {{ report.score }}%
//                 </div>
//             </div>
//
//             <div class="card flush dossier">
//                 <div class="dossier-table-wrapper">
//                     <table class="dossier">
//                         <tbody>
//                             <tr v-for="item in report.results">
//                                 <td class="w-8 text-center">
//                                     <status-icon :status="item.status"></status-icon>
//                                 </div>
//                                 <td>{{{ item.description }}}</td>
//                                 <td class="text-grey text-right">{{{ item.comment }}}</td>
//                             </tr>
//                         </tbody>
//                     </table>
//                 </div>
//             </div>
//
//             <div class="card flush dossier">
//                 <div class="dossier-table-wrapper">
//                     <table class="dossier">
//                         <tbody>
//                             <tr v-for="item in report.pages">
//                                 <td class="w-8 text-center">
//                                     <status-icon :status="item.status"></status-icon>
//                                 </div>
//                                 <td>
//                                     <a href="" @click.prevent="selected = item.id">{{{ item.url }}}</a>
//                                     <report-details
//                                         v-if="selected === item.id"
//                                         :item="item"
//                                         @closed="selected = null"
//                                     ></report-details>
//                                 </td>
//                                 <td class="text-right text-xs">
//                                     <a @click.prevent="selected = item.id" class="text-grey-light mr-2 hover:text-grey-dark" v-text="translate('cp.details')"></a>
//                                     <a v-if="item.edit_url" target="_blank" :href="item.edit_url" class="mr-2 text-grey-light hover:text-grey-dark" v-text="translate('cp.edit')"></a>
//                                 </td>
//                             </tr>
//                         </tbody>
//                     </table>
//                 </div>
//             </div>
//
//         </div>
//
//     </div>
//
// </template>
//
//
// <script>
/* harmony default export */ __webpack_exports__["default"] = ({

    components: {
        ReportDetails: __webpack_require__(10),
        RelativeDate: __webpack_require__(2),
        StatusIcon: __webpack_require__(3)
    },

    props: ['id'],

    data: function data() {
        return {
            loading: false,
            report: null,
            selected: null
        };
    },
    ready: function ready() {
        this.load();
    },


    methods: {
        load: function load() {
            var _this = this;

            this.loading = true;
            this.report = null;

            this.$http.get(cp_url('addons/seo-pro/reports/' + this.id)).then(function (response) {
                if (response.data.status === 'pending') {
                    setTimeout(function () {
                        return _this.load();
                    }, 1000);
                    return;
                }

                _this.report = response.data;
                _this.loading = false;
            });
        }
    }
    // </script>

});

/***/ }),
/* 10 */
/***/ (function(module, exports, __webpack_require__) {

var __vue_script__, __vue_template__
var __vue_styles__ = {}
__vue_script__ = __webpack_require__(11)
if (Object.keys(__vue_script__).some(function (key) { return key !== "default" && key !== "__esModule" })) {
  console.warn("[vue-loader] SeoPro/resources/assets/src/js/Report/Details.vue: named exports in *.vue files are ignored.")}
__vue_template__ = __webpack_require__(12)
module.exports = __vue_script__ || {}
if (module.exports.__esModule) module.exports = module.exports.default
var __vue_options__ = typeof module.exports === "function" ? (module.exports.options || (module.exports.options = {})) : module.exports
if (__vue_template__) {
__vue_options__.template = __vue_template__
}
if (!__vue_options__.computed) __vue_options__.computed = {}
Object.keys(__vue_styles__).forEach(function (key) {
var module = __vue_styles__[key]
__vue_options__.computed[key] = function () { return module }
})
if (false) {(function () {  module.hot.accept()
  var hotAPI = require("vue-hot-reload-api")
  hotAPI.install(require("vue"), false)
  if (!hotAPI.compatible) return
  var id = "_v-32b0e412/Details.vue"
  if (!module.hot.data) {
    hotAPI.createRecord(id, module.exports)
  } else {
    hotAPI.update(id, module.exports, __vue_template__)
  }
})()}

/***/ }),
/* 11 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
//
// <template>
//
//     <modal :show="true" @closed="$emit('closed')" :dismissible="true">
//         <template slot="header">
//             <h1>{{ translate('addons.SeoPro::messages.page_details') }}</h1>
//         </template>
//
//         <template slot="body">
//
//             <div class="">
//                 <div v-for="item in item.results" class="flex mb-2 leading-normal">
//                     <div>
//                         <span class="icon-status" :class="{'icon-status-live': item.status === 'pass', 'icon-status-error': item.status === 'fail', 'icon-status-warning': item.status === 'warning'}"></span>
//                     </div>
//                     <div class="flex-1 pl-2">
//                         <span :class="{ 'font-bold': item.status !== 'pass' }">{{{ item.description }}}</span>
//                         <div class="text-grey text-xs" v-if="item.comment">{{{ item.comment }}}</div>
//                     </div>
//                 </div>
//             </div>
//
//         </template>
//         <template slot="footer"><div class="font-mono text-left text-grey text-xs">{{ item.url }}</div></template>
//     </modal>
//
// </template>
//
//
// <script>

/* harmony default export */ __webpack_exports__["default"] = ({

    props: ['item']
    // </script>

});

/***/ }),
/* 12 */
/***/ (function(module, exports) {

module.exports = "\n\n\n<modal :show=\"true\" @closed=\"$emit('closed')\" :dismissible=\"true\">\n    <template slot=\"header\">\n        <h1>{{ translate('addons.SeoPro::messages.page_details') }}</h1>\n    </template>\n\n    <template slot=\"body\">\n\n        <div class=\"\">\n            <div v-for=\"item in item.results\" class=\"flex mb-2 leading-normal\">\n                <div>\n                    <span class=\"icon-status\" :class=\"{'icon-status-live': item.status === 'pass', 'icon-status-error': item.status === 'fail', 'icon-status-warning': item.status === 'warning'}\"></span>\n                </div>\n                <div class=\"flex-1 pl-2\">\n                    <span :class=\"{ 'font-bold': item.status !== 'pass' }\">{{{ item.description }}}</span>\n                    <div class=\"text-grey text-xs\" v-if=\"item.comment\">{{{ item.comment }}}</div>\n                </div>\n            </div>\n        </div>\n\n    </template>\n    <template slot=\"footer\"><div class=\"font-mono text-left text-grey text-xs\">{{ item.url }}</div></template>\n</modal>\n\n";

/***/ }),
/* 13 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
// <template>
//
//     <span>{{ text }}</span>
//
// </template>
//
//
// <script>
/* harmony default export */ __webpack_exports__["default"] = ({

    props: ['date'],

    data: function data() {
        return {
            text: null
        };
    },
    ready: function ready() {
        this.update();
    },


    methods: {
        update: function update() {
            var _this = this;

            this.text = moment(this.date * 1000).fromNow();

            setTimeout(function () {
                return _this.update();
            }, 60000); // once a minute
        }
    }
    // </script>

});

/***/ }),
/* 14 */
/***/ (function(module, exports) {

module.exports = "\n\n<span>{{ text }}</span>\n\n";

/***/ }),
/* 15 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
// <template>
//
//     <span>
//         <span v-if="status === 'pending'" class="icon icon-circular-graph animation-spin"></span>
//
//         <span
//             v-else
//             class="icon-status"
//             :class="{
//                 'icon-status-live': status === 'pass',
//                 'icon-status-error': status === 'fail',
//                 'icon-status-warning': status === 'warning'
//             }"></span>
//     </span>
//
// </template>
//
//
// <script>
/* harmony default export */ __webpack_exports__["default"] = ({

    props: ['status']
    // </script>

});

/***/ }),
/* 16 */
/***/ (function(module, exports) {

module.exports = "\n\n<span>\n    <span v-if=\"status === 'pending'\" class=\"icon icon-circular-graph animation-spin\"></span>\n\n    <span\n        v-else\n        class=\"icon-status\"\n        :class=\"{\n            'icon-status-live': status === 'pass',\n            'icon-status-error': status === 'fail',\n            'icon-status-warning': status === 'warning'\n        }\"></span>\n</span>\n\n";

/***/ }),
/* 17 */
/***/ (function(module, exports) {

module.exports = "\n\n<div>\n\n    <div v-if=\"loading\" class=\"card loading\">\n        <span class=\"icon icon-circular-graph animation-spin\"></span>\n        {{ translate('addons.SeoPro::messages.report_is_being_generated')}}\n    </div>\n\n    <div v-if=\"!loading\">\n\n        <div class=\"card text-sm text-grey flex items-center justify-between\">\n            <div>\n                {{ translate('addons.SeoPro::messages.generated') }}:\n                <relative-date :date=\"report.date\"></relative-date>\n                <span class=\"mx-1\">&bull;</span>\n                {{ translate_choice('cp.pages', 2) }}:\n                {{ report.pages.length }}\n            </div>\n            <div class=\"text-xl leading-none\"\n                :class=\"{\n                    'text-red': report.score < 70,\n                    'text-yellow-dark': report.score < 90,\n                    'text-green': report.score >= 90 }\">\n                {{ report.score }}%\n            </div>\n        </div>\n\n        <div class=\"card flush dossier\">\n            <div class=\"dossier-table-wrapper\">\n                <table class=\"dossier\">\n                    <tbody>\n                        <tr v-for=\"item in report.results\">\n                            <td class=\"w-8 text-center\">\n                                <status-icon :status=\"item.status\"></status-icon>\n                            </div>\n                            <td>{{{ item.description }}}</td>\n                            <td class=\"text-grey text-right\">{{{ item.comment }}}</td>\n                        </tr>\n                    </tbody>\n                </table>\n            </div>\n        </div>\n\n        <div class=\"card flush dossier\">\n            <div class=\"dossier-table-wrapper\">\n                <table class=\"dossier\">\n                    <tbody>\n                        <tr v-for=\"item in report.pages\">\n                            <td class=\"w-8 text-center\">\n                                <status-icon :status=\"item.status\"></status-icon>\n                            </div>\n                            <td>\n                                <a href=\"\" @click.prevent=\"selected = item.id\">{{{ item.url }}}</a>\n                                <report-details\n                                    v-if=\"selected === item.id\"\n                                    :item=\"item\"\n                                    @closed=\"selected = null\"\n                                ></report-details>\n                            </td>\n                            <td class=\"text-right text-xs\">\n                                <a @click.prevent=\"selected = item.id\" class=\"text-grey-light mr-2 hover:text-grey-dark\" v-text=\"translate('cp.details')\"></a>\n                                <a v-if=\"item.edit_url\" target=\"_blank\" :href=\"item.edit_url\" class=\"mr-2 text-grey-light hover:text-grey-dark\" v-text=\"translate('cp.edit')\"></a>\n                            </td>\n                        </tr>\n                    </tbody>\n                </table>\n            </div>\n        </div>\n\n    </div>\n\n</div>\n\n";

/***/ }),
/* 18 */
/***/ (function(module, exports, __webpack_require__) {

var __vue_script__, __vue_template__
var __vue_styles__ = {}
__vue_script__ = __webpack_require__(19)
if (Object.keys(__vue_script__).some(function (key) { return key !== "default" && key !== "__esModule" })) {
  console.warn("[vue-loader] SeoPro/resources/assets/src/js/Report/Listing.vue: named exports in *.vue files are ignored.")}
__vue_template__ = __webpack_require__(20)
module.exports = __vue_script__ || {}
if (module.exports.__esModule) module.exports = module.exports.default
var __vue_options__ = typeof module.exports === "function" ? (module.exports.options || (module.exports.options = {})) : module.exports
if (__vue_template__) {
__vue_options__.template = __vue_template__
}
if (!__vue_options__.computed) __vue_options__.computed = {}
Object.keys(__vue_styles__).forEach(function (key) {
var module = __vue_styles__[key]
__vue_options__.computed[key] = function () { return module }
})
if (false) {(function () {  module.hot.accept()
  var hotAPI = require("vue-hot-reload-api")
  hotAPI.install(require("vue"), false)
  if (!hotAPI.compatible) return
  var id = "_v-61a28958/Listing.vue"
  if (!module.hot.data) {
    hotAPI.createRecord(id, module.exports)
  } else {
    hotAPI.update(id, module.exports, __vue_template__)
  }
})()}

/***/ }),
/* 19 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
// <template>
//
//     <div>
//
//         <div v-if="loading" class="card loading">
//             <span class="icon icon-circular-graph animation-spin"></span>
//             {{ translate('cp.loading') }}
//         </div>
//
//         <div class="card" v-if="reports.length == 0">
//             <div class="no-results">
//                 <span class="icon icon-documents"></span>
//                 <h2>{{ translate('addons.SeoPro::messages.seo_reports') }}</h2>
//                 <h3>{{ translate('addons.SeoPro::messages.report_no_results_text') }}</h3>
//                 <button class="btn btn-default btn-lg" @click.prevent="$parent.generateReport" v-text="translate('addons.SeoPro::messages.generate_your_first_report')"</button>
//             </div>
//         </div>
//
//         <div class="card flush dossier">
//             <div class="dossier-table-wrapper">
//                 <table class="dossier">
//                     <tbody>
//                             <tr v-for="report in reports">
//                                 <td class="w-1 text-center">
//                                     <status-icon :status="report.status"></status-icon>
//                                 </td>
//                                 <td class="w-2 text-xs"
//                                     :class="{
//                                         'text-red': report.score < 70,
//                                         'text-yellow-dark': report.score > 70 && report.score < 90,
//                                         'text-green': report.score >= 90 }">
//                                     {{ report.score }}%
//                                 </td>
//                                 <td>
//                                     <a @click.prevent="$emit('report-selected', report.id)">
//                                         <relative-date :date="report.date"></relative-date>
//                                     </a>
//                                 </td>
//                             </tr>
//
//                     </tbody>
//                 </table>
//             </div>
//         </div>
//
//     </div>
//
// </template>
//
// <script>
/* harmony default export */ __webpack_exports__["default"] = ({

    components: {
        StatusIcon: __webpack_require__(3),
        RelativeDate: __webpack_require__(2)
    },

    data: function data() {
        return {
            loading: true,
            reports: null
        };
    },
    ready: function ready() {
        var _this = this;

        this.$http.get(cp_url('addons/seo-pro/reports')).then(function (response) {
            _this.reports = response.data;
            _this.loading = false;
        });
    }
});
// </script>

/***/ }),
/* 20 */
/***/ (function(module, exports) {

module.exports = "\n\n<div>\n\n    <div v-if=\"loading\" class=\"card loading\">\n        <span class=\"icon icon-circular-graph animation-spin\"></span>\n        {{ translate('cp.loading') }}\n    </div>\n\n    <div class=\"card\" v-if=\"reports.length == 0\">\n        <div class=\"no-results\">\n            <span class=\"icon icon-documents\"></span>\n            <h2>{{ translate('addons.SeoPro::messages.seo_reports') }}</h2>\n            <h3>{{ translate('addons.SeoPro::messages.report_no_results_text') }}</h3>\n            <button class=\"btn btn-default btn-lg\" @click.prevent=\"$parent.generateReport\" v-text=\"translate('addons.SeoPro::messages.generate_your_first_report')\"</button>\n        </div>\n    </div>\n\n    <div class=\"card flush dossier\">\n        <div class=\"dossier-table-wrapper\">\n            <table class=\"dossier\">\n                <tbody>\n                        <tr v-for=\"report in reports\">\n                            <td class=\"w-1 text-center\">\n                                <status-icon :status=\"report.status\"></status-icon>\n                            </td>\n                            <td class=\"w-2 text-xs\"\n                                :class=\"{\n                                    'text-red': report.score < 70,\n                                    'text-yellow-dark': report.score > 70 && report.score < 90,\n                                    'text-green': report.score >= 90 }\">\n                                {{ report.score }}%\n                            </td>\n                            <td>\n                                <a @click.prevent=\"$emit('report-selected', report.id)\">\n                                    <relative-date :date=\"report.date\"></relative-date>\n                                </a>\n                            </td>\n                        </tr>\n\n                </tbody>\n            </table>\n        </div>\n    </div>\n\n</div>\n\n";

/***/ }),
/* 21 */
/***/ (function(module, exports) {

module.exports = "\n\n<div>\n\n    <div class=\"flex items-center mb-3\">\n        <h1 class=\"flex-1\">{{ title }}</h1>\n        <a href=\"\"\n            v-if=\"showingReport\"\n            @click.prevent=\"currentReportId = null\"\n            class=\"btn btn-default mr-2\">\n            &larr; {{ translate('addons.SeoPro::messages.back_to_reports') }}\n        </a>\n        <a href=\"\"\n            @click.prevent=\"generateReport\"\n            class=\"btn btn-primary\"\n            v-text=\"translate('addons.SeoPro::messages.generate_report')\">\n        </a>\n    </div>\n\n    <seo-report-listing\n        v-if=\"showingListing\"\n        @report-selected=\"selectReport\"\n    ></seo-report-listing>\n\n    <seo-report\n        v-if=\"showingReport\"\n        :id=\"currentReportId\"\n    ></seo-report>\n\n</div>\n\n";

/***/ })
/******/ ]);