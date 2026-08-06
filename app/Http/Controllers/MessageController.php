<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Display a listing of messages.
     */
    public function index()
    {
        $user = Auth::user();
        
        $inbox = Message::with('sender')->where('receiver_id', $user->id)->orderBy('created_at', 'desc')->paginate(15);
        $sent = Message::with('receiver')->where('sender_id', $user->id)->orderBy('created_at', 'desc')->paginate(15);

        // Determine the view based on role
        if ($user->role === 'parent') {
            return view('parent.messages.index', compact('inbox', 'sent'));
        } elseif ($user->role === 'teacher') {
            return view('teacher.messages.index', compact('inbox', 'sent'));
        }
        
        abort(403, 'Unauthorized role.');
    }

    /**
     * Show the form for creating a new message.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $teachers = collect();

        if ($user->role === 'parent') {
            $childIds = $user->children()->pluck('users.id');
            $teachers = User::where('role', 'teacher')
                ->whereHas('subjects', function($q) use ($childIds) {
                    $q->whereHas('enrolledStudents', function($q2) use ($childIds) {
                        $q2->whereIn('users.id', $childIds);
                    });
                })
                ->get();
        } elseif ($user->role === 'teacher') {
            // Get parents of students enrolled in the teacher's subjects
            $parents = User::where('role', 'parent')
                ->whereHas('children', function($q) use ($user) {
                    $q->whereHas('enrolledSubjects', function($q2) use ($user) {
                        $q2->where('instructor_id', $user->id);
                    });
                })
                ->get();
        }

        $replyTo = null;
        if ($request->has('reply_to')) {
            $replyTo = User::find($request->reply_to);
        }

        if ($user->role === 'parent') {
            return view('parent.messages.create', compact('teachers', 'replyTo'));
        } elseif ($user->role === 'teacher') {
            return view('teacher.messages.create', compact('parents', 'replyTo'));
        }

        abort(403);
    }

    /**
     * Store a newly created message in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string|max:2000',
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'subject' => $request->subject,
            'body' => $request->body,
        ]);

        $role = Auth::user()->role;
        return redirect()->route($role . '.messages.index')->with('success', 'Message sent successfully.');
    }

    /**
     * Display the specified message.
     */
    public function show(Message $message)
    {
        $user = Auth::user();

        // Ensure user is sender or receiver
        if ($message->sender_id !== $user->id && $message->receiver_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        // Mark as read if receiver
        if ($message->receiver_id === $user->id && is_null($message->read_at)) {
            $message->update(['read_at' => now()]);
        }

        if ($user->role === 'parent') {
            return view('parent.messages.show', compact('message'));
        } elseif ($user->role === 'teacher') {
            return view('teacher.messages.show', compact('message'));
        }

        abort(403);
    }
}
