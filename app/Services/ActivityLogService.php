<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;

/**
 * Service to log all important actions to activity_logs table for dashboard display.
 * 
 * According to specification:
 * - Log all important actions to activity_logs table for dashboard display
 */
class ActivityLogService
{
    /**
     * Log an activity.
     * 
     * @param string $type Activity type: donation, delivery, mosque, user, campaign, other
     * @param string $messageAr Arabic message
     * @param string $messageEn English message
     * @param User|null $user User who performed the action
     * @param int|null $relatedId ID of related entity
     * @param string|null $relatedType Model name of related entity
     * @param array|null $metadata Additional metadata
     * @return ActivityLog
     */
    public function log(
        string $type,
        string $messageAr,
        string $messageEn,
        ?User $user = null,
        ?int $relatedId = null,
        ?string $relatedType = null,
        ?array $metadata = null
    ): ActivityLog {
        return ActivityLog::create([
            'type' => $type,
            'user_id' => $user?->id,
            'related_id' => $relatedId,
            'related_type' => $relatedType,
            'message_ar' => $messageAr,
            'message_en' => $messageEn,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'created_at' => now(),
        ]);
    }

    /**
     * Log donation activity.
     */
    public function logDonation(string $messageAr, string $messageEn, ?User $user = null, ?int $donationId = null, ?array $metadata = null): ActivityLog
    {
        return $this->log('donation', $messageAr, $messageEn, $user, $donationId, 'Donation', $metadata);
    }

    /**
     * Log delivery activity.
     */
    public function logDelivery(string $messageAr, string $messageEn, ?User $user = null, ?int $deliveryId = null, ?array $metadata = null): ActivityLog
    {
        return $this->log('delivery', $messageAr, $messageEn, $user, $deliveryId, 'Delivery', $metadata);
    }

    /**
     * Log mosque activity.
     */
    public function logMosque(string $messageAr, string $messageEn, ?User $user = null, ?int $mosqueId = null, ?array $metadata = null): ActivityLog
    {
        return $this->log('mosque', $messageAr, $messageEn, $user, $mosqueId, 'Mosque', $metadata);
    }

    /**
     * Log user activity.
     */
    public function logUser(string $messageAr, string $messageEn, ?User $user = null, ?int $userId = null, ?array $metadata = null): ActivityLog
    {
        return $this->log('user', $messageAr, $messageEn, $user, $userId, 'User', $metadata);
    }

    /**
     * Log campaign activity.
     */
    public function logCampaign(string $messageAr, string $messageEn, ?User $user = null, ?int $campaignId = null, ?array $metadata = null): ActivityLog
    {
        return $this->log('campaign', $messageAr, $messageEn, $user, $campaignId, 'Campaign', $metadata);
    }

    /**
     * Log other activity.
     */
    public function logOther(string $messageAr, string $messageEn, ?User $user = null, ?int $relatedId = null, ?string $relatedType = null, ?array $metadata = null): ActivityLog
    {
        return $this->log('other', $messageAr, $messageEn, $user, $relatedId, $relatedType, $metadata);
    }
}

