<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_user_can_view_categories(): void
    {
        $user = \App\Models\User::factory()->create();
        $category = \App\Models\Category::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/categories');

        $response->assertStatus(200);
        $response->assertSee($category->name);
    }

    public function test_user_can_create_category(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->post('/categories', [
            'name' => 'Work',
            'color' => '#ff0000',
        ]);

        $response->assertRedirect('/categories');
        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Work',
            'color' => '#ff0000',
        ]);
    }

    public function test_user_cannot_view_others_category(): void
    {
        $user = \App\Models\User::factory()->create();
        $otherUser = \App\Models\User::factory()->create();
        $category = \App\Models\Category::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get("/categories/{$category->id}/edit");

        $response->assertStatus(403);
    }
}
