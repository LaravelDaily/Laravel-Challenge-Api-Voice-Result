<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVoiceRequest;
use App\Models\Question;
use App\Models\Voice;

class VoiceController extends Controller
{
    public function voice(StoreVoiceRequest $request)
    {
        $question = Question::find($request->post('question_id'));
        abort_if(
            $question->user_id == auth()->id(),
            500,
            'The user is not allowed to vote to your question'
        );

        //check if user voted
        $voice = Voice::firstOrCreate([
            'user_id' => auth()->id(),
            'question_id' => $request->post('question_id')
        ], [
            'value' => $request->post('value')
        ]);

        if ($voice->wasRecentlyCreated) {
            return [
                'message' => 'Voting completed successfully'
            ];
        }

        if ($voice->value === $request->post('value')) {
            abort(500, 'The user is not allowed to vote more than once');
        } else {
            $voice->update([
                'value' => $request->post('value')
            ]);
            return response()->json([
                'message' => 'update your voice'
            ], 201);
        }
    }
}
