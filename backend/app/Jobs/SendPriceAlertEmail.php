<?php

namespace App\Jobs;

use App\Models\PriceAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPriceAlertEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public PriceAlert $priceAlert
    ) {}

    public function handle(): void
    {
        $alert = $this->priceAlert->load(['user', 'item.identifiers', 'item.contributors', 'priceCheck']);
        
        $user = $alert->user;
        $item = $alert->item;
        $priceCheck = $alert->priceCheck;

        // Basic email data
        $emailData = [
            'user_name' => $user->name,
            'item_title' => $item->title,
            'item_type' => $item->type,
            'target_price' => $alert->target_price,
            'current_price' => $alert->triggered_price,
            'savings' => $alert->savings,
            'savings_percentage' => round($alert->savings_percentage, 1),
            'source' => $alert->source,
            'source_url' => $priceCheck->source_url,
            'availability' => $priceCheck->availability,
        ];

        // Add item identifiers
        if ($item->identifiers->isNotEmpty()) {
            $isbn = $item->identifiers->where('type', 'isbn')->first();
            $emailData['isbn'] = $isbn ? $isbn->value : null;
        }

        // Add contributors
        if ($item->contributors->isNotEmpty()) {
            $authors = $item->contributors->where('role', 'author')->pluck('name')->toArray();
            $emailData['authors'] = implode(', ', $authors);
        }

        try {
            // For now, just log the email content (replace with actual email sending)
            Log::info('Price alert email would be sent:', [
                'to' => $user->email,
                'subject' => "Price Alert: {$item->title} is now available for €{$alert->triggered_price}",
                'data' => $emailData
            ]);

            // Mark alert as notified
            $alert->markAsNotified();

            Log::info("Price alert email sent successfully for alert {$alert->id}");

        } catch (\Exception $e) {
            Log::error("Failed to send price alert email for alert {$alert->id}: " . $e->getMessage());
            
            // Re-throw the exception to trigger job retry
            throw $e;
        }
    }
}