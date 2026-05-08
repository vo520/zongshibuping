<?php

use think\facade\Route;

Route::group('api', function () {

    // ==================== 栏目 API ====================
    Route::group('column', function () {
        Route::get('list', 'api.Column/list');
        Route::get('detail', 'api.Column/detail');
        Route::get('tree', 'api.Column/tree');
        Route::get('breadcrumb', 'api.Column/breadcrumb');
    });

    // ==================== 文章 API ====================
    Route::group('article', function () {
        Route::get('list', 'api.Article/list');
        Route::get('detail', 'api.Article/detail');
        Route::get('recommend', 'api.Article/recommend');
        Route::get('hot', 'api.Article/hot');
    });

    // ==================== 产品 API ====================
    Route::group('product', function () {
        Route::get('list', 'api.Product/list');
        Route::get('detail', 'api.Product/detail');
        Route::get('recommend', 'api.Product/recommend');
    });

    // ==================== 图片 API ====================
    Route::group('images', function () {
        Route::get('list', 'api.Images/list');
        Route::get('detail', 'api.Images/detail');
    });

    // ==================== 下载 API ====================
    Route::group('download', function () {
        Route::get('list', 'api.Download/list');
        Route::get('detail', 'api.Download/detail');
    });

    // ==================== 视频 API ====================
    Route::group('video', function () {
        Route::get('list', 'api.Video/list');
        Route::get('detail', 'api.Video/detail');
    });

    // ==================== 其他内容 API ====================
    Route::group('other', function () {
        Route::get('single', 'api.Other/single');
        Route::get('links', 'api.Other/linkList');
        Route::get('slides', 'api.Other/slideList');
        Route::get('tags', 'api.Other/tagList');
        Route::get('nav', 'api.Other/navList');
        Route::get('config', 'api.Other/config');
    });

    // ==================== 标签 API ====================
    Route::group('tag', function () {
        Route::get('article', 'api.Tag/article');
        Route::get('product', 'api.Tag/product');
        Route::get('hot', 'api.Tag/hot');
    });

    // ==================== 统计 API ====================
    Route::group('stats', function () {
        Route::get('overview', 'api.Stats/overview');
        Route::get('popular', 'api.Stats/popular');
        Route::get('visits', 'api.Stats/visits');
        Route::get('columns', 'api.Stats/columns');
    });

    // ==================== 反馈 API ====================
    Route::group('feedback', function () {
        Route::get('list', 'api.Feedback/list');
        Route::get('detail', 'api.Feedback/detail');
        Route::get('statistics', 'api.Feedback/statistics');
    });

    // ==================== 搜索 API ====================
    Route::get('search', 'api.Search/index');

    // ==================== 模板 API ====================
    Route::group('template', function () {
        Route::get('slides', 'api.Template/slides');
        Route::get('slideSpaces', 'api.Template/slideSpaces');
        Route::get('links', 'api.Template/links');
        Route::get('nav', 'api.Template/nav');
        Route::get('tagGroups', 'api.Template/tagGroups');
        Route::get('tags', 'api.Template/tags');
        Route::get('singles', 'api.Template/singles');
        Route::get('single', 'api.Template/single');
        Route::get('contact', 'api.Template/contact');
        Route::get('advertising', 'api.Template/advertising');
        Route::get('forms', 'api.Template/forms');
    });

})->middleware(\app\api\middleware\RateLimiter::class)->allowCrossDomain();