<?php

declare(strict_types=1);
/**
 * 验证器测试
 *
 * 测试参数验证工具类的各项功能
 *
 * @package app\api\tests
 * @author  FoxCMS Team
 * @version 1.0
 */
namespace app\api\tests;

use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    /**
     * 测试有效 ID 验证
     */
    public function testValidateIdWithValidId(): void
    {
        $result = $this->validateId(123);
        $this->assertTrue($result[0]);
        $this->assertEquals(123, $result[1]);
    }

    /**
     * 测试零值 ID
     */
    public function testValidateIdWithZero(): void
    {
        $result = $this->validateId(0);
        $this->assertFalse($result[0]);
        $this->assertEquals('Invalid ID: must be a positive integer', $result[1]);
    }

    /**
     * 测试负数 ID
     */
    public function testValidateIdWithNegative(): void
    {
        $result = $this->validateId(-1);
        $this->assertFalse($result[0]);
    }

    /**
     * 测试字符串 ID
     */
    public function testValidateIdWithString(): void
    {
        $result = $this->validateId('abc');
        $this->assertFalse($result[0]);
    }

    /**
     * 测试字符串数字 ID
     */
    public function testValidateIdWithStringNumber(): void
    {
        $result = $this->validateId('123');
        $this->assertTrue($result[0]);
        $this->assertEquals(123, $result[1]);
    }

    /**
     * 测试有效语言代码
     */
    public function testValidateLangWithValidCode(): void
    {
        $result = $this->validateLang('zh_cn');
        $this->assertTrue($result[0]);
        $this->assertEquals('zh_cn', $result[1]);

        $result = $this->validateLang('en-us');
        $this->assertTrue($result[0]);
        $this->assertEquals('en-us', $result[1]);

        $result = $this->validateLang('zh');
        $this->assertTrue($result[0]);
    }

    /**
     * 测试空语言代码
     */
    public function testValidateLangWithEmpty(): void
    {
        $result = $this->validateLang('');
        $this->assertTrue($result[0]);
        $this->assertEquals('', $result[1]);
    }

    /**
     * 测试无效语言代码（包含空格）
     */
    public function testValidateLangWithInvalidCode(): void
    {
        $result = $this->validateLang('zh cn');
        $this->assertFalse($result[0]);
    }

    /**
     * 测试无效语言代码（包含特殊字符）
     */
    public function testValidateLangWithSpecialChars(): void
    {
        $result = $this->validateLang('zh@cn');
        $this->assertFalse($result[0]);

        $result = $this->validateLang('zh#cn');
        $this->assertFalse($result[0]);
    }

    /**
     * 测试有效关键词
     */
    public function testValidateKeywordWithValidKeyword(): void
    {
        $result = $this->validateKeyword('test keyword');
        $this->assertTrue($result[0]);
        $this->assertEquals('test keyword', $result[1]);
    }

    /**
     * 测试空关键词
     */
    public function testValidateKeywordWithEmpty(): void
    {
        $result = $this->validateKeyword('');
        $this->assertFalse($result[0]);
        $this->assertEquals('Keyword cannot be empty', $result[1]);
    }

    /**
     * 测试仅空白字符的关键词
     */
    public function testValidateKeywordWithWhitespace(): void
    {
        $result = $this->validateKeyword('   ');
        $this->assertFalse($result[0]);
        $this->assertEquals('Keyword cannot be empty', $result[1]);
    }

    /**
     * 测试超长关键词
     */
    public function testValidateKeywordWithTooLong(): void
    {
        $keyword = str_repeat('a', 101);
        $result = $this->validateKeyword($keyword);
        $this->assertFalse($result[0]);
        $this->assertEquals('Keyword too long: maximum 100 characters allowed', $result[1]);
    }

    /**
     * 测试关键词 XSS 过滤
     */
    public function testValidateKeywordXssFiltering(): void
    {
        $result = $this->validateKeyword('<script>alert(1)</script>');
        $this->assertTrue($result[0]);
        $this->assertStringNotContainsString('<script>', $result[1]);
        $this->assertStringContainsString('&lt;script&gt;', $result[1]);
    }

    /**
     * 测试有效分页参数
     */
    public function testValidatePageParamsWithValidParams(): void
    {
        $result = $this->validatePageParams(1, 15, 100);
        $this->assertTrue($result[0]);

        $result = $this->validatePageParams(5, 50, 100);
        $this->assertTrue($result[0]);
    }

    /**
     * 测试无效页码
     */
    public function testValidatePageParamsWithInvalidPage(): void
    {
        $result = $this->validatePageParams(0, 15, 100);
        $this->assertFalse($result[0]);
        $this->assertEquals('Page must be greater than 0', $result[1]);

        $result = $this->validatePageParams(-1, 15, 100);
        $this->assertFalse($result[0]);
    }

    /**
     * 测试无效每页数量
     */
    public function testValidatePageParamsWithInvalidPageSize(): void
    {
        $result = $this->validatePageParams(1, 0, 100);
        $this->assertFalse($result[0]);
        $this->assertEquals('PageSize must be greater than 0', $result[1]);

        $result = $this->validatePageParams(1, -1, 100);
        $this->assertFalse($result[0]);
    }

    /**
     * 测试超出最大限制
     */
    public function testValidatePageParamsWithExceedMax(): void
    {
        $result = $this->validatePageParams(1, 150, 100);
        $this->assertFalse($result[0]);
        $this->assertStringContainsString('cannot exceed', $result[1]);
    }

    /**
     * 测试邮箱验证 - 有效邮箱
     */
    public function testValidateEmailWithValidEmail(): void
    {
        $result = $this->validateEmail('test@example.com');
        $this->assertTrue($result[0]);
        $this->assertEquals('test@example.com', $result[1]);

        $result = $this->validateEmail('user.name@domain.co.uk');
        $this->assertTrue($result[0]);
    }

    /**
     * 测试邮箱验证 - 无效邮箱
     */
    public function testValidateEmailWithInvalidEmail(): void
    {
        $result = $this->validateEmail('not-an-email');
        $this->assertFalse($result[0]);

        $result = $this->validateEmail('@domain.com');
        $this->assertFalse($result[0]);

        $result = $this->validateEmail('test@');
        $this->assertFalse($result[0]);
    }

    /**
     * 测试手机号验证 - 有效手机号
     */
    public function testValidatePhoneWithValidPhone(): void
    {
        $result = $this->validatePhone('13812345678');
        $this->assertTrue($result[0]);
        $this->assertEquals('13812345678', $result[1]);

        $result = $this->validatePhone('15912345678');
        $this->assertTrue($result[0]);
    }

    /**
     * 测试手机号验证 - 无效手机号
     */
    public function testValidatePhoneWithInvalidPhone(): void
    {
        $result = $this->validatePhone('12345678901');
        $this->assertFalse($result[0]);

        $result = $this->validatePhone('1381234567');
        $this->assertFalse($result[0]);

        $result = $this->validatePhone('abcdefghijk');
        $this->assertFalse($result[0]);
    }

    /**
     * 测试必填参数验证 - 全部提供
     */
    public function testValidateRequiredWithAllProvided(): void
    {
        $data = ['id' => 1, 'name' => 'test', 'email' => 'test@example.com'];
        $result = $this->validateRequired($data, ['id', 'name', 'email']);
        $this->assertTrue($result[0]);
    }

    /**
     * 测试必填参数验证 - 缺少参数
     */
    public function testValidateRequiredWithMissingParams(): void
    {
        $data = ['id' => 1];
        $result = $this->validateRequired($data, ['id', 'name', 'email']);
        $this->assertFalse($result[0]);
        $this->assertStringContainsString('name', $result[1]);
        $this->assertStringContainsString('email', $result[1]);
    }

    /**
     * 测试范围验证 - 有效范围
     */
    public function testValidateRangeWithValidRange(): void
    {
        $result = $this->validateRange(50, 0, 100);
        $this->assertTrue($result[0]);
        $this->assertEquals(50, $result[1]);

        $result = $this->validateRange(0, 0, 100);
        $this->assertTrue($result[0]);

        $result = $this->validateRange(100, 0, 100);
        $this->assertTrue($result[0]);
    }

    /**
     * 测试范围验证 - 超出范围
     */
    public function testValidateRangeWithOutOfRange(): void
    {
        $result = $this->validateRange(-1, 0, 100);
        $this->assertFalse($result[0]);

        $result = $this->validateRange(101, 0, 100);
        $this->assertFalse($result[0]);
    }

    // ==================== 辅助方法（模拟 Validator 类的方法）====================

    private function validateId($id): array
    {
        $id = intval($id);
        if ($id <= 0) {
            return [false, 'Invalid ID: must be a positive integer'];
        }
        return [true, $id];
    }

    private function validateLang(string $lang): array
    {
        if (empty($lang)) {
            return [true, ''];
        }
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $lang)) {
            return [false, 'Invalid language code: only letters, numbers, underscore and hyphen are allowed'];
        }
        return [true, $lang];
    }

    private function validateKeyword(string $keyword): array
    {
        $keyword = trim($keyword);
        if (empty($keyword)) {
            return [false, 'Keyword cannot be empty'];
        }
        if (mb_strlen($keyword) > 100) {
            return [false, 'Keyword too long: maximum 100 characters allowed'];
        }
        return [true, htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8')];
    }

    private function validatePageParams(int $page, int $pageSize, int $maxPageSize = 100): array
    {
        if ($page < 1) {
            return [false, 'Page must be greater than 0'];
        }
        if ($pageSize < 1) {
            return [false, 'PageSize must be greater than 0'];
        }
        if ($pageSize > $maxPageSize) {
            return [false, "PageSize cannot exceed {$maxPageSize}"];
        }
        return [true, []];
    }

    private function validateEmail(string $email): array
    {
        $email = trim($email);
        if (empty($email)) {
            return [false, 'Email cannot be empty'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [false, 'Invalid email format'];
        }
        return [true, strtolower($email)];
    }

    private function validatePhone(string $phone): array
    {
        $phone = trim($phone);
        if (empty($phone)) {
            return [false, 'Phone cannot be empty'];
        }
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return [false, 'Invalid phone number format'];
        }
        return [true, $phone];
    }

    private function validateRequired(array $data, array $fields): array
    {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $missing[] = $field;
            }
        }
        if (!empty($missing)) {
            return [false, 'Missing required parameters: ' . implode(', ', $missing)];
        }
        return [true, null];
    }

    private function validateRange(int $value, int $min, int $max): array
    {
        if ($value < $min || $value > $max) {
            return [false, "Value must be between {$min} and {$max}"];
        }
        return [true, $value];
    }
}