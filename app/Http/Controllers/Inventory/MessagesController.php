<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryMessagesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessagesController extends Controller
{
    public function __construct(
        private InventoryMessagesService $messages,
    ) {}

    public function index(Request $request): View
    {
        return view('inventory.messages.index', [
            'tableReady' => $this->messages->ensureReady(),
            'messages' => $this->messages->listMessages(),
            'unreadCount' => $this->messages->unreadCount(),
            'status' => (string) $request->query('status', ''),
        ]);
    }

    public function markRead(Request $request): RedirectResponse
    {
        $this->messages->markRead((int) $request->input('allocation_id', 0));

        return redirect()->route('inventory.messages.index', ['status' => 'updated']);
    }

    public function markAllRead(): RedirectResponse
    {
        $this->messages->markAllRead();

        return redirect()->route('inventory.messages.index', ['status' => 'updated']);
    }
}
