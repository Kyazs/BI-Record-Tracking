<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Applicant extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name', 'age', 'address', 'date_of_birth', 'birth_place', 'school', 'status',
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
