<?php

namespace ProjectsManager;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $fillable = [
        'project_id',
        'team_id',
        'created_by',
        'title',
        'description',
        'shared_notes',
        'room_name',
        'platform',
        'meeting_link',
        'location',
        'passcode',
        'status',
        'starts_at',
        'ended_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public const PLATFORM_TASKPILOT = 'taskpilot';
    public const PLATFORM_GOOGLE_MEET = 'google_meet';
    public const PLATFORM_ZOOM = 'zoom';
    public const PLATFORM_TEAMS = 'teams';
    public const PLATFORM_OFFLINE = 'offline';
    public const PLATFORM_OTHER = 'other';

    public static function platformOptions(): array
    {
        return [
            self::PLATFORM_GOOGLE_MEET => [
                'label' => 'Google Meet',
                'icon' => 'fa fa-video-camera',
                'color' => '#0f9d58',
                'bg' => '#e6f4ea',
                'badge' => 'badge-success',
                'type' => 'online',
            ],
            self::PLATFORM_ZOOM => [
                'label' => 'Zoom',
                'icon' => 'fa fa-video-camera',
                'color' => '#2d8cff',
                'bg' => '#e8f2ff',
                'badge' => 'badge-primary',
                'type' => 'online',
            ],
            self::PLATFORM_TEAMS => [
                'label' => 'Microsoft Teams',
                'icon' => 'fa fa-users',
                'color' => '#5059c9',
                'bg' => '#eeeffc',
                'badge' => 'badge-info',
                'type' => 'online',
            ],
            self::PLATFORM_TASKPILOT => [
                'label' => 'TaskPilot Meet',
                'icon' => 'fa fa-bolt',
                'color' => '#f59e0b',
                'bg' => '#fef3c7',
                'badge' => 'badge-warning',
                'type' => 'builtin',
            ],
            self::PLATFORM_OFFLINE => [
                'label' => 'Trực tiếp (Offline)',
                'icon' => 'fa fa-map-marker',
                'color' => '#ea4335',
                'bg' => '#fce8e6',
                'badge' => 'badge-danger',
                'type' => 'offline',
            ],
            self::PLATFORM_OTHER => [
                'label' => 'Nền tảng khác / Link ngoài',
                'icon' => 'fa fa-link',
                'color' => '#64748b',
                'bg' => '#f1f5f9',
                'badge' => 'badge-secondary',
                'type' => 'online',
            ],
        ];
    }

    public function getPlatformInfoAttribute(): array
    {
        $platform = $this->platform ?? self::PLATFORM_TASKPILOT;
        $options = self::platformOptions();
        return $options[$platform] ?? $options[self::PLATFORM_TASKPILOT];
    }

    public function getPlatformLabelAttribute(): string
    {
        return $this->platform_info['label'];
    }

    public function isBuiltin(): bool
    {
        return ($this->platform ?? self::PLATFORM_TASKPILOT) === self::PLATFORM_TASKPILOT;
    }

    public function isOffline(): bool
    {
        return ($this->platform ?? self::PLATFORM_TASKPILOT) === self::PLATFORM_OFFLINE;
    }

    public function isExternalOnline(): bool
    {
        return in_array($this->platform, [
            self::PLATFORM_GOOGLE_MEET,
            self::PLATFORM_ZOOM,
            self::PLATFORM_TEAMS,
            self::PLATFORM_OTHER,
        ]);
    }

    public function getEffectiveJoinUrlAttribute(): ?string
    {
        if ($this->isExternalOnline()) {
            return $this->meeting_link;
        }
        return route('meetings.show', $this);
    }

    public function project() { return $this->belongsTo(Project::class); }
    public function team() { return $this->belongsTo(Team::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function members() { return $this->belongsToMany(User::class, 'meeting_members')->withPivot('role', 'permissions')->withTimestamps(); }
    public function messages() { return $this->hasMany(MeetingMessage::class); }
    public function note() { return $this->hasOne(MeetingNote::class); }
    public function minutes() { return $this->hasOne(MeetingMinute::class); }
    public function recordings() { return $this->hasMany(MeetingRecording::class); }
    public function participantLogs() { return $this->hasMany(MeetingParticipantLog::class); }
    public function documents() { return $this->hasMany(MeetingDocument::class); }
    public function breakoutRooms() { return $this->hasMany(MeetingBreakoutRoom::class); }
}