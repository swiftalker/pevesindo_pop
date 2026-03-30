<?php

namespace App\Models\Project;

use App\Models\Employee;
use App\Models\Project;
use Carbon\Carbon;
use Database\Factories\SurveyReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $project_id
 * @property int $surveyor_id
 * @property string $findings
 * @property string $measurement_data
 * @property string $recommended_products
 * @property string $photos
 * @property Carbon $submitted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SurveyReport extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'center_app_ref',
        'project_id',
        'surveyor_id',
        'findings',
        'measurement_data',
        'recommended_products',
        'photos',
        'submitted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'project_id' => 'integer',
            'surveyor_id' => 'integer',
            'measurement_data' => 'array',
            'recommended_products' => 'array',
            'photos' => 'array',
            'submitted_at' => 'timestamp',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function surveyor(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): SurveyReportFactory
    {
        return SurveyReportFactory::new();
    }
}
