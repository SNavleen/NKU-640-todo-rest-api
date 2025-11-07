<?php

namespace TodoApi\Tests;

use PHPUnit\Framework\TestCase;
use TodoApi\Models\TodoList;
use TodoApi\Services\Database;

class TodoListModelTest extends TestCase
{
    private TodoList $listModel;
    private string $testDbPath;

    protected function setUp(): void
    {
        // Use a test database
        $this->testDbPath = __DIR__ . '/../data/test_todo.db';

        // Clean up any existing test database
        if (file_exists($this->testDbPath)) {
            unlink($this->testDbPath);
        }

        // Override the database path for testing
        putenv('DATABASE_PATH=' . $this->testDbPath);

        $this->listModel = new TodoList();
    }

    protected function tearDown(): void
    {
        // Clean up test database
        if (file_exists($this->testDbPath)) {
            unlink($this->testDbPath);
        }
    }

    public function testCreateList(): void
    {
        $data = [
            'name' => 'Test List',
            'description' => 'Test Description',
        ];

        $list = $this->listModel->create($data);

        $this->assertIsArray($list);
        $this->assertArrayHasKey('id', $list);
        $this->assertEquals('Test List', $list['name']);
        $this->assertEquals('Test Description', $list['description']);
        $this->assertNotNull($list['created_at']);
    }

    public function testFindAllLists(): void
    {
        // Create two lists
        $this->listModel->create(['name' => 'List 1', 'description' => 'Desc 1']);
        $this->listModel->create(['name' => 'List 2', 'description' => 'Desc 2']);

        $lists = $this->listModel->findAll();

        $this->assertIsArray($lists);
        $this->assertCount(2, $lists);
    }

    public function testFindListById(): void
    {
        $created = $this->listModel->create(['name' => 'Test List']);
        $id = $created['id'];

        $found = $this->listModel->findById($id);

        $this->assertIsArray($found);
        $this->assertEquals($id, $found['id']);
        $this->assertEquals('Test List', $found['name']);
    }

    public function testFindNonExistentList(): void
    {
        $result = $this->listModel->findById('550e8400-e29b-41d4-a716-446655440000');

        $this->assertNull($result);
    }

    public function testUpdateList(): void
    {
        $created = $this->listModel->create(['name' => 'Original Name']);
        $id = $created['id'];

        $updated = $this->listModel->update($id, ['name' => 'Updated Name']);

        $this->assertIsArray($updated);
        $this->assertEquals('Updated Name', $updated['name']);
        $this->assertNotNull($updated['updated_at']);
    }

    public function testUpdateNonExistentList(): void
    {
        $result = $this->listModel->update('550e8400-e29b-41d4-a716-446655440000', ['name' => 'Test']);

        $this->assertNull($result);
    }

    public function testDeleteList(): void
    {
        $created = $this->listModel->create(['name' => 'Test List']);
        $id = $created['id'];

        $deleted = $this->listModel->delete($id);

        $this->assertTrue($deleted);

        // Verify it's actually deleted
        $found = $this->listModel->findById($id);
        $this->assertNull($found);
    }

    public function testDeleteNonExistentList(): void
    {
        $result = $this->listModel->delete('550e8400-e29b-41d4-a716-446655440000');

        $this->assertFalse($result);
    }

    public function testCreateListWithoutDescription(): void
    {
        $data = ['name' => 'Minimal List'];

        $list = $this->listModel->create($data);

        $this->assertIsArray($list);
        $this->assertEquals('Minimal List', $list['name']);
        $this->assertNull($list['description']);
    }

    public function testPartialUpdate(): void
    {
        $created = $this->listModel->create([
            'name' => 'Test List',
            'description' => 'Original Description',
        ]);
        $id = $created['id'];

        // Update only description
        $updated = $this->listModel->update($id, ['description' => 'New Description']);

        $this->assertEquals('Test List', $updated['name']);
        $this->assertEquals('New Description', $updated['description']);
    }
}
