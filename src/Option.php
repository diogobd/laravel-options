<?php

namespace Appstract\Options;

use Illuminate\Database\Eloquent\Model;
use SolutoSoft\MultiTenant\Tenant;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class Option extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var [type]
     */
    protected $fillable = [
        'key',
        'value',
        'tenant_id'
    ];

    /**
     * Returns value of property existing in identity object
     * @return integer
     */
    protected function getTenantId()
    {
        //$user = app('auth')->user();
        $user = Auth::user();

        if ($user instanceof Tenant) {
            return $user->getTenantId();
        } else {
            throw new RuntimeException("Current user must implement Tenant interface");
        }
    }

    /**
     * Returns query
     * @param  string  $key
     * @param  mixed   $tenantId
     * @return integer
     */
    protected function getQuery($key, $tenantId)
    {
        if ($this->getIsMultiTenant() && $tenantId === null) {
            $tenantId = $this->getTenantId();
        }

        $query = self::where('key', $key);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        return $query;
    }

    /**
     * Whether multi-tenant control is enabled
     * @return bool
     */
    public function getIsMultiTenant()
    {
        return !Auth::guest();
    }

    /**
     * Determine if the given option value exists.
     *
     * @param  string  $key
     * @param  mixed   $tenantId
     * @return bool
     */
    public function exists($key, $tenantId = null)
    {
        return $this->getQuery($key, $tenantId)->exists();
    }

    /**
     * Get the specified option value.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @param  mixed   $tenantId
     * @return mixed
     */
    public function get($key, $default = null, $tenantId = null)
    {
        if ($option = $this->getQuery($key, $tenantId)->first()) {
            return $option->value;
        }

        return $default;
    }

    /**
     * Set a given option value.
     *
     * @param  array|string  $key
     * @param  mixed   $value
     * @param  mixed   $tenantId
     * @return void
     */
    public function set($key, $value = null, $tenantId = null)
    {
        if ($this->getIsMultiTenant() && $tenantId === null) {
            $tenantId = $this->getTenantId();
        }

        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            ($tenantId) ?
                self::updateOrCreate(['key' => $key, 'tenant_id' => $tenantId], ['value' => $value, 'tenant_id' => $tenantId]) :
                self::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    /**
     * Remove/delete the specified option value.
     *
     * @param  string  $key
     * @return bool
     */
    public function remove($key, $tenantId = null)
    {
        return (bool) $this->getQuery($key, $tenantId)->delete();
    }
}
