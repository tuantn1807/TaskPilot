<?php

namespace ProjectsManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomField extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'field_key',
        'field_type',
        'options',
        'is_required',
        'description',
        'order_index',
        'created_by',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'order_index' => 'integer',
    ];

    public static array $types = [
        'text' => ['label' => 'Văn bản ngắn (Text)', 'icon' => 'fa-font'],
        'number' => ['label' => 'Số nguyên / Thập phân (Number)', 'icon' => 'fa-hashtag'],
        'date' => ['label' => 'Ngày tháng (Date)', 'icon' => 'fa-calendar'],
        'select' => ['label' => 'Danh sách thả xuống (Select Dropdown)', 'icon' => 'fa-list-ul'],
        'checkbox' => ['label' => 'Hộp kiểm Có/Không (Checkbox)', 'icon' => 'fa-check-square-o'],
        'url' => ['label' => 'Đường dẫn liên kết (URL)', 'icon' => 'fa-external-link'],
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(TaskCustomFieldValue::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

