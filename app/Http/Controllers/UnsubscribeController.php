<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscriber;


class UnsubscribeController extends Controller
{
    public function unsubscribe(string $token)
    {
        $subscriber = Subscriber::where('unsubscribe_token', $token)->firstOrFail();

        if (! $subscriber->is_unsubscribed) {
            $subscriber->update([
                'status' => Subscriber::STATUS_UNSUBSCRIBED,
                'unsubscribed_at' => now(),
            ]);
        }

        return view('unsubscribe.success');
    }
}
