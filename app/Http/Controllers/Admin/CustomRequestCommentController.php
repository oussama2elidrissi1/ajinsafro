<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomRequest;
use App\Services\CustomRequestNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomRequestCommentController extends Controller
{
    public function __construct(private readonly CustomRequestNotificationService $notifications) {}

    public function store(Request $request, CustomRequest $customRequest): RedirectResponse
    {
        abort_unless($request->user()?->can('custom_requests.view'), 403);

        $data = $request->validate([
            'comment_type' => ['required', Rule::in(['internal', 'agent_message', 'offline_message', 'missing_info'])],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $customRequest->comments()->create([
            'user_id' => $request->user()->id,
            'comment_type' => $data['comment_type'],
            'message' => $data['message'],
        ]);

        if ($data['comment_type'] === 'missing_info') {
            $customRequest->changeStatus(CustomRequest::STATUS_MISSING_INFO, $request->user()->id, $data['message']);
            $this->notifications->notifyMissingInfo($customRequest->fresh('creator'));
        }

        return back()->with('success', 'Commentaire ajouté.');
    }
}
