<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Sirmerdas\Sparkle\Traits\QueryComponents;

final class QueryComponentsTest extends TestCase
{
    use QueryComponents;
    public function test_build_limit_offset_query(): void
    {
        $this->assertEquals("LIMIT 10 OFFSET 5", $this->buildLimitOffsetQuery(10, 5));
        $this->assertNull($this->buildLimitOffsetQuery(0, 5));
    }

    public function test_build_where_query(): void
    {
        $this->assertEquals("WHERE id = 1 ", $this->buildWhereQuery(['id = 1'], []));
        $this->assertEquals("WHERE name = 'sirmerdas'  OR age > 30", $this->buildWhereQuery(["name = 'sirmerdas'"], ["age > 30"]));
        $this->assertNull($this->buildWhereQuery([], []));
    }

    public function test_build_order_by_query(): void
    {
        $this->assertEquals(" ORDER BY name ASC,age DESC", $this->buildOrderByQuery(["name ASC", "age DESC"]));
        $this->assertNull($this->buildOrderByQuery([]));
    }

    public function test_build_group_by_query(): void
    {
        $this->assertEquals("GROUP BY category", $this->buildGroupByQuery("category"));
        $this->assertNull($this->buildGroupByQuery(null));
    }

    public function test_build_having_query(): void
    {
        $this->assertEquals(" HAVING count(id) > 5", $this->buildHavingQuery(["count(id) > 5"]));
        $this->assertNull($this->buildHavingQuery([]));
    }

    public function test_build_join_query(): void
    {
        $this->assertEquals("INNER JOIN users ON users.id = posts.user_id", $this->buildJoinQuery(["INNER JOIN users ON users.id = posts.user_id"]));
        $this->assertNull($this->buildJoinQuery([]));
    }

    public function test_build_insert_columns_query(): void
    {
        $this->assertEquals("`name`, `email`, `password`", $this->buildInsertColumnsQuery(["name", "email", "password"]));
    }

    public function test_build_insert_placeholder(): void
    {
        $this->assertEquals("?, ?, ?", $this->buildInsertPlaceholder(["name", "email", "password"]));
    }

    public function test_needs_backtick(): void
    {
        $this->assertEquals(true, $this->needsBacktick("order.customerNumber"));
        $this->assertEquals(false, $this->needsBacktick("customers.CustomerNumber"));
    }

    public function tests_quote_column(): void
    {
        $this->assertEquals("`order`.`customerNumber`", $this->quoteColumn("order.customerNumber"));
        $this->assertEquals("customers.CustomerNumber", $this->quoteColumn("customers.CustomerNumber"));
    }
}
