<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProjectsManager\Meeting;

class MeetingPlatformTest extends TestCase
{
    public function test_platform_options_defines_all_required_providers(): void
    {
        $options = Meeting::platformOptions();

        $this->assertArrayHasKey(Meeting::PLATFORM_GOOGLE_MEET, $options);
        $this->assertArrayHasKey(Meeting::PLATFORM_ZOOM, $options);
        $this->assertArrayHasKey(Meeting::PLATFORM_TEAMS, $options);
        $this->assertArrayHasKey(Meeting::PLATFORM_TASKPILOT, $options);
        $this->assertArrayHasKey(Meeting::PLATFORM_OFFLINE, $options);
        $this->assertArrayHasKey(Meeting::PLATFORM_OTHER, $options);

        $this->assertEquals('Google Meet', $options[Meeting::PLATFORM_GOOGLE_MEET]['label']);
        $this->assertEquals('Zoom', $options[Meeting::PLATFORM_ZOOM]['label']);
        $this->assertEquals('Microsoft Teams', $options[Meeting::PLATFORM_TEAMS]['label']);
    }

    public function test_google_meet_platform_helpers(): void
    {
        $meeting = new Meeting([
            'platform' => Meeting::PLATFORM_GOOGLE_MEET,
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
        ]);

        $this->assertEquals('Google Meet', $meeting->platform_label);
        $this->assertTrue($meeting->isExternalOnline());
        $this->assertFalse($meeting->isBuiltin());
        $this->assertFalse($meeting->isOffline());
        $this->assertEquals('https://meet.google.com/abc-defg-hij', $meeting->effective_join_url);
    }

    public function test_zoom_platform_helpers(): void
    {
        $meeting = new Meeting([
            'platform' => Meeting::PLATFORM_ZOOM,
            'meeting_link' => 'https://zoom.us/j/1234567890',
            'passcode' => '123456',
        ]);

        $this->assertEquals('Zoom', $meeting->platform_label);
        $this->assertTrue($meeting->isExternalOnline());
        $this->assertFalse($meeting->isBuiltin());
        $this->assertFalse($meeting->isOffline());
        $this->assertEquals('https://zoom.us/j/1234567890', $meeting->effective_join_url);
        $this->assertEquals('123456', $meeting->passcode);
    }

    public function test_offline_platform_helpers(): void
    {
        $meeting = new Meeting([
            'platform' => Meeting::PLATFORM_OFFLINE,
            'location' => 'Phòng họp Lotus - Tầng 3',
        ]);

        $this->assertEquals('Trực tiếp (Offline)', $meeting->platform_label);
        $this->assertTrue($meeting->isOffline());
        $this->assertFalse($meeting->isExternalOnline());
        $this->assertFalse($meeting->isBuiltin());
        $this->assertEquals('Phòng họp Lotus - Tầng 3', $meeting->location);
    }

    public function test_default_taskpilot_platform_helpers(): void
    {
        $meeting = new Meeting();

        $this->assertEquals('TaskPilot Meet', $meeting->platform_label);
        $this->assertTrue($meeting->isBuiltin());
        $this->assertFalse($meeting->isOffline());
        $this->assertFalse($meeting->isExternalOnline());
    }
}

