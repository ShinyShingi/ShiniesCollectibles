<?php

namespace App\Http\Controllers;

use App\Models\PriceAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PriceAlertController extends Controller
{
    public function index(Request $request)
    {
        $query = Auth::user()->priceAlerts()
            ->with(['item.identifiers', 'item.contributors'])
            ->latest('triggered_at');

        if ($request->boolean('unread_only')) {
            $query->unread();
        }

        if ($request->has('days')) {
            $query->recent($request->integer('days', 7));
        }

        $alerts = $query->paginate(20);

        return response()->json($alerts);
    }

    public function markAsRead(PriceAlert $priceAlert)
    {
        if ($priceAlert->user_id !== Auth::id()) {
            abort(403);
        }

        $priceAlert->markAsRead();

        return response()->json(['message' => 'Alert marked as read']);
    }

    public function markAllAsRead()
    {
        Auth::user()->priceAlerts()->unread()->update(['is_read' => true]);

        return response()->json(['message' => 'All alerts marked as read']);
    }

    public function destroy(PriceAlert $priceAlert)
    {
        if ($priceAlert->user_id !== Auth::id()) {
            abort(403);
        }

        $priceAlert->delete();

        return response()->json(['message' => 'Alert deleted']);
    }

    public function statistics()
    {
        $user = Auth::user();
        
        $stats = [
            'total_alerts' => $user->priceAlerts()->count(),
            'unread_alerts' => $user->unreadPriceAlerts()->count(),
            'recent_alerts' => $user->priceAlerts()->recent(7)->count(),
            'total_savings' => $user->priceAlerts()
                ->selectRaw('SUM(target_price - triggered_price) as total')
                ->value('total') ?? 0,
            'average_savings' => $user->priceAlerts()
                ->selectRaw('AVG(target_price - triggered_price) as average')
                ->value('average') ?? 0,
        ];

        return response()->json($stats);
    }
}