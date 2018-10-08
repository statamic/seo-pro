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
/******/ 	return __webpack_require__(__webpack_require__.s = 22);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports) {

/*
	MIT License http://www.opensource.org/licenses/mit-license.php
	Author Tobias Koppers @sokra
*/
// css base code, injected by the css-loader
module.exports = function(useSourceMap) {
	var list = [];

	// return the list of modules as css string
	list.toString = function toString() {
		return this.map(function (item) {
			var content = cssWithMappingToString(item, useSourceMap);
			if(item[2]) {
				return "@media " + item[2] + "{" + content + "}";
			} else {
				return content;
			}
		}).join("");
	};

	// import a list of modules into the list
	list.i = function(modules, mediaQuery) {
		if(typeof modules === "string")
			modules = [[null, modules, ""]];
		var alreadyImportedModules = {};
		for(var i = 0; i < this.length; i++) {
			var id = this[i][0];
			if(typeof id === "number")
				alreadyImportedModules[id] = true;
		}
		for(i = 0; i < modules.length; i++) {
			var item = modules[i];
			// skip already imported module
			// this implementation is not 100% perfect for weird media query combinations
			//  when a module is imported multiple times with different media queries.
			//  I hope this will never occur (Hey this way we have smaller bundles)
			if(typeof item[0] !== "number" || !alreadyImportedModules[item[0]]) {
				if(mediaQuery && !item[2]) {
					item[2] = mediaQuery;
				} else if(mediaQuery) {
					item[2] = "(" + item[2] + ") and (" + mediaQuery + ")";
				}
				list.push(item);
			}
		}
	};
	return list;
};

function cssWithMappingToString(item, useSourceMap) {
	var content = item[1] || '';
	var cssMapping = item[3];
	if (!cssMapping) {
		return content;
	}

	if (useSourceMap && typeof btoa === 'function') {
		var sourceMapping = toComment(cssMapping);
		var sourceURLs = cssMapping.sources.map(function (source) {
			return '/*# sourceURL=' + cssMapping.sourceRoot + source + ' */'
		});

		return [content].concat(sourceURLs).concat([sourceMapping]).join('\n');
	}

	return [content].join('\n');
}

// Adapted from convert-source-map (MIT)
function toComment(sourceMap) {
	// eslint-disable-next-line no-undef
	var base64 = btoa(unescape(encodeURIComponent(JSON.stringify(sourceMap))));
	var data = 'sourceMappingURL=data:application/json;charset=utf-8;base64,' + base64;

	return '/*# ' + data + ' */';
}


/***/ }),
/* 1 */
/***/ (function(module, exports) {

/*
	MIT License http://www.opensource.org/licenses/mit-license.php
	Author Tobias Koppers @sokra
*/
var stylesInDom = {},
	memoize = function(fn) {
		var memo;
		return function () {
			if (typeof memo === "undefined") memo = fn.apply(this, arguments);
			return memo;
		};
	},
	isOldIE = memoize(function() {
		return /msie [6-9]\b/.test(window.navigator.userAgent.toLowerCase());
	}),
	getHeadElement = memoize(function () {
		return document.head || document.getElementsByTagName("head")[0];
	}),
	singletonElement = null,
	singletonCounter = 0,
	styleElementsInsertedAtTop = [];

module.exports = function(list, options) {
	if(typeof DEBUG !== "undefined" && DEBUG) {
		if(typeof document !== "object") throw new Error("The style-loader cannot be used in a non-browser environment");
	}

	options = options || {};
	// Force single-tag solution on IE6-9, which has a hard limit on the # of <style>
	// tags it will allow on a page
	if (typeof options.singleton === "undefined") options.singleton = isOldIE();

	// By default, add <style> tags to the bottom of <head>.
	if (typeof options.insertAt === "undefined") options.insertAt = "bottom";

	var styles = listToStyles(list);
	addStylesToDom(styles, options);

	return function update(newList) {
		var mayRemove = [];
		for(var i = 0; i < styles.length; i++) {
			var item = styles[i];
			var domStyle = stylesInDom[item.id];
			domStyle.refs--;
			mayRemove.push(domStyle);
		}
		if(newList) {
			var newStyles = listToStyles(newList);
			addStylesToDom(newStyles, options);
		}
		for(var i = 0; i < mayRemove.length; i++) {
			var domStyle = mayRemove[i];
			if(domStyle.refs === 0) {
				for(var j = 0; j < domStyle.parts.length; j++)
					domStyle.parts[j]();
				delete stylesInDom[domStyle.id];
			}
		}
	};
}

function addStylesToDom(styles, options) {
	for(var i = 0; i < styles.length; i++) {
		var item = styles[i];
		var domStyle = stylesInDom[item.id];
		if(domStyle) {
			domStyle.refs++;
			for(var j = 0; j < domStyle.parts.length; j++) {
				domStyle.parts[j](item.parts[j]);
			}
			for(; j < item.parts.length; j++) {
				domStyle.parts.push(addStyle(item.parts[j], options));
			}
		} else {
			var parts = [];
			for(var j = 0; j < item.parts.length; j++) {
				parts.push(addStyle(item.parts[j], options));
			}
			stylesInDom[item.id] = {id: item.id, refs: 1, parts: parts};
		}
	}
}

function listToStyles(list) {
	var styles = [];
	var newStyles = {};
	for(var i = 0; i < list.length; i++) {
		var item = list[i];
		var id = item[0];
		var css = item[1];
		var media = item[2];
		var sourceMap = item[3];
		var part = {css: css, media: media, sourceMap: sourceMap};
		if(!newStyles[id])
			styles.push(newStyles[id] = {id: id, parts: [part]});
		else
			newStyles[id].parts.push(part);
	}
	return styles;
}

function insertStyleElement(options, styleElement) {
	var head = getHeadElement();
	var lastStyleElementInsertedAtTop = styleElementsInsertedAtTop[styleElementsInsertedAtTop.length - 1];
	if (options.insertAt === "top") {
		if(!lastStyleElementInsertedAtTop) {
			head.insertBefore(styleElement, head.firstChild);
		} else if(lastStyleElementInsertedAtTop.nextSibling) {
			head.insertBefore(styleElement, lastStyleElementInsertedAtTop.nextSibling);
		} else {
			head.appendChild(styleElement);
		}
		styleElementsInsertedAtTop.push(styleElement);
	} else if (options.insertAt === "bottom") {
		head.appendChild(styleElement);
	} else {
		throw new Error("Invalid value for parameter 'insertAt'. Must be 'top' or 'bottom'.");
	}
}

function removeStyleElement(styleElement) {
	styleElement.parentNode.removeChild(styleElement);
	var idx = styleElementsInsertedAtTop.indexOf(styleElement);
	if(idx >= 0) {
		styleElementsInsertedAtTop.splice(idx, 1);
	}
}

function createStyleElement(options) {
	var styleElement = document.createElement("style");
	styleElement.type = "text/css";
	insertStyleElement(options, styleElement);
	return styleElement;
}

function addStyle(obj, options) {
	var styleElement, update, remove;

	if (options.singleton) {
		var styleIndex = singletonCounter++;
		styleElement = singletonElement || (singletonElement = createStyleElement(options));
		update = applyToSingletonTag.bind(null, styleElement, styleIndex, false);
		remove = applyToSingletonTag.bind(null, styleElement, styleIndex, true);
	} else {
		styleElement = createStyleElement(options);
		update = applyToTag.bind(null, styleElement);
		remove = function() {
			removeStyleElement(styleElement);
		};
	}

	update(obj);

	return function updateStyle(newObj) {
		if(newObj) {
			if(newObj.css === obj.css && newObj.media === obj.media && newObj.sourceMap === obj.sourceMap)
				return;
			update(obj = newObj);
		} else {
			remove();
		}
	};
}

var replaceText = (function () {
	var textStore = [];

	return function (index, replacement) {
		textStore[index] = replacement;
		return textStore.filter(Boolean).join('\n');
	};
})();

function applyToSingletonTag(styleElement, index, remove, obj) {
	var css = remove ? "" : obj.css;

	if (styleElement.styleSheet) {
		styleElement.styleSheet.cssText = replaceText(index, css);
	} else {
		var cssNode = document.createTextNode(css);
		var childNodes = styleElement.childNodes;
		if (childNodes[index]) styleElement.removeChild(childNodes[index]);
		if (childNodes.length) {
			styleElement.insertBefore(cssNode, childNodes[index]);
		} else {
			styleElement.appendChild(cssNode);
		}
	}
}

function applyToTag(styleElement, obj) {
	var css = obj.css;
	var media = obj.media;
	var sourceMap = obj.sourceMap;

	if (media) {
		styleElement.setAttribute("media", media);
	}

	if (sourceMap) {
		// https://developer.chrome.com/devtools/docs/javascript-debugging
		// this makes source maps inside style tags work properly in Chrome
		css += '\n/*# sourceURL=' + sourceMap.sources[0] + ' */';
		// http://stackoverflow.com/a/26603875
		css += "\n/*# sourceMappingURL=data:application/json;base64," + btoa(unescape(encodeURIComponent(JSON.stringify(sourceMap)))) + " */";
	}

	if (styleElement.styleSheet) {
		styleElement.styleSheet.cssText = css;
	} else {
		while(styleElement.firstChild) {
			styleElement.removeChild(styleElement.firstChild);
		}
		styleElement.appendChild(document.createTextNode(css));
	}
}


/***/ }),
/* 2 */,
/* 3 */,
/* 4 */,
/* 5 */,
/* 6 */,
/* 7 */,
/* 8 */,
/* 9 */,
/* 10 */,
/* 11 */,
/* 12 */,
/* 13 */,
/* 14 */,
/* 15 */,
/* 16 */,
/* 17 */,
/* 18 */,
/* 19 */,
/* 20 */,
/* 21 */,
/* 22 */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(23);


/***/ }),
/* 23 */
/***/ (function(module, exports, __webpack_require__) {

Vue.component('seo_pro-fieldtype', __webpack_require__(24));
Vue.component('seo_pro-source-fieldtype', __webpack_require__(29));
Vue.component('seo_pro-asset-fieldtype', __webpack_require__(34));

/***/ }),
/* 24 */
/***/ (function(module, exports, __webpack_require__) {

var __vue_script__, __vue_template__
var __vue_styles__ = {}
__webpack_require__(25)
__vue_script__ = __webpack_require__(27)
if (Object.keys(__vue_script__).some(function (key) { return key !== "default" && key !== "__esModule" })) {
  console.warn("[vue-loader] SeoPro/resources/assets/src/js/SeoProFieldtype.vue: named exports in *.vue files are ignored.")}
__vue_template__ = __webpack_require__(28)
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
  var id = "_v-78ad4b3d/SeoProFieldtype.vue"
  if (!module.hot.data) {
    hotAPI.createRecord(id, module.exports)
  } else {
    hotAPI.update(id, module.exports, __vue_template__)
  }
})()}

/***/ }),
/* 25 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(26);
if(typeof content === 'string') content = [[module.i, content, '']];
// add the styles to the DOM
var update = __webpack_require__(1)(content, {});
if(content.locals) module.exports = content.locals;
// Hot Module Replacement
if(false) {
	// When the styles change, update the <style> tags
	if(!content.locals) {
		module.hot.accept("!!../../../../../node_modules/css-loader/index.js!../../../../../node_modules/vue-loader/lib/style-rewriter.js!../../../../../node_modules/vue-loader/lib/selector.js?type=style&index=0!./SeoProFieldtype.vue", function() {
			var newContent = require("!!../../../../../node_modules/css-loader/index.js!../../../../../node_modules/vue-loader/lib/style-rewriter.js!../../../../../node_modules/vue-loader/lib/selector.js?type=style&index=0!./SeoProFieldtype.vue");
			if(typeof newContent === 'string') newContent = [[module.id, newContent, '']];
			update(newContent);
		});
	}
	// When the module is disposed, remove the <style> tags
	module.hot.dispose(function() { update(); });
}

/***/ }),
/* 26 */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(false);
// imports


// module
exports.push([module.i, "\n\n\n\n\n\n\n\n\n\n\n\n\n.seo_pro-fieldtype > .field-inner > label {\n    display: none !important;\n}\n\n.seo_pro-fieldtype,\n.seo_pro-fieldtype .publish-fields {\n    padding: 0 !important;\n}\n\n", ""]);

// exports


/***/ }),
/* 27 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
// <template>
//
//     <publish-fields
//         :fields="fields"
//         :data.sync="data"
//         :regular-title-field="true"
//     ></publish-fields>
//
// </template>
//
//
// <style>
//
// .seo_pro-fieldtype > .field-inner > label {
//     display: none !important;
// }
//
// .seo_pro-fieldtype,
// .seo_pro-fieldtype .publish-fields {
//     padding: 0 !important;
// }
//
// </style>
//
//
// <script>
/* harmony default export */ __webpack_exports__["default"] = ({

    mixins: [Fieldtype],

    computed: {
        fields: function fields() {
            return _.chain(this.config.fields).map(function (field, name) {
                field.name = name;
                return field;
            }).values().value();
        }
    }
    // </script>

});

/***/ }),
/* 28 */
/***/ (function(module, exports) {

module.exports = "\n\n<publish-fields\n    :fields=\"fields\"\n    :data.sync=\"data\"\n    :regular-title-field=\"true\"\n></publish-fields>\n\n";

/***/ }),
/* 29 */
/***/ (function(module, exports, __webpack_require__) {

var __vue_script__, __vue_template__
var __vue_styles__ = {}
__webpack_require__(30)
__vue_script__ = __webpack_require__(32)
if (Object.keys(__vue_script__).some(function (key) { return key !== "default" && key !== "__esModule" })) {
  console.warn("[vue-loader] SeoPro/resources/assets/src/js/SourceFieldtype.vue: named exports in *.vue files are ignored.")}
__vue_template__ = __webpack_require__(33)
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
  var id = "_v-2d949bdc/SourceFieldtype.vue"
  if (!module.hot.data) {
    hotAPI.createRecord(id, module.exports)
  } else {
    hotAPI.update(id, module.exports, __vue_template__)
  }
})()}

/***/ }),
/* 30 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(31);
if(typeof content === 'string') content = [[module.i, content, '']];
// add the styles to the DOM
var update = __webpack_require__(1)(content, {});
if(content.locals) module.exports = content.locals;
// Hot Module Replacement
if(false) {
	// When the styles change, update the <style> tags
	if(!content.locals) {
		module.hot.accept("!!../../../../../node_modules/css-loader/index.js!../../../../../node_modules/vue-loader/lib/style-rewriter.js!../../../../../node_modules/vue-loader/lib/selector.js?type=style&index=0!./SourceFieldtype.vue", function() {
			var newContent = require("!!../../../../../node_modules/css-loader/index.js!../../../../../node_modules/vue-loader/lib/style-rewriter.js!../../../../../node_modules/vue-loader/lib/selector.js?type=style&index=0!./SourceFieldtype.vue");
			if(typeof newContent === 'string') newContent = [[module.id, newContent, '']];
			update(newContent);
		});
	}
	// When the module is disposed, remove the <style> tags
	module.hot.dispose(function() { update(); });
}

/***/ }),
/* 31 */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(false);
// imports


// module
exports.push([module.i, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n.source-type-select {\n    width: 20rem;\n}\n\n.inherit-placeholder {\n    padding-top: 5px;\n}\n\n.source-field-select .selectize-dropdown,\n.source-field-select .selectize-input span {\n    font-family: 'Menlo', 'Monaco', 'Consolas', 'Liberation Mono', 'Courier New', 'monospace';\n    font-size: 12px;\n}\n\n", ""]);

// exports


/***/ }),
/* 32 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
// <template>
//
//     <div class="flex">
//
//         <div class="source-type-select pr-2">
//             <select-fieldtype :data.sync="source" :options="sourceTypeSelectOptions"></select-fieldtype>
//         </div>
//
//         <div class="flex-1">
//             <div v-if="source === 'inherit'" class="text-sm text-grey inherit-placeholder">
//                 {{ config.placeholder }}
//             </div>
//
//             <div v-if="source === 'field'" class="source-field-select">
//                 <suggest-fieldtype :data.sync="sourceField" :config="suggestConfig" :suggestions-prop="suggestSuggestions"></suggest-fieldtype>
//             </div>
//
//             <component
//                 v-if="source === 'custom'"
//                 :is="componentName"
//                 :name="name"
//                 :data.sync="customText"
//                 :config="fieldConfig"
//                 :leave-alert="true">
//             </component>
//         </div>
//     </div>
//
// </template>
//
//
// <style>
//
//     .source-type-select {
//         width: 20rem;
//     }
//
//     .inherit-placeholder {
//         padding-top: 5px;
//     }
//
//     .source-field-select .selectize-dropdown,
//     .source-field-select .selectize-input span {
//         font-family: 'Menlo', 'Monaco', 'Consolas', 'Liberation Mono', 'Courier New', 'monospace';
//         font-size: 12px;
//     }
//
// </style>
//
//
// <script>
/* harmony default export */ __webpack_exports__["default"] = ({

    mixins: [Fieldtype],

    data: function data() {
        return {
            source: null,
            customText: null,
            sourceField: null,
            autoBindChangeWatcher: false,
            changeWatcherWatchDeep: false,
            allowedFieldtypes: []
        };
    },


    computed: {
        componentName: function componentName() {
            return this.config.field.type.replace('.', '-') + '-fieldtype';
        },
        sourceTypeSelectOptions: function sourceTypeSelectOptions() {
            var options = [];

            if (this.config.field !== false) {
                options.push({ text: 'Custom', value: 'custom' });
            }

            if (this.config.from_field !== false) {
                options.unshift({ text: 'From Field', value: 'field' });
            }

            if (this.config.inherit !== false) {
                options.unshift({ text: 'Inherit', value: 'inherit' });
            }

            if (this.config.disableable) {
                options.push({ text: 'Disable', value: 'disable' });
            }

            return options;
        },
        suggestConfig: function suggestConfig() {
            return {
                type: 'suggest',
                mode: 'seo_pro',
                max_items: 1,
                create: true,
                placeholder: translate('addons.SeoPro::messages.source_suggest_placeholder')
            };
        },
        suggestSuggestions: function suggestSuggestions() {
            var _this = this;

            return SeoPro.fieldSuggestions.filter(function (item) {
                return _this.allowedFieldtypes.includes(item.type);
            });
        },
        fieldConfig: function fieldConfig() {
            return Object.assign(this.config.field, { placeholder: this.config.placeholder });
        }
    },

    watch: {
        source: function source(val) {
            this.data.source = val;

            if (val === 'field') {
                this.data.value = Array.isArray(this.sourceField) ? this.sourceField[0] : this.sourceField;
            } else {
                this.data.value = this.customText;
            }
        },
        sourceField: function sourceField(val) {
            this.data.value = Array.isArray(val) ? val[0] : val;
        },
        customText: function customText(val) {
            this.data.value = val;
        }
    },

    ready: function ready() {
        var types = this.config.allowed_fieldtypes || ['text', 'textarea', 'markdown', 'redactor'];
        this.allowedFieldtypes = types.concat(this.config.merge_allowed_fieldtypes || []);

        if (this.data.source === 'field') {
            this.sourceField = [this.data.value];
        } else {
            this.customText = this.data.value;
        }

        // Set source after so that the suggest fields don't load before they potentially have data.
        this.source = this.data.source;

        this.bindChangeWatcher();
    }
});
// </script>

/***/ }),
/* 33 */
/***/ (function(module, exports) {

module.exports = "\n\n<div class=\"flex\">\n\n    <div class=\"source-type-select pr-2\">\n        <select-fieldtype :data.sync=\"source\" :options=\"sourceTypeSelectOptions\"></select-fieldtype>\n    </div>\n\n    <div class=\"flex-1\">\n        <div v-if=\"source === 'inherit'\" class=\"text-sm text-grey inherit-placeholder\">\n            {{ config.placeholder }}\n        </div>\n\n        <div v-if=\"source === 'field'\" class=\"source-field-select\">\n            <suggest-fieldtype :data.sync=\"sourceField\" :config=\"suggestConfig\" :suggestions-prop=\"suggestSuggestions\"></suggest-fieldtype>\n        </div>\n\n        <component\n            v-if=\"source === 'custom'\"\n            :is=\"componentName\"\n            :name=\"name\"\n            :data.sync=\"customText\"\n            :config=\"fieldConfig\"\n            :leave-alert=\"true\">\n        </component>\n    </div>\n</div>\n\n";

/***/ }),
/* 34 */
/***/ (function(module, exports, __webpack_require__) {

var __vue_script__, __vue_template__
var __vue_styles__ = {}
__webpack_require__(35)
__vue_script__ = __webpack_require__(37)
if (Object.keys(__vue_script__).some(function (key) { return key !== "default" && key !== "__esModule" })) {
  console.warn("[vue-loader] SeoPro/resources/assets/src/js/AssetFieldtype.vue: named exports in *.vue files are ignored.")}
__vue_template__ = __webpack_require__(38)
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
  var id = "_v-02b9c3fb/AssetFieldtype.vue"
  if (!module.hot.data) {
    hotAPI.createRecord(id, module.exports)
  } else {
    hotAPI.update(id, module.exports, __vue_template__)
  }
})()}

/***/ }),
/* 35 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(36);
if(typeof content === 'string') content = [[module.i, content, '']];
// add the styles to the DOM
var update = __webpack_require__(1)(content, {});
if(content.locals) module.exports = content.locals;
// Hot Module Replacement
if(false) {
	// When the styles change, update the <style> tags
	if(!content.locals) {
		module.hot.accept("!!../../../../../node_modules/css-loader/index.js!../../../../../node_modules/vue-loader/lib/style-rewriter.js!../../../../../node_modules/vue-loader/lib/selector.js?type=style&index=0!./AssetFieldtype.vue", function() {
			var newContent = require("!!../../../../../node_modules/css-loader/index.js!../../../../../node_modules/vue-loader/lib/style-rewriter.js!../../../../../node_modules/vue-loader/lib/selector.js?type=style&index=0!./AssetFieldtype.vue");
			if(typeof newContent === 'string') newContent = [[module.id, newContent, '']];
			update(newContent);
		});
	}
	// When the module is disposed, remove the <style> tags
	module.hot.dispose(function() { update(); });
}

/***/ }),
/* 36 */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(false);
// imports


// module
exports.push([module.i, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n.seo-asset-fieldtype .assets-fieldtype .manage-assets {\n    border: none;\n    background: none;\n    padding: 0;\n}\n\n.seo-asset-fieldtype .assets-fieldtype .drag-notification + .manage-assets {\n    opacity: 0;\n}\n\n.seo-asset-fieldtype .no-container {\n    padding: 5px;\n}\n\n", ""]);

// exports


/***/ }),
/* 37 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
// <template>
//
//     <div class="seo-asset-fieldtype">
//         <div v-if="!container" class="no-container text-sm">
//             <i class="icon icon-warning"></i>
//             {{ translate('cp.no_asset_container_specified')}}
//             <a :href="cp_url('addons/seo-pro/settings')" class="ml-1">{{ translate('cp.edit') }}</a>
//         </div>
//
//         <assets-fieldtype v-if="container" :data.sync="data" :config="fieldConfig"></assets-fieldtype>
//     </div>
//
// </template>
//
//
// <style>
//
//     .seo-asset-fieldtype .assets-fieldtype .manage-assets {
//         border: none;
//         background: none;
//         padding: 0;
//     }
//
//     .seo-asset-fieldtype .assets-fieldtype .drag-notification + .manage-assets {
//         opacity: 0;
//     }
//
//     .seo-asset-fieldtype .no-container {
//         padding: 5px;
//     }
//
// </style>
//
//
// <script>
/* harmony default export */ __webpack_exports__["default"] = ({

    mixins: [Fieldtype],

    computed: {
        container: function container() {
            var container = SeoPro.assetContainer;
            if (container == '') return false;
            return container;
        },
        fieldConfig: function fieldConfig() {
            return Object.assign(this.config, {
                container: this.container,
                folder: '/',
                max_files: 1
            });
        }
    }
    // </script>

});

/***/ }),
/* 38 */
/***/ (function(module, exports) {

module.exports = "\n\n<div class=\"seo-asset-fieldtype\">\n    <div v-if=\"!container\" class=\"no-container text-sm\">\n        <i class=\"icon icon-warning\"></i>\n        {{ translate('cp.no_asset_container_specified')}}\n        <a :href=\"cp_url('addons/seo-pro/settings')\" class=\"ml-1\">{{ translate('cp.edit') }}</a>\n    </div>\n\n    <assets-fieldtype v-if=\"container\" :data.sync=\"data\" :config=\"fieldConfig\"></assets-fieldtype>\n</div>\n\n";

/***/ })
/******/ ]);