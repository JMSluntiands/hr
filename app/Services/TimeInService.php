<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class TimeInService
{
    public function ensureTable(): void
    {
        if (Schema::hasTable('time_in_notifications')) {
            return;
        }

        DB::statement('CREATE TABLE IF NOT EXISTS time_in_notifications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            time_in_date DATE NOT NULL,
            time_in_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_date (user_id, time_in_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    public function hasTimeInToday(int $userId, string $date): bool
    {
        return DB::table('time_in_notifications')
            ->where('user_id', $userId)
            ->where('time_in_date', $date)
            ->exists();
    }

    public function record(int $userId, string $date, string $dateTime): bool
    {
        return DB::table('time_in_notifications')->insert([
            'user_id' => $userId,
            'time_in_date' => $date,
            'time_in_at' => $dateTime,
        ]);
    }

    public function manilaNow(): Carbon
    {
        return Carbon::now('Asia/Manila');
    }

    public function notifySlack(string $name): void
    {
        $configPath = config('hr.slack_config');
        if (! is_file($configPath)) {
            return;
        }

        require $configPath;

        $webhook = defined('SLACK_TIMEIN_WEBHOOK_URL') ? (string) SLACK_TIMEIN_WEBHOOK_URL : '';
        if ($webhook === '') {
            return;
        }

        $now = $this->manilaNow();
        $safeName = trim($name) !== '' ? trim($name) : 'Unknown';

        try {
            Http::timeout(3)->post($webhook, [
                'text' => "*Time In Alert*\nName: {$safeName}\nDate: {$now->format('Y-m-d')}\nTime In: {$now->format('h:i A')}",
            ]);
        } catch (\Throwable) {
            // non-blocking
        }
    }
}
