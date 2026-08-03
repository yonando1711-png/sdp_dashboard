<?php

namespace App\Traits;

use App\Models\User;

trait HasBranchScope
{
    /**
     * Scope query to filter records by logged-in user's assigned branch.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param User|null $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUserBranch($query, ?User $user = null)
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return $query;
        }

        $targetBranch = null;

        if ($user->isNationwide()) {
            $hqFilter = session('active_hq_branch');
            if ($hqFilter && $hqFilter !== 'ALL') {
                $targetBranch = $hqFilter;
            } else {
                return $query; // Full nationwide view (no branch restriction)
            }
        } else {
            $targetBranch = $user->branch;
        }

        $warehouses = $user->getBranchWarehouses($targetBranch);
        $prefixes = $user->getBranchLocationPrefixes($targetBranch);

        if ($warehouses && !empty($warehouses)) {
            return $query->where(function($q) use ($warehouses, $prefixes) {
                // Condition 1: Non-in-stock items (Active Rentals, In Service, etc.) matching contract warehouse
                $q->where(function($sub) use ($warehouses) {
                    $sub->where('in_stock', false)
                        ->whereIn('warehouse', $warehouses);
                })
                // Condition 2: In-stock items MUST physically match branch location prefix (e.g. SDSUB/...)
                ->orWhere(function($sub) use ($warehouses, $prefixes) {
                    $sub->where('in_stock', true)
                        ->where(function($locQuery) use ($prefixes) {
                            foreach ($prefixes as $p) {
                                $locQuery->orWhere('location', 'like', $p . '%');
                            }
                        });
                });
            });
        }

        return $query;
    }
}
