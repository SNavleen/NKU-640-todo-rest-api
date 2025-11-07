<?php

namespace TodoApi\Tests;

use PHPUnit\Framework\TestCase;
use TodoApi\Models\Task;
use TodoApi\Models\TodoList;

class TaskModelTest extends TestCase
{
    private Task $taskModel;
    private TodoList $listModel;
    private string $testDbPath;
    private string $testListId;

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

        $this->taskModel = new Task();
        $this->listModel = new TodoList();

        // Create a test list for tasks
        $list = $this->listModel->create(['name' => 'Test List']);
        $this->testListId = $list['id'];
    }

    protected function tearDown(): void
    {
        // Clean up test database
        if (file_exists($this->testDbPath)) {
            unlink($this->testDbPath);
        }
    }

    public function testCreateTask(): void
    {
        $data = [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'priority' => 'high',
            'categories' => ['work', 'urgent'],
        ];

        $task = $this->taskModel->create($this->testListId, $data);

        $this->assertIsArray($task);
        $this->assertArrayHasKey('id', $task);
        $this->assertEquals('Test Task', $task['title']);
        $this->assertEquals('Test Description', $task['description']);
        $this->assertEquals('high', $task['priority']);
        $this->assertEquals(['work', 'urgent'], $task['categories']);
        $this->assertFalse($task['completed']);
    }

    public function testCreateMinimalTask(): void
    {
        $data = ['title' => 'Minimal Task'];

        $task = $this->taskModel->create($this->testListId, $data);

        $this->assertIsArray($task);
        $this->assertEquals('Minimal Task', $task['title']);
        $this->assertNull($task['description']);
        $this->assertNull($task['priority']);
        $this->assertNull($task['categories']);
        $this->assertFalse($task['completed']);
    }

    public function testFindAllTasksByList(): void
    {
        // Create two tasks
        $this->taskModel->create($this->testListId, ['title' => 'Task 1']);
        $this->taskModel->create($this->testListId, ['title' => 'Task 2']);

        $tasks = $this->taskModel->findAllByListId($this->testListId);

        $this->assertIsArray($tasks);
        $this->assertCount(2, $tasks);
    }

    public function testFindTaskById(): void
    {
        $created = $this->taskModel->create($this->testListId, ['title' => 'Test Task']);
        $id = $created['id'];

        $found = $this->taskModel->findById($id);

        $this->assertIsArray($found);
        $this->assertEquals($id, $found['id']);
        $this->assertEquals('Test Task', $found['title']);
    }

    public function testFindNonExistentTask(): void
    {
        $result = $this->taskModel->findById('660e8400-e29b-41d4-a716-446655440000');

        $this->assertNull($result);
    }

    public function testUpdateTask(): void
    {
        $created = $this->taskModel->create($this->testListId, [
            'title' => 'Original Title',
            'completed' => false,
        ]);
        $id = $created['id'];

        $updated = $this->taskModel->update($id, [
            'title' => 'Updated Title',
            'completed' => true,
        ]);

        $this->assertIsArray($updated);
        $this->assertEquals('Updated Title', $updated['title']);
        $this->assertTrue($updated['completed']);
        $this->assertNotNull($updated['updatedAt']);
    }

    public function testUpdateNonExistentTask(): void
    {
        $result = $this->taskModel->update('660e8400-e29b-41d4-a716-446655440000', ['title' => 'Test']);

        $this->assertNull($result);
    }

    public function testDeleteTask(): void
    {
        $created = $this->taskModel->create($this->testListId, ['title' => 'Test Task']);
        $id = $created['id'];

        $deleted = $this->taskModel->delete($id);

        $this->assertTrue($deleted);

        // Verify it's actually deleted
        $found = $this->taskModel->findById($id);
        $this->assertNull($found);
    }

    public function testDeleteNonExistentTask(): void
    {
        $result = $this->taskModel->delete('660e8400-e29b-41d4-a716-446655440000');

        $this->assertFalse($result);
    }

    public function testTaskWithDueDate(): void
    {
        $dueDate = '2025-11-07T18:00:00Z';
        $data = [
            'title' => 'Task with due date',
            'dueDate' => $dueDate,
        ];

        $task = $this->taskModel->create($this->testListId, $data);

        $this->assertEquals($dueDate, $task['dueDate']);
    }

    public function testUpdateTaskPriority(): void
    {
        $created = $this->taskModel->create($this->testListId, [
            'title' => 'Test Task',
            'priority' => 'low',
        ]);
        $id = $created['id'];

        $updated = $this->taskModel->update($id, ['priority' => 'high']);

        $this->assertEquals('high', $updated['priority']);
    }

    public function testUpdateTaskCategories(): void
    {
        $created = $this->taskModel->create($this->testListId, [
            'title' => 'Test Task',
            'categories' => ['old'],
        ]);
        $id = $created['id'];

        $updated = $this->taskModel->update($id, ['categories' => ['new', 'updated']]);

        $this->assertEquals(['new', 'updated'], $updated['categories']);
    }

    public function testPartialUpdatePreservesOtherFields(): void
    {
        $created = $this->taskModel->create($this->testListId, [
            'title' => 'Test Task',
            'description' => 'Original Description',
            'priority' => 'low',
        ]);
        $id = $created['id'];

        // Update only completed status
        $updated = $this->taskModel->update($id, ['completed' => true]);

        $this->assertEquals('Test Task', $updated['title']);
        $this->assertEquals('Original Description', $updated['description']);
        $this->assertEquals('low', $updated['priority']);
        $this->assertTrue($updated['completed']);
    }

    public function testCascadeDeleteTasksWhenListDeleted(): void
    {
        // Create a task
        $task = $this->taskModel->create($this->testListId, ['title' => 'Test Task']);
        $taskId = $task['id'];

        // Delete the list (should cascade delete tasks)
        $this->listModel->delete($this->testListId);

        // Verify task is deleted
        $found = $this->taskModel->findById($taskId);
        $this->assertNull($found);
    }
}
