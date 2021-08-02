<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use App\Models\Voice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_voice_post_is_not_public()
    {
        $response = $this->postJson('api/voices',
            ['question_id' => 1, 'value' => 1]);

        $response->assertStatus(401);
    }

    public function test_can_post_voice_on_someone_elses_question()
    {
        $question_user = User::factory()->create();
        $question = Question::factory()->create(['user_id' => $question_user->id]);

        $voice_user = User::factory()->create();
        $response = $this->actingAs($voice_user, 'api')->postJson('api/voices',
            ['question_id' => $question->id, 'value' => 1]);

        $response->assertStatus(200);
        $response->assertJson([
            'message'=>'Voting completed successfully'
        ]);
        $this->assertDatabaseHas(Voice::class,
            ['user_id' => $voice_user->id, 'question_id' => $question->id, 'value' => 1]);
    }

    public function test_cannot_post_voice_on_nonexisting_question()
    {
        $question_user = User::factory()->create();
        $question = Question::factory()->create(['user_id' => $question_user->id]);

        $voice_user = User::factory()->create();
        $response = $this->actingAs($voice_user, 'api')->postJson('api/voices',
            ['question_id' => $question->id + 1, 'value' => '1']);

        $response->assertStatus(422);
    }

    public function test_cannot_post_voice_on_your_own_question()
    {
        $user = User::factory()->create();
        $question = Question::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'api')->postJson('api/voices',
            ['question_id' => $question->id, 'value' => '1']);

        $response->assertStatus(500);
        $response->assertJson([
            'message' => 'The user is not allowed to vote to your question'
        ]);
    }

    public function test_cannot_post_same_voice_twice()
    {
        $question_user = User::factory()->create();
        $question = Question::factory()->create(['user_id' => $question_user->id]);

        $voice_user = User::factory()->create();
        $this->actingAs($voice_user, 'api')->postJson('api/voices',
            ['question_id' => $question->id, 'value' => '1']);

        $response = $this->actingAs($voice_user, 'api')->postJson('api/voices',
            ['question_id' => $question->id, 'value' => '1', 'aaa' => 'bbb']);

        $response->assertStatus(500);
        $response->assertJson([
            'message' => 'The user is not allowed to vote more than once'
        ]);
    }

    public function test_can_update_your_own_voice()
    {
        $question_user = User::factory()->create();
        $question = Question::factory()->create(['user_id' => $question_user->id]);

        $voice_user = User::factory()->create();
        $this->actingAs($voice_user, 'api')->postJson('api/voices',
            ['question_id' => $question->id, 'value' => '1']);

        $response = $this->actingAs($voice_user, 'api')->postJson('api/voices',
            ['question_id' => $question->id, 'value' => '0']);

        $response->assertStatus(201);
        $response->assertJson([
            'message'=>'update your voice'
        ]);
    }
}
