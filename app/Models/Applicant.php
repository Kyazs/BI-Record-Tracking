<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Observers\ApplicantObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(ApplicantObserver::class)]
class Applicant extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'age',
        'address',
        'phone',
        'date_of_birth',
        'birth_place',
        'school',
        'status',
        'officer_name',
    ];

    public function documents()
    {
        return $this->hasOne(Document::class);
    }
// return formated data properly for json response
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function toArray()
    {
        $array = parent::toArray();
        $array['created_at'] = $this->created_at->format('Y-m-d H:i:s');
        return $array;
    }
}
