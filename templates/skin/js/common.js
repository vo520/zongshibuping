const FoxCommon = (function($) {
    function init() {
        initLazyLoad();
        initWow();
        initShareFallback();
        initImagePreview();
        initLightbox();
    }

    function initLazyLoad() {
        if (FoxModules && FoxModules.LazyLoad && !window.lazyLoadInitialized) {
            FoxModules.LazyLoad.init();
            window.lazyLoadInitialized = true;
        }
    }

    function initWow() {
        if (typeof WOW !== 'undefined') {
            new WOW().init();
        }
    }

    function initShareFallback() {
        if (!FoxModules || !FoxModules.Share) {
            window.shareQQ = shareQQ;
            window.shareWeixin = shareWeixin;
            window.shareSina = shareSina;
        }
    }

    function initImagePreview() {
        if (FoxModules && FoxModules.ImagePreview && !window.imagePreviewInitialized) {
            FoxModules.ImagePreview.init({
                selector: '.image-preview',
                triggerSelector: '.preview-trigger',
                previewClass: 'preview-content',
                position: 'top',
                offset: 10,
                delay: 200
            });
            window.imagePreviewInitialized = true;
        }
    }

    function initLightbox() {
        if (FoxModules && FoxModules.Lightbox && !window.lightboxInitialized) {
            $(document).on('click', '[data-lightbox]', function(e) {
                e.preventDefault();
                const $this = $(this);
                const src = $this.data('lightbox') || $this.attr('href');
                const caption = $this.data('caption') || $this.attr('alt') || '';
                FoxModules.Lightbox.open(src, { caption });
            });
            window.lightboxInitialized = true;
        }
    }

    function shareQQ(url, title, showcount, desc, summary, pic, flash, site) {
        const param = {
            url: url || window.location.href,
            title: title || '',
            showcount: showcount || '1',
            desc: desc || '',
            summary: summary || '',
            pics: pic || '',
            flash: flash || '',
            site: site || '',
        };
        const temp = [];
        for (const i in param) {
            temp.push(i + '=' + encodeURIComponent(param[i] || ''));
        }
        const targetUrl = 'http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?' + temp.join('&');
        window.open(targetUrl, 'qq', 'height=430, width=400');
    }

    function shareWeixin(url) {
        url = url || window.location.href;
        const encodePath = encodeURIComponent(url);
        const targetUrl = '//api.qianfox.com/api/qrcode?text=' + encodePath;
        if (typeof foxui !== 'undefined') {
            foxui.dialog({
                title: '微信',
                content: `<div class="foxui-display-flex foxui-justify-content-center"><img width="180" src="${targetUrl}" alt="微信分享二维码"/></div>`,
                width: '340px',
            });
        }
    }

    function shareSina(url, title, type, pic, count, appkey, ralateUid, rnd) {
        const param = {
            url: url || window.location.href,
            title: title || '',
            type: type || '3',
            pic: pic || '',
            count: count || '1',
            appkey: appkey || '',
            ralateUid: ralateUid || '',
            rnd: rnd || new Date().valueOf(),
        };
        const temp = [];
        for (const p in param) {
            temp.push(p + '=' + encodeURIComponent(param[p] || ''));
        }
        const targetUrl = 'http://service.weibo.com/share/share.php?' + temp.join('&');
        window.open(targetUrl, 'sinaweibo', 'height=430, width=400');
    }

    return {
        init,
        shareQQ,
        shareWeixin,
        shareSina,
    };
})(jQuery);

$(document).ready(function() {
    FoxCommon.init();
});
