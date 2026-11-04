<?php

namespace Primexcalibur\CheckinWithHistory\Tests\unit;

use Flarum\Extension\ExtensionManager;
use Flarum\Testing\unit\TestCase;
use Flarum\User\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Mockery as m;
use Primexcalibur\CheckinWithHistory\Support\CheckinCardManager;

class CheckinCardManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    /** @test */
    public function is_store_enabled_returns_true_when_extension_enabled(): void
    {
        $extensions = m::mock(ExtensionManager::class);
        $extensions->shouldReceive('isEnabled')->with('mattoid-store')->andReturn(true);

        $connection = m::mock(ConnectionInterface::class);

        $manager = new CheckinCardManager($extensions, $connection);

        $this->assertTrue($manager->isStoreEnabled());
    }

    /** @test */
    public function is_store_enabled_returns_true_when_table_exists(): void
    {
        $extensions = m::mock(ExtensionManager::class);
        $extensions->shouldReceive('isEnabled')->with('mattoid-store')->andReturn(false);

        $schema = m::mock(SchemaBuilder::class);
        $schema->shouldReceive('hasTable')->with('store_cart')->andReturn(true);

        $connection = m::mock(ConnectionInterface::class);
        $connection->shouldReceive('getSchemaBuilder')->andReturn($schema);

        $manager = new CheckinCardManager($extensions, $connection);

        $this->assertTrue($manager->isStoreEnabled());
    }

    /** @test */
    public function is_store_enabled_returns_false_when_neither_extension_nor_table_exists(): void
    {
        $extensions = m::mock(ExtensionManager::class);
        $extensions->shouldReceive('isEnabled')->with('mattoid-store')->andReturn(false);

        $schema = m::mock(SchemaBuilder::class);
        $schema->shouldReceive('hasTable')->with('store_cart')->andReturn(false);

        $connection = m::mock(ConnectionInterface::class);
        $connection->shouldReceive('getSchemaBuilder')->andReturn($schema);

        $manager = new CheckinCardManager($extensions, $connection);

        $this->assertFalse($manager->isStoreEnabled());
    }

    /** @test */
    public function get_available_cards_returns_zero_when_store_disabled(): void
    {
        $extensions = m::mock(ExtensionManager::class);
        $extensions->shouldReceive('isEnabled')->with('mattoid-store')->andReturn(false);

        $schema = m::mock(SchemaBuilder::class);
        $schema->shouldReceive('hasTable')->with('store_cart')->andReturn(false);

        $connection = m::mock(ConnectionInterface::class);
        $connection->shouldReceive('getSchemaBuilder')->andReturn($schema);

        $manager = new CheckinCardManager($extensions, $connection);

        $user = new User();
        $user->setRawAttributes(['id' => 2]);

        $this->assertSame(0, $manager->getAvailableCards($user));
    }

    /** @test */
    public function consume_card_returns_false_when_store_disabled(): void
    {
        $extensions = m::mock(ExtensionManager::class);
        $extensions->shouldReceive('isEnabled')->with('mattoid-store')->andReturn(false);

        $schema = m::mock(SchemaBuilder::class);
        $schema->shouldReceive('hasTable')->with('store_cart')->andReturn(false);

        $connection = m::mock(ConnectionInterface::class);
        $connection->shouldReceive('getSchemaBuilder')->andReturn($schema);

        $manager = new CheckinCardManager($extensions, $connection);

        $user = new User();
        $user->setRawAttributes(['id' => 2]);

        $this->assertFalse($manager->consumeCard($user));
    }

    /** @test */
    public function get_available_cards_counts_permanent_cards_even_with_past_outtime(): void
    {
        $connection = $this->createMemoryConnection();
        $extensions = m::mock(ExtensionManager::class);
        $extensions->shouldReceive('isEnabled')->with('mattoid-store')->andReturn(true);

        $now = \Carbon\Carbon::now();

        // 1. Permanent card with past outtime (should be available)
        $connection->table('store_cart')->insert([
            'user_id' => 2,
            'code' => 'checkin-card',
            'status' => 1,
            'type' => 'permanent',
            'outtime' => '1970-01-01 00:00:00',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 2. Expired time-limited card (should NOT be available)
        $connection->table('store_cart')->insert([
            'user_id' => 2,
            'code' => 'checkin-card',
            'status' => 1,
            'type' => 'limit',
            'outtime' => '2020-01-01 00:00:00',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 3. Valid time-limited card in future (should be available)
        $connection->table('store_cart')->insert([
            'user_id' => 2,
            'code' => 'checkin-card',
            'status' => 1,
            'type' => 'limit',
            'outtime' => $now->copy()->addDays(5)->toDateTimeString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 4. Inactive card (status = 3, consumed)
        $connection->table('store_cart')->insert([
            'user_id' => 2,
            'code' => 'checkin-card',
            'status' => 3,
            'type' => 'permanent',
            'outtime' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $manager = new CheckinCardManager($extensions, $connection);
        $user = new User();
        $user->setRawAttributes(['id' => 2]);

        $this->assertSame(2, $manager->getAvailableCards($user));
    }

    /** @test */
    public function consume_card_prioritizes_soonest_expiring_card_over_permanent_card(): void
    {
        $connection = $this->createMemoryConnection();
        $extensions = m::mock(ExtensionManager::class);
        $extensions->shouldReceive('isEnabled')->with('mattoid-store')->andReturn(true);

        $now = \Carbon\Carbon::now();

        // id 1: Permanent card
        $connection->table('store_cart')->insert([
            'id' => 1,
            'user_id' => 2,
            'code' => 'checkin-card',
            'status' => 1,
            'type' => 'permanent',
            'outtime' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // id 2: Time-limited card expiring in 10 days
        $connection->table('store_cart')->insert([
            'id' => 2,
            'user_id' => 2,
            'code' => 'checkin-card',
            'status' => 1,
            'type' => 'limit',
            'outtime' => $now->copy()->addDays(10)->toDateTimeString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // id 3: Time-limited card expiring in 2 days (soonest!)
        $connection->table('store_cart')->insert([
            'id' => 3,
            'user_id' => 2,
            'code' => 'checkin-card',
            'status' => 1,
            'type' => 'limit',
            'outtime' => $now->copy()->addDays(2)->toDateTimeString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $manager = new CheckinCardManager($extensions, $connection);
        $user = new User();
        $user->setRawAttributes(['id' => 2]);

        // First consumption: should consume id 3 (soonest expiring limit card)
        $this->assertTrue($manager->consumeCard($user));
        $this->assertSame(3, (int) $connection->table('store_cart')->where('id', 3)->value('status'));
        $this->assertSame(1, (int) $connection->table('store_cart')->where('id', 2)->value('status'));
        $this->assertSame(1, (int) $connection->table('store_cart')->where('id', 1)->value('status'));

        // Second consumption: should consume id 2 (remaining limit card)
        $this->assertTrue($manager->consumeCard($user));
        $this->assertSame(3, (int) $connection->table('store_cart')->where('id', 2)->value('status'));
        $this->assertSame(1, (int) $connection->table('store_cart')->where('id', 1)->value('status'));

        // Third consumption: should consume id 1 (permanent card)
        $this->assertTrue($manager->consumeCard($user));
        $this->assertSame(3, (int) $connection->table('store_cart')->where('id', 1)->value('status'));

        // Fourth consumption: no cards left
        $this->assertFalse($manager->consumeCard($user));
    }

    private function createMemoryConnection(): ConnectionInterface
    {
        $capsule = new \Illuminate\Database\Capsule\Manager();
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $capsule->setAsGlobal();

        $conn = $capsule->getConnection();
        $conn->getSchemaBuilder()->create('store_cart', function ($table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('code');
            $table->integer('status')->default(1);
            $table->string('type')->default('permanent');
            $table->dateTime('outtime')->nullable();
            $table->timestamps();
        });

        return $conn;
    }
}
