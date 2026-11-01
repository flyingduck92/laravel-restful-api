<?php

namespace Tests\Feature\Api\V1;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_tasks(): void {
        // Arrange: create 2 data
        $tasks = Task::factory()->count(2)->create();

        // Act: make GET request
        $response = $this->getJson('/api/v1/tasks');

        // Assert: [1] 200(OK) [2] data count = 2
        $response->assertOk();
        $response->assertJsonCount(2,'data');
        $response->assertJsonStructure([
            'data'=> [
                ['id','name','is_completed']
            ]
        ]);

    }

    public function test_single_task(): void {
        // Arrange: create a single data
        $task = Task::factory()->create();

        // Act: make GET request with task ID
        $response = $this->getJson("/api/v1/tasks/$task->id");

        // Assert: [1] 200(OK) [2] single data with task ID
        $response->assertOk();
        $response->assertJsonStructure([
            'data'=>[
                'id','name','is_completed'
            ]
        ]);
        $response->assertJson([
            'data'=> [
                'id'=> $task->id,
                'name'=> $task->name,
                'is_completed'=> $task->is_completed,
            ]
        ]);
    }

    // POST /tasks -> create a new task
    public function test_success_create_a_task(): void {
        // Act
        $response = $this->postJson('/api/v1/tasks', [
            'name' => "New Task"
        ]);

        // Assert
        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => ['id','name','is_completed']
        ]);

        $this->assertDatabaseHas('tasks', [
            'name' => "New Task"
        ]);
    }

    public function test_failed_create_a_task():void {
        $response = $this->postJson('/api/v1/tasks', [
            'name'=>''
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    // PUT /tasks/{id} -> update a task based on ID
    public function test_success_update_a_task():void {
        $task = Task::factory()->create();

        $response = $this->putJson('/api/v1/tasks/'.$task->id, [
            'name' => "Updated Task"
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => "Updated Task"
        ]);
    }

    public function test_failed_update_a_task():void {
        $task = Task::factory()->create();

        $response = $this->putJson('/api/v1/tasks/'.$task->id, [
            'name' => ""
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    // PATCH /tasks/{id}/complete -> mark task as completed or incomplete
    public function test_success_toggle_task_completion():void {
        $task = Task::factory()->create([
            'is_completed' => false
        ]);

        $response = $this->patchJson('/api/v1/tasks/'.$task->id.'/complete', [
            'is_completed' => true
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'is_completed' => true
        ]);
    }

    public function test_failed_toggle_task_completion_with_invalid_data():void {
        $task = Task::factory()->create();

        $response = $this->patchJson('/api/v1/tasks/'.$task->id.'/complete', [
            'is_completed' => "yes"
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['is_completed']);
    }

    // DELETE /tasks/ -> delete a task
    public function test_success_delete_a_task():void {
        $task = Task::factory()->create();

        $response = $this->deleteJson('/api/v1/tasks/'.$task->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('tasks', [
            'id'=> $task->id
        ]);
    }

    public function test_failed_delete_a_task():void {
        $randomId = (Task::max('id') ?? 0) + 1;

        $response = $this->deleteJson('/api/v1/tasks/'.$randomId);

        $response->assertNotFound();
    }
}
