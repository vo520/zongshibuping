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

            const { el, ...configOptions } = options;
            const mergedOptions = { ...defaultOptions, ...configOptions };
            
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

            return new Swiper(el, mergedOptions);
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
                    content: `<div class="foxui-display-flex foxui-justify-content-center"><img width="180" src="${targetUrl}" alt="微信分享二维码"/></div>`,
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
            const settings = {
                selector: 'img[data-src]',
                excludeSelector: '.no-lazy, [data-lazy-ignore]',
                threshold: 200,
                placeholderColor: '#f5f7fa',
                errorPlaceholder: '',
                ...options
            };

            const $images = $(settings.selector).not(settings.excludeSelector);

            $images.each((_, img) => {
                const $img = $(img);
                const $container = $img.closest('.lazy-container');
                const aspectRatio = $container.data('aspect-ratio');

                if (aspectRatio) {
                    $container.css('--aspect-ratio', aspectRatio);
                }

                if (!$img.attr('src')) {
                    $img.attr('src', `data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3Crect fill='${encodeURIComponent(settings.placeholderColor)}' width='1' height='1'/%3E%3C/svg%3E`);
                }
            });

            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            const $img = $(img);
                            const $container = $img.closest('.lazy-container');
                            const src = $img.attr('data-src');
                            const srcset = $img.attr('data-srcset');

                            if (src) {
                                $img.addClass('lazy-loading');
                                
                                if (srcset) {
                                    $img.attr('srcset', srcset).removeAttr('data-srcset');
                                }
                                $img.attr('src', src).removeAttr('data-src');

                                img.onload = () => {
                                    $img.removeClass('lazy-loading').addClass('lazy-loaded');
                                    $container.removeClass('lazy-loading').addClass('lazy-loaded');
                                    if ($container.hasClass('wow') && !$container.hasClass('animated')) {
                                        $container.addClass('animated');
                                    }
                                    $img.trigger('lazyload:complete');
                                };
                                img.onerror = () => {
                                    $img.removeClass('lazy-loading').addClass('lazy-error');
                                    $container.removeClass('lazy-loading').addClass('lazy-loaded');
                                    if ($container.hasClass('wow') && !$container.hasClass('animated')) {
                                        $container.addClass('animated');
                                    }
                                    if (settings.errorPlaceholder) {
                                        $img.attr('src', settings.errorPlaceholder);
                                    }
                                    $img.trigger('lazyload:error');
                                };
                            }

                            observer.unobserve(img);
                        }
                    });
                }, {
                    rootMargin: `${settings.threshold}px`,
                    threshold: 0
                });

                $images.each((_, img) => {
                    if (!img.src || img.src.startsWith('data:image/svg+xml')) {
                        observer.observe(img);
                    }
                });
            } else {
                $images.each((_, img) => {
                    const $img = $(img);
                    const src = $img.attr('data-src');
                    const srcset = $img.attr('data-srcset');
                    if (src) {
                        if (srcset) {
                            $img.attr('srcset', srcset).removeAttr('data-srcset');
                        }
                        $img.attr('src', src).removeAttr('data-src').addClass('lazy-loaded');
                    }
                });
            }
        },

        fallback() {
            $('img[data-src]').each((_, img) => {
                const $img = $(img);
                const src = $img.attr('data-src');
                const srcset = $img.attr('data-srcset');
                if (src) {
                    if (srcset) {
                        $img.attr('srcset', srcset).removeAttr('data-srcset');
                    }
                    $img.attr('src', src).removeAttr('data-src').addClass('lazy-loaded');
                }
            });
        },

        initWithContainer(options = {}) {
            this.init({
                selector: 'img[data-src]',
                ...options
            });
        },

        loadImages(selector) {
            $(selector).each((_, img) => {
                const $img = $(img);
                const src = $img.attr('data-src');
                const srcset = $img.attr('data-srcset');
                if (src) {
                    if (srcset) {
                        $img.attr('srcset', srcset).removeAttr('data-srcset');
                    }
                    $img.attr('src', src).removeAttr('data-src').addClass('lazy-loaded');
                }
            });
        },
    };

    modules.ImageLightbox = {
        init(options = {}) {
            const settings = {
                selector: '[data-lightbox]',
                groupAttribute: 'data-lightbox-group',
                closeOnOverlayClick: true,
                closeOnEscape: true,
                enableKeyboardNav: true,
                prevText: '上一张',
                nextText: '下一张',
                closeText: '关闭',
                ...options
            };

            const $triggers = $(settings.selector);

            $triggers.on('click', function(e) {
                e.preventDefault();
                const $trigger = $(this);
                const group = $trigger.attr(settings.groupAttribute);
                let $images;

                if (group) {
                    $images = $(`[${settings.groupAttribute}="${group}"]`);
                } else {
                    $images = $trigger;
                }

                const currentIndex = $images.index($trigger);
                modules.ImageLightbox.open($images, currentIndex, settings);
            });
        },

        open($images, currentIndex, settings) {
            const imageData = $images.map(function() {
                const $el = $(this);
                return {
                    src: $el.attr('href') || $el.data('src') || $el.attr('src'),
                    title: $el.attr('title') || $el.data('title') || ''
                };
            }).get();

            const lightbox = this.createLightbox(imageData, currentIndex, settings);
            $('body').append(lightbox).addClass('lightbox-open');

            this.setupNavigation(imageData, currentIndex, settings);
            this.setupClose(settings);
        },

        createLightbox(imageData, currentIndex, settings) {
            const current = imageData[currentIndex];
            const lightbox = `
                <div class="lightbox-overlay">
                    <div class="lightbox-container">
                        <button class="lightbox-close" aria-label="${settings.closeText}">
                            <span class="lightbox-close-icon">&times;</span>
                        </button>
                        <button class="lightbox-prev" aria-label="${settings.prevText}">
                            <span class="lightbox-arrow lightbox-arrow-left">&#x2039;</span>
                        </button>
                        <button class="lightbox-next" aria-label="${settings.nextText}">
                            <span class="lightbox-arrow lightbox-arrow-right">&#x203A;</span>
                        </button>
                        <div class="lightbox-content">
                            <img src="${current.src}" alt="${current.title}" class="lightbox-image" />
                            ${current.title ? `<div class="lightbox-caption">${current.title}</div>` : ''}
                        </div>
                        <div class="lightbox-counter">
                            ${currentIndex + 1} / ${imageData.length}
                        </div>
                    </div>
                </div>
            `;
            return lightbox;
        },

        setupNavigation(imageData, currentIndex, settings) {
            const $overlay = $('.lightbox-overlay');
            let current = currentIndex;

            $overlay.on('click', '.lightbox-prev', () => {
                current = current > 0 ? current - 1 : imageData.length - 1;
                this.updateImage(current, imageData);
            });

            $overlay.on('click', '.lightbox-next', () => {
                current = current < imageData.length - 1 ? current + 1 : 0;
                this.updateImage(current, imageData);
            });

            if (settings.enableKeyboardNav) {
                $(document).on('keydown.lightbox', (e) => {
                    if (e.key === 'ArrowLeft') {
                        current = current > 0 ? current - 1 : imageData.length - 1;
                        this.updateImage(current, imageData);
                    } else if (e.key === 'ArrowRight') {
                        current = current < imageData.length - 1 ? current + 1 : 0;
                        this.updateImage(current, imageData);
                    } else if (e.key === 'Escape' && settings.closeOnEscape) {
                        this.close();
                    }
                });
            }
        },

        updateImage(index, imageData) {
            const $image = $('.lightbox-image');
            const $caption = $('.lightbox-caption');
            const $counter = $('.lightbox-counter');
            const data = imageData[index];

            $image.fadeOut(200, () => {
                $image.attr('src', data.src).attr('alt', data.title);
                if (data.title) {
                    $caption.text(data.title).show();
                } else {
                    $caption.hide();
                }
                $counter.text(`${index + 1} / ${imageData.length}`);
                $image.fadeIn(200);
            });
        },

        setupClose(settings) {
            const $overlay = $('.lightbox-overlay');

            $overlay.on('click', '.lightbox-close', () => {
                this.close();
            });

            if (settings.closeOnOverlayClick) {
                $overlay.on('click', (e) => {
                    if (e.target === $overlay[0]) {
                        this.close();
                    }
                });
            }
        },

        close() {
            $('.lightbox-overlay').fadeOut(200, () => {
                $(this).remove();
            });
            $('body').removeClass('lightbox-open');
            $(document).off('keydown.lightbox');
        }
    };

    modules.ImagePreview = {
        init(options = {}) {
            const settings = {
                selector: '.image-preview',
                triggerSelector: '.preview-trigger',
                ...options
            };

            $(document).on('mouseenter', settings.selector, function() {
                const $container = $(this);
                const $preview = $container.find('.preview-content');
                if ($preview.length) {
                    $preview.stop(true, true).fadeIn(200);
                }
            });

            $(document).on('mouseleave', settings.selector, function() {
                const $container = $(this);
                const $preview = $container.find('.preview-content');
                if ($preview.length) {
                    $preview.stop(true, true).fadeOut(200);
                }
            });
        }
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