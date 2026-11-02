<?php

namespace ProjectsManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskLink extends Model
{
    protected $fillable = [
        'source_task_id',
        'target_task_id',
        'link_type',
        'created_by',
    ];

    public static array $linkTypes = [
        'blocks' => [
            'label' => 'Chặn (Blocks)',
            'badge_class' => 'bg-danger text-white',
            'icon' => 'fa-ban',
            'inverse' => 'is_blocked_by',
            'inverse_label' => 'Bị chặn bởi (Is blocked by)',
        ],
        'is_blocked_by' => [
            'label' => 'Bị chặn bởi (Is blocked by)',
            'badge_class' => 'bg-warning text-dark',
            'icon' => 'fa-exclamation-triangle',
            'inverse' => 'blocks',
            'inverse_label' => 'Chặn (Blocks)',
        ],
        'relates_to' => [
            'label' => 'Liên quan tới (Relates to)',
            'badge_class' => 'bg-info text-white',
            'icon' => 'fa-link',
            'inverse' => 'relates_to',
            'inverse_label' => 'Liên quan tới (Relates to)',
        ],
        'duplicates' => [
            'label' => 'Trùng lặp với (Duplicates)',
            'badge_class' => 'bg-secondary text-white',
            'icon' => 'fa-clone',
            'inverse' => 'is_duplicated_by',
            'inverse_label' => 'Bị trùng lặp bởi (Is duplicated by)',
        ],
        'is_duplicated_by' => [
            'label' => 'Bị trùng lặp bởi (Is duplicated by)',
            'badge_class' => 'bg-secondary text-white',
            'icon' => 'fa-clone',
            'inverse' => 'duplicates',
            'inverse_label' => 'Trùng lặp với (Duplicates)',
        ],
        'causes' => [
            'label' => 'Gây ra (Causes)',
            'badge_class' => 'bg-danger text-white',
            'icon' => 'fa-bug',
            'inverse' => 'is_caused_by',
            'inverse_label' => 'Gây ra bởi (Is caused by)',
        ],
        'is_caused_by' => [
            'label' => 'Gây ra bởi (Is caused by)',
            'badge_class' => 'bg-warning text-dark',
            'icon' => 'fa-bug',
            'inverse' => 'causes',
            'inverse_label' => 'Gây ra (Causes)',
        ],
    ];

    public function sourceTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'source_task_id');
    }

    public function targetTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'target_task_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Create bidirectional link between two tasks
     */
    public static function createBidirectional(int $sourceId, int $targetId, string $linkType, ?int $userId = null): void
    {
        if ($sourceId === $targetId) {
            return;
        }

        $typeMeta = self::$linkTypes[$linkType] ?? self::$linkTypes['relates_to'];
        $inverseType = $typeMeta['inverse'];

        // Source -> Target
        self::updateOrCreate(
            ['source_task_id' => $sourceId, 'target_task_id' => $targetId, 'link_type' => $linkType],
            ['created_by' => $userId]
        );

        // Target -> Source (Inverse)
        self::updateOrCreate(
            ['source_task_id' => $targetId, 'target_task_id' => $sourceId, 'link_type' => $inverseType],
            ['created_by' => $userId]
        );
    }

    /**
     * Remove bidirectional link
     */
    public function removeBidirectional(): void
    {
        $typeMeta = self::$linkTypes[$this->link_type] ?? null;
        $inverseType = $typeMeta['inverse'] ?? null;

        if ($inverseType) {
            self::where('source_task_id', $this->target_task_id)
                ->where('target_task_id', $this->source_task_id)
                ->where('link_type', $inverseType)
                ->delete();
        }

        $this->delete();
    }
}

