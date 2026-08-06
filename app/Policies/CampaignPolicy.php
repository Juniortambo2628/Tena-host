<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CampaignPolicy
{
    use HandlesAuthorization;

    public function manage(User $user, Campaign $campaign): bool
    {
        return $campaign->user_id === $user->id;
    }
}
