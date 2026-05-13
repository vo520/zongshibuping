const FoxModules = (function($, Swiper) {
    const modules = {};

    modules.SwiperHelper = {
        create(options) {
            const defaultOptions = {
                loop: false,
                autoplay: false,
                slidesPerView: 1,
                spaceBetween: 0,
                pagination: null,
                navigation: null,
                breakpoints: null,
                freeMode: false,
            };

            const mergedOptions = { ...defaultOptions, ...options };
            
            if (mergedOptions.pagination && typeof mergedOptions.pagination === 'string') {
                mergedOptions.pagination = {
                    el: mergedOptions.pagination,
                    clickable: true,
                };
            }

            if (mergedOptions.navigation && typeof mergedOptions.navigation === 'object') {
                mergedOptions.navigation = {
                    nextEl: mergedOptions.navigation.nextEl,
                    prevEl: mergedOptions.navigation.prevEl,
                };
            }

            return new Swiper(mergedOptions.el, mergedOptions);
        },

        initMultiple(swiperConfigs) {
            const instances = [];
            swiperConfigs.forEach(config => {
                if (config.el && $(config.el).length) {
                    instances.push(this.create(config));
                }
            });
            return instances;
        },
    };

    modules.Validator = {
        isPhone(phone) {
            const mobileReg = /^1[3|4|5|7|8|9]\d{9}$/;
            const teleReg = /^((0\d{2,3})-)?(\d{7,8})$/;
            return mobileReg.test(phone) || teleReg.test(phone);
        },

        isEmail(email) {
            const reg = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return reg.test(email);
        },

        isEmpty(value) {
            return value === null || value === undefined || value === '' || (Array.isArray(value) && value.length === 0);
        },

        notEmpty(value) {
            return !this.isEmpty(value);
        },

        minLength(value, min) {
            return String(value).length >= min;
        },

        maxLength(value, max) {
            return String(value).length <= max;
        },

        isPasswordStrong(password) {
            const re = /^(?![a-zA-Z]+$)(?![A-Z0-9]+$)(?![A-Z\W_]+$)(?![a-z0-9]+$)(?![a-z\W_]+$)(?![0-9\W_]+$)[a-zA-Z0-9\W_]{8,}$/;
            return re.test(password);
        },
    };

    modules.Debounce = {
        create(fn, delay = 300) {
            let timer = null;
            return function(...args) {
                if (timer) clearTimeout(timer);
                timer = setTimeout(() => fn.apply(this, args), delay);
            };
        },

        throttle(fn, limit = 300) {
            let inThrottle = false;
            return function(...args) {
                if (!inThrottle) {
                    fn.apply(this, args);
                    inThrottle = true;
                    setTimeout(() => (inThrottle = false), limit);
                }
            };
        },
    };

    modules.UI = {
        scrollToTop(duration = 500) {
            $('html, body').animate({ scrollTop: 0 }, duration);
        },

        scrollToElement(selector, offset = 0, duration = 500) {
            const $element = $(selector);
            if ($element.length) {
                const offsetTop = $element.offset().top + offset;
                $('html, body').animate({ scrollTop: offsetTop }, duration);
            }
        },

        showToast(message, type = 'info', duration = 3000) {
            if (typeof foxui !== 'undefined') {
                foxui.message({
                    type,
                    text: message,
                });
            } else {
                alert(message);
            }
        },

        showLoading() {
            if (typeof foxui !== 'undefined') {
                foxui.loading();
            }
        },

        hideLoading() {
            if (typeof foxui !== 'undefined') {
                foxui.closeLoading();
            }
        },
    };

    modules.Share = {
        qq(url, title, options = {}) {
            const defaults = {
                showcount: '1',
                desc: '',
                summary: '',
                pic: '',
                flash: '',
                site: '',
            };
            const params = { ...defaults, ...options, url: url || window.location.href, title };
            const queryString = Object.keys(params)
                .map(key => `${key}=${encodeURIComponent(params[key] || '')}`)
                .join('&');
            window.open(`http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?${queryString}`, 'qq', 'height=430, width=400');
        },

        weixin(url) {
            const encodePath = encodeURIComponent(url || window.location.href);
            const targetUrl = `//api.qianfox.com/api/qrcode?text=${encodePath}`;
            if (typeof foxui !== 'undefined') {
                foxui.dialog({
                    title: '微信',
                    content: `<div class="foxui-display-flex foxui-justify-content-center"><img width="180" src="${targetUrl}"/></div>`,
                    width: '340px',
                });
            }
        },

        sina(url, title, options = {}) {
            const defaults = {
                type: '3',
                pic: '',
                count: '1',
                appkey: '',
                ralateUid: '',
            };
            const params = { ...defaults, ...options, url: url || window.location.href, title, rnd: new Date().valueOf() };
            const queryString = Object.keys(params)
                .map(key => `${key}=${encodeURIComponent(params[key] || '')}`)
                .join('&');
            window.open(`http://service.weibo.com/share/share.php?${queryString}`, 'sinaweibo', 'height=430, width=400');
        },
    };

    modules.LazyLoad = {
        init(options = {}) {
            const defaults = {
                selector: 'img[data-src]',
                threshold: 100,
                placeholder: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"%3E%3Crect fill="%23f0f0f0" width="200" height="200"/%3E%3Ctext fill="%23ccc" font-family="sans-serif" font-size="14" x="50%25" y="50%25" text-anchor="middle" dy=".3em"%3ELoading...%3C/text%3E%3C/svg%3E',
            };

            const settings = { ...defaults, ...options };
            const $images = $(settings.selector);

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const $img = $(entry.target);
                        const src = $img.attr('data-src');
                        if (src) {
                            $img.attr('src', src).removeAttr('data-src');
                            observer.unobserve(entry.target);
                        }
                    }
                });
            }, { rootMargin: `${settings.threshold}px` });

            $images.each((_, img) => observer.observe(img));
        },

        fallback() {
            $('img[data-src]').each((_, img) => {
                const $img = $(img);
                $img.attr('src', $img.attr('data-src')).removeAttr('data-src');
            });
        },
    };

    modules.Form = {
        validate(formSelector, rules) {
            const $form = $(formSelector);
            let isValid = true;
            const errors = [];

            Object.keys(rules).forEach(fieldName => {
                const $field = $form.find(`[name="${fieldName}"]`);
                const value = $field.val();
                const fieldRules = rules[fieldName];

                if (fieldRules.required && modules.Validator.isEmpty(value)) {
                    isValid = false;
                    errors.push(fieldRules.message || `${fieldName}不能为空`);
                    $field.addClass('err');
                    return;
                }

                if (fieldRules.phone && !modules.Validator.isPhone(value)) {
                    isValid = false;
                    errors.push(fieldRules.message || '请输入正确的手机号码');
                    $field.addClass('err');
                    return;
                }

                if (fieldRules.email && !modules.Validator.isEmail(value)) {
                    isValid = false;
                    errors.push(fieldRules.message || '请输入正确的邮箱地址');
                    $field.addClass('err');
                    return;
                }

                if (fieldRules.minLength && !modules.Validator.minLength(value, fieldRules.minLength)) {
                    isValid = false;
                    errors.push(fieldRules.message || `${fieldName}长度不足`);
                    $field.addClass('err');
                    return;
                }

                if (fieldRules.maxLength && !modules.Validator.maxLength(value, fieldRules.maxLength)) {
                    isValid = false;
                    errors.push(fieldRules.message || `${fieldName}长度超出限制`);
                    $field.addClass('err');
                    return;
                }

                if (fieldRules.password && !modules.Validator.isPasswordStrong(value)) {
                    isValid = false;
                    errors.push(fieldRules.message || '密码必须包含数字、大小写字母、特殊字符中至少3种，且不少于8位');
                    $field.addClass('err');
                    return;
                }

                $field.removeClass('err');
            });

            if (!isValid && errors.length > 0) {
                modules.UI.showToast(errors[0], 'danger');
            }

            return { isValid, errors };
        },

        serialize(formSelector) {
            const $form = $(formSelector);
            const data = {};
            
            $form.serializeArray().forEach(item => {
                if (data[item.name]) {
                    if (!Array.isArray(data[item.name])) {
                        data[item.name] = [data[item.name]];
                    }
                    data[item.name].push(item.value);
                } else {
                    data[item.name] = item.value;
                }
            });

            return data;
        },
    };

    return modules;
})(jQuery, Swiper);

if (typeof module !== 'undefined' && module.exports) {
    module.exports = FoxModules;
}