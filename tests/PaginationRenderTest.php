<?php
use PHPUnit\Framework\TestCase;

class PaginationRenderTest extends TestCase
{
    public function testRenderReturnsEmptyForSinglePage()
    {
        $p = paginate(5, 1, 10);
        $html = render_pagination($p, 'dashboard.php?page=recursos');
        $this->assertSame('', $html);
    }

    public function testRenderContainsNav()
    {
        $p = paginate(100, 1, 10);
        $html = render_pagination($p, 'dashboard.php?page=recursos');
        $this->assertStringContainsString('<nav', $html);
        $this->assertStringContainsString('pagination', $html);
    }

    public function testRenderFirstPageActive()
    {
        $p = paginate(100, 1, 10);
        $html = render_pagination($p, 'dashboard.php?page=recursos');
        $this->assertStringContainsString('page-item active', $html);
        $this->assertStringContainsString('page=1', $html);
    }

    public function testRenderLastPage()
    {
        $p = paginate(100, 10, 10);
        $html = render_pagination($p, 'dashboard.php?page=recursos');
        $this->assertStringContainsString('page=10"', $html);
    }

    public function testRenderHasPreviousDisabled()
    {
        $p = paginate(100, 1, 10);
        $html = render_pagination($p, 'dashboard.php?page=recursos');
        $this->assertStringContainsString('disabled', $html);
    }
}
