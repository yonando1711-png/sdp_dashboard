<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'branch',
        'role',
        'menu_permissions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'menu_permissions' => 'array',
        ];
    }

    /**
     * Check if user is IT Admin
     */
    public function isItAdmin(): bool
    {
        return $this->role === 'it_admin';
    }

    /**
     * Check if user is Jakarta branch or IT Admin (nationwide view)
     */
    public function isNationwide(): bool
    {
        return $this->isItAdmin() || strtoupper((string) $this->branch) === 'ALL' || strtoupper((string) $this->branch) === 'JKT' || strtoupper((string) $this->branch) === 'JAKARTA';
    }

    /**
     * Check if user has permission for a specific menu
     */
    public function hasMenuPermission(string $menu): bool
    {
        if ($this->isItAdmin()) {
            return true;
        }

        // Standardize key hyphens/underscores
        $key = str_replace('_', '-', strtolower($menu));

        // Non-Jakarta/Non-Nationwide branches can NEVER access LoR or CRM
        if (!$this->isNationwide() && in_array($key, ['lor', 'crm'])) {
            return false;
        }

        // If custom menu_permissions array exists for this account, strictly enforce it!
        if (is_array($this->menu_permissions)) {
            return in_array($menu, $this->menu_permissions)
                || in_array($key, $this->menu_permissions)
                || ($key === 'inventory' && (in_array('in-stock', $this->menu_permissions) || in_array('active-rentals', $this->menu_permissions) || in_array('in-service', $this->menu_permissions)))
                || ($key === 'active-rentals' && (in_array('active-rental', $this->menu_permissions) || in_array('active-rentals', $this->menu_permissions)))
                || ($key === 'in-stock' && (in_array('in_stock', $this->menu_permissions) || in_array('in-stock', $this->menu_permissions)))
                || ($key === 'in-service' && (in_array('in_service', $this->menu_permissions) || in_array('in-service', $this->menu_permissions)));
        }

        // Default fallbacks when menu_permissions is null
        if ($this->isNationwide()) {
            return in_array($key, ['dashboard', 'total-stock', 'rental-pairs', 'in-stock', 'active-rentals', 'in-service', 'inventory', 'details', 'lor', 'crm']);
        }

        return in_array($key, ['dashboard', 'total-stock', 'rental-pairs', 'in-stock', 'active-rentals', 'in-service', 'inventory', 'details']);
    }

    /**
     * Get array of matching warehouse names for this user's branch query scope
     */
    public function getBranchWarehouses(?string $branchCode = null): ?array
    {
        $branch = strtoupper(trim((string) ($branchCode ?? $this->branch)));

        if ($branch === 'ALL') {
            return null; // Null means no filtering (all nationwide data)
        }

        $map = [
            'JKT' => ['JAKARTA'],
            'JAKARTA' => ['JAKARTA'],
            'SUB' => ['SURABAYA'],
            'SURABAYA' => ['SURABAYA'],
            'SMG' => ['SEMARANG'],
            'SEMARANG' => ['SEMARANG'],
            'DPS' => ['DENPASAR', 'BALI'],
            'BALI' => ['DENPASAR', 'BALI'],
            'DENPASAR' => ['DENPASAR', 'BALI'],
            'BDG' => ['BANDUNG'],
            'BANDUNG' => ['BANDUNG'],
            'CLG' => ['CILEGON'],
            'CILEGON' => ['CILEGON'],
            'CRB' => ['CIREBON'],
            'CIREBON' => ['CIREBON'],
            'MKS' => ['MAKASAR'],
            'MAKASAR' => ['MAKASAR'],
            'PLM' => ['PALEMBANG'],
            'PALEMBANG' => ['PALEMBANG'],
            'MDN' => ['MEDAN'],
            'MEDAN' => ['MEDAN'],
        ];

        return $map[$branch] ?? [$branch];
    }

    /**
     * Get physical location prefixes for a branch (e.g. SDSUB for Surabaya, SDJKT for Jakarta)
     */
    public function getBranchLocationPrefixes(?string $branchCode = null): ?array
    {
        $branch = strtoupper(trim((string) ($branchCode ?? $this->branch)));

        if ($branch === 'ALL') {
            return null;
        }

        $prefixMap = [
            'JKT' => ['SDJKT', 'JAKARTA'],
            'JAKARTA' => ['SDJKT', 'JAKARTA'],
            'SUB' => ['SDSUB', 'SURABAYA'],
            'SURABAYA' => ['SDSUB', 'SURABAYA'],
            'SMG' => ['SDSMG', 'SEMARANG'],
            'SEMARANG' => ['SDSMG', 'SEMARANG'],
            'DPS' => ['SDDPS', 'BALI', 'DENPASAR'],
            'BALI' => ['SDDPS', 'BALI', 'DENPASAR'],
            'DENPASAR' => ['SDDPS', 'BALI', 'DENPASAR'],
            'BDG' => ['SDBDG', 'BANDUNG'],
            'BANDUNG' => ['SDBDG', 'BANDUNG'],
            'CLG' => ['SDCLG', 'CILEGON'],
            'CILEGON' => ['SDCLG', 'CILEGON'],
            'CRB' => ['SDCRB', 'CIREBON'],
            'CIREBON' => ['SDCRB', 'CIREBON'],
            'MKS' => ['SDMKS', 'MAKASAR'],
            'MAKASAR' => ['SDMKS', 'MAKASAR'],
            'PLM' => ['SDPLM', 'PALEMBANG'],
            'PALEMBANG' => ['SDPLM', 'PALEMBANG'],
            'MDN' => ['SDMDN', 'MEDAN'],
            'MEDAN' => ['SDMDN', 'MEDAN'],
            'LMP' => ['SDLMP', 'LAMPUNG'],
            'LAMPUNG' => ['SDLMP', 'LAMPUNG'],
            'PKN' => ['SDPKN', 'PEKANBARU'],
            'PEKANBARU' => ['SDPKN', 'PEKANBARU'],
            'YOG' => ['SDYOG', 'YOGYAKARTA'],
            'YOGYAKARTA' => ['SDYOG', 'YOGYAKARTA'],
            'PML' => ['SDPML', 'POMALA'],
            'POMALA' => ['SDPML', 'POMALA'],
        ];

        return $prefixMap[$branch] ?? [$branch];
    }
}
