<?php

namespace App\Repositories;

use App\Models\Deal;

class DealRepository
{
    public function create(array $data)
    {
        return Deal::create($data);
    }

    public function getAllForUser($userId, $role)
    {
        $column = ($role === 'influencer') ? 'influencer_id' : 'advertiser_id';
        return Deal::where($column, $userId)->with(['influencer', 'advertiser'])->get();
    }
}
