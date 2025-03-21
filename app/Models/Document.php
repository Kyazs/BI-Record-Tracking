<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'barangay_cert_path',
        'pnp_clearance_path',
        'rtc_clearance_path',
        'school_cert_path',
        'derogatory_records_path',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
}
