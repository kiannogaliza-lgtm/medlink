<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ChildVaccination extends Model
{
    protected $fillable = [
        'user_id',
        'child_name',
        'child_dob',
        'child_gender',
        'age_category',
        'vaccine_name',
        'dose_number',
        'date_administered',
        'administered_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'child_dob' => 'date',
        'date_administered' => 'date',
    ];

    /**
     * Parent or guardian resident user.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Compute child age label (e.g., "3 months old (Infant)", "25 yrs old (Adult)").
     */
    public function getAgeCategoryLabelAttribute(): string
    {
        if ($this->age_category) {
            if ($this->child_dob) {
                $dob = Carbon::parse($this->child_dob)->startOfDay();
                $today = Carbon::today();
                $diffDays = (int) $dob->diffInDays($today);
                $diffMonths = (int) $dob->diffInMonths($today);
                $diffYears = (int) $dob->diffInYears($today);

                if ($diffDays <= 42) {
                    $dayLabel = $diffDays === 1 ? '1 day' : "{$diffDays} days";
                    return "{$dayLabel} old ({$this->age_category})";
                } elseif ($diffMonths < 12) {
                    $moLabel = $diffMonths === 1 ? '1 mo' : "{$diffMonths} mos";
                    return "{$moLabel} old ({$this->age_category})";
                } elseif ($diffYears < 2) {
                    return "{$diffMonths} mos old ({$this->age_category})";
                } else {
                    $yrLabel = $diffYears === 1 ? '1 yr' : "{$diffYears} yrs";
                    return "{$yrLabel} old ({$this->age_category})";
                }
            }
            return $this->age_category;
        }

        if (!$this->child_dob) return 'Age unknown';

        $dob = Carbon::parse($this->child_dob)->startOfDay();
        $today = Carbon::today();

        $diffDays = (int) $dob->diffInDays($today);
        $diffMonths = (int) $dob->diffInMonths($today);
        $diffYears = (int) $dob->diffInYears($today);

        if ($diffDays <= 28) {
            $dayLabel = $diffDays === 1 ? '1 day' : "{$diffDays} days";
            return "{$dayLabel} old (Newborn)";
        } elseif ($diffMonths < 12) {
            $moLabel = $diffMonths === 1 ? '1 mo' : "{$diffMonths} mos";
            return "{$moLabel} old (Infant)";
        } elseif ($diffYears < 2) {
            return "{$diffMonths} mos old (Young child)";
        } elseif ($diffYears <= 4) {
            return "{$diffYears} yrs old (Preschool child)";
        } elseif ($diffYears <= 9) {
            return "{$diffYears} yrs old (School-age child)";
        } elseif ($diffYears <= 19) {
            return "{$diffYears} yrs old (Adolescent)";
        } elseif ($diffYears <= 59) {
            return "{$diffYears} yrs old (Adult)";
        } else {
            return "{$diffYears} yrs old (Senior citizen)";
        }
    }
}

