<?php
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testPaginateFirstPage()
    {
        $p = paginate(100, 1, 10);
        $this->assertSame(1, $p['current']);
        $this->assertSame(10, $p['per_page']);
        $this->assertSame(100, $p['total']);
        $this->assertSame(10, $p['pages']);
        $this->assertSame(0, $p['offset']);
    }

    public function testPaginateLastPage()
    {
        $p = paginate(100, 10, 10);
        $this->assertSame(10, $p['current']);
        $this->assertSame(90, $p['offset']);
    }

    public function testPaginateClampsPageTooHigh()
    {
        $p = paginate(50, 999, 10);
        $this->assertSame(5, $p['current']);
        $this->assertSame(5, $p['pages']);
    }

    public function testPaginateClampsPageTooLow()
    {
        $p = paginate(50, 0, 10);
        $this->assertSame(1, $p['current']);
    }

    public function testPaginateZeroTotal()
    {
        $p = paginate(0, 1, 10);
        $this->assertSame(1, $p['pages']);
        $this->assertSame(0, $p['offset']);
    }

    public function testValidatePasswordOk()
    {
        $this->assertNull(validate_password_strength('Abcdef1x'));
    }

    public function testValidatePasswordTooShort()
    {
        $this->assertNotNull(validate_password_strength('Ab1x'));
    }

    public function testValidatePasswordNoUpper()
    {
        $this->assertNotNull(validate_password_strength('abcdef1xx'));
    }

    public function testValidatePasswordNoLower()
    {
        $this->assertNotNull(validate_password_strength('ABCDEF1XX'));
    }

    public function testValidatePasswordNoDigit()
    {
        $this->assertNotNull(validate_password_strength('Abcdefghx'));
    }

    public function testValidatePasswordTooLong()
    {
        $this->assertNotNull(validate_password_strength('Abcdef1x' . str_repeat('x', 20)));
    }

    public function testSanitizeFilenameGeneratesRandomName()
    {
        $name = sanitize_filename('../../etc/passwd.php');
        $this->assertStringEndsWith('.php', $name);
        $this->assertStringNotContainsString('..', $name);
        $this->assertStringNotContainsString('/', $name);
    }

    public function testSanitizeFilenameRemovesBadExtension()
    {
        $name = sanitize_filename('malicious.php;.jpg');
        $this->assertStringEndsWith('.jpg', $name);
    }

    public function testCsrfTokenGenerates()
    {
        $_SESSION = [];
        $token1 = csrf_token();
        $this->assertNotEmpty($token1);
        $token2 = csrf_token();
        $this->assertSame($token1, $token2);
    }
}
