<?php
/**
 * Test runner simple (no PHPUnit dependency)
 * Run: php run_tests.php
 */
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/config.php';

if (session_status() === PHP_SESSION_NONE) {
    $_SESSION = [];
}

$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void {
    global $passed, $failed;
    try {
        $fn();
        echo "  ✅ $name\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  ❌ $name: " . $e->getMessage() . "\n";
        $failed++;
    }
}

function assert_true(mixed $v, string $msg = ''): void {
    if ($v !== true) throw new Exception($msg ?: 'Expected true, got ' . var_export($v, true));
}
function assert_false(mixed $v, string $msg = ''): void {
    if ($v !== false) throw new Exception($msg ?: 'Expected false, got ' . var_export($v, true));
}
function assert_null(mixed $v, string $msg = ''): void {
    if ($v !== null) throw new Exception($msg ?: 'Expected null, got ' . var_export($v, true));
}
function assert_not_null(mixed $v, string $msg = ''): void {
    if ($v === null) throw new Exception($msg ?: 'Expected not null');
}
function assert_eq(mixed $a, mixed $b, string $msg = ''): void {
    if ($a !== $b) throw new Exception($msg ?: "Expected " . var_export($a, true) . " === " . var_export($b, true));
}
function assert_contains(string $needle, string $haystack, string $msg = ''): void {
    if (!str_contains($haystack, $needle)) throw new Exception($msg ?: "Expected '$needle' not found in string");
}

echo "\n=== HelpersTest ===\n";

test('paginate first page', function () {
    $p = paginate(100, 1, 10);
    assert_eq(1, $p['current']);
    assert_eq(10, $p['per_page']);
    assert_eq(100, $p['total']);
    assert_eq(10, $p['pages']);
    assert_eq(0, $p['offset']);
});

test('paginate last page', function () {
    $p = paginate(100, 10, 10);
    assert_eq(10, $p['current']);
    assert_eq(90, $p['offset']);
});

test('paginate clamps page too high', function () {
    $p = paginate(50, 999, 10);
    assert_eq(5, $p['current']);
    assert_eq(5, $p['pages']);
});

test('paginate clamps page too low', function () {
    $p = paginate(50, 0, 10);
    assert_eq(1, $p['current']);
});

test('paginate zero total', function () {
    $p = paginate(0, 1, 10);
    assert_eq(1, $p['pages']);
    assert_eq(0, $p['offset']);
});

test('validate_password_strength ok', function () {
    assert_null(validate_password_strength('Abcdef1x'));
});

test('validate_password_strength too short', function () {
    assert_not_null(validate_password_strength('Ab1x'));
});

test('validate_password_strength no upper', function () {
    assert_not_null(validate_password_strength('abcdef1xx'));
});

test('validate_password_strength no lower', function () {
    assert_not_null(validate_password_strength('ABCDEF1XX'));
});

test('validate_password_strength no digit', function () {
    assert_not_null(validate_password_strength('Abcdefghx'));
});

test('validate_password_strength too long', function () {
    assert_not_null(validate_password_strength('Abcdef1x' . str_repeat('x', 20)));
});

test('sanitize_filename generates random safe name', function () {
    $name = sanitize_filename('../../etc/passwd.php');
    assert_true(str_ends_with($name, '.php'), "Should end with .php");
    assert_false(str_contains($name, '..'), "Should not contain ..");
    assert_false(str_contains($name, '/'), "Should not contain /");
});

test('sanitize_filename removes bad extension chars', function () {
    $name = sanitize_filename('malicious.php;.jpg');
    assert_true(str_ends_with($name, '.jpg'), "Should end with .jpg");
});

test('csrf_token generates and reuses', function () {
    $_SESSION = [];
    $token1 = csrf_token();
    assert_true(strlen($token1) > 0);
    $token2 = csrf_token();
    assert_eq($token1, $token2);
});

echo "\n=== PaginationRenderTest ===\n";

test('render returns empty for single page', function () {
    $p = paginate(5, 1, 10);
    $html = render_pagination($p, 'dashboard.php?page=recursos');
    assert_eq('', $html);
});

test('render contains nav', function () {
    $p = paginate(100, 1, 10);
    $html = render_pagination($p, 'dashboard.php?page=recursos');
    assert_contains('<nav', $html);
    assert_contains('pagination', $html);
});

test('render first page active', function () {
    $p = paginate(100, 1, 10);
    $html = render_pagination($p, 'dashboard.php?page=recursos');
    assert_contains('page-item active', $html);
    assert_contains('page=1', $html);
});

test('render last page', function () {
    $p = paginate(100, 10, 10);
    $html = render_pagination($p, 'dashboard.php?page=recursos');
    assert_contains('page=10"', $html);
});

test('render has previous disabled on page 1', function () {
    $p = paginate(100, 1, 10);
    $html = render_pagination($p, 'dashboard.php?page=recursos');
    assert_contains('disabled', $html);
});

echo "\n===========================";
echo "\nResultados: $passed passed, $failed failed\n";
echo "===========================\n";
exit($failed > 0 ? 1 : 0);
