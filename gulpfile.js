// gulpfile.js

const { src, dest, series, parallel, watch } = require('gulp');

const ts = require('gulp-typescript');
const del = require('del');
const babel = require('gulp-babel');
const less = require('gulp-less');
var rename = require("gulp-rename");

const jsSourceFolder = 'assets/js/';
const jsVendorFolder = 'assets/js/vendor/';
const jsBuildFolder = 'javascript/';

const cssSourceFolder = 'assets/css/';
const cssVendorFolder = 'assets/css/vendor/';
const cssBuildFolder = 'css/';

function cleanJs() {
    return del([jsBuildFolder + '**/*']);
}

function cleanCss() {
    return del([cssBuildFolder + '**/*']);
}

function buildTypeScript() {
    return src([jsSourceFolder + '**/*.ts', jsSourceFolder + '**/*.tsx', '!' + jsVendorFolder + '**/*'])
        .pipe(ts({
            noImplicitAny: false,
            jsx: "react",
            target: "es5",
            isolatedModules: true
        }))
        .pipe(dest(jsBuildFolder));
}

function buildJavascript() {
    return src([jsSourceFolder + '/**/*.js', jsSourceFolder + '/**/*.jsx', '!' + jsVendorFolder + '**/*'])
        // .pipe(babel({
        //     presets: [
        //         '@babel/env',
        //         '@babel/preset-react'
        //     ],
        //     plugins: [
        //         '@babel/plugin-proposal-class-properties'
        //     ]
        // }))
        .pipe(dest(jsBuildFolder));
}

function buildVendorJs() {
    // Vendor files are not compiled, just moved
    return src(jsVendorFolder + '**/*.js')
        .pipe(dest(jsBuildFolder))
}

function buildLess() {
    return src(cssSourceFolder + 'default.less')
        .pipe(less())
        .pipe(rename('main.css'))
        // .pipe(cleanCSS({ level: 1 }))
        // .pipe(rename({ suffix: '.min' }))
        .pipe(dest(cssBuildFolder))
}

function buildVendorCss() {
    // Vendor files are not compiled, just moved
    return src(cssVendorFolder + '**/*.css')
        .pipe(dest(cssBuildFolder))
}

const buildAllJs = series(cleanJs, parallel(buildTypeScript, buildJavascript, buildVendorJs));
const buildAllCss = series(cleanCss, buildLess, buildVendorCss);
const buildAll = parallel(buildAllJs, buildAllCss);

function watchJs() {
    watch([jsSourceFolder], buildAllJs);
}

function watchCss() {
    watch([cssSourceFolder], buildAllCss);
}

function watchAll() {
    watch([jsSourceFolder, cssSourceFolder], buildAll);
}

exports.default = buildAll;
exports.buildJs = buildAllJs;
exports.buildCss = buildAllCss;

exports.watch = watchAll;
exports.watchJs = watchJs;
exports.watchCss = watchCss;

// END OF
