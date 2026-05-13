const FoxFormValidator = (function($) {
    const validator = FoxModules && FoxModules.Validator ? FoxModules.Validator : null;
    const debounce = FoxModules && FoxModules.Debounce ? FoxModules.Debounce : null;

    function init() {
        bindEvents();
    }

    function bindEvents() {
        $(document).on('click', '#requiredForm .submit-btn', function (event) {
            event.preventDefault();
            const $form = $(this).closest('form');
            if (validateForm($form)) {
                $form.find('input[type="submit"]').click();
            }
        });

        const debouncedValidate = debounce ? debounce.create(validateField, 300) : validateField;
        
        $(document).on('input', 'input[name="text0"]', function () {
            debouncedValidate.call(this, 'name');
        });

        $(document).on('input', 'input[name="text1"]', function () {
            debouncedValidate.call(this, 'phone');
        });

        $(document).on('input', 'input[name="text2"]', function () {
            debouncedValidate.call(this, 'requirement');
        });
    }

    function validateField(fieldType) {
        const $field = $(this);
        let isValid = false;

        switch (fieldType) {
            case 'name':
                isValid = validator ? validator.notEmpty($field.val()) : notEmpty($field.val());
                break;
            case 'phone':
                isValid = validator ? validator.isPhone($field.val()) : isPhone($field.val());
                break;
            case 'requirement':
                isValid = validator ? validator.notEmpty($field.val()) : notEmpty($field.val());
                break;
            default:
                isValid = true;
        }

        $field.toggleClass('err', !isValid);
    }

    function validateForm($form) {
        const $text0 = $form.find('input[name="text0"]'),
              $text1 = $form.find('input[name="text1"]'),
              $text2 = $form.find('input[name="text2"]'),
              text0 = $text0.val(),
              text1 = $text1.val(),
              text2 = $text2.val();

        const isText0Valid = validator ? validator.notEmpty(text0) : notEmpty(text0);
        const isText1Valid = validator ? validator.isPhone(text1) : isPhone(text1);
        const isText2Valid = validator ? validator.notEmpty(text2) : notEmpty(text2);

        if (!isText0Valid) {
            showError('请填写阁下姓名', $text0);
            return false;
        } else if (!isText1Valid) {
            showError('请填写正确的手机号码', $text1);
            return false;
        } else if (!isText2Valid) {
            showError('请填写您的需求', $text2);
            return false;
        }

        return true;
    }

    function showError(message, $field) {
        if (typeof foxui !== 'undefined') {
            foxui.message({
                text: message,
                type: 'danger',
            });
        } else {
            alert(message);
        }
        $field.addClass('err');
    }

    function isPhone(phone) {
        const mobileReg = /^1[3|4|5|7|8|9]\d{9}$/;
        const teleReg = /^((0\d{2,3})-)?(\d{7,8})$/;
        return mobileReg.test(phone) || teleReg.test(phone);
    }

    function notEmpty(value) {
        return value !== null && value !== undefined && value !== '';
    }

    return {
        init,
        validateForm,
    };
})(jQuery);

$(document).ready(function() {
    FoxFormValidator.init();
});
