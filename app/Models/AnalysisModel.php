<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class AnalysisModel extends Model
{
    protected $table = 'analysis_results';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'symbol',
        'analyzed_at',
        'recommendation',
        'confidence',
        'payload_json',
    ];

    protected $useTimestamps = false;
}
