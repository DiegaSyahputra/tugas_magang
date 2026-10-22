<?php

namespace Tests\Integration;

use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Meeting;
use App\Models\MeetingMessage;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveClassIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected User $studentUser;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->teacher()->create();
        $this->studentUser = User::factory()->student()->create();
        $this->student = Student::factory()->create(['user_id' => $this->studentUser->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_teacher_can_schedule_live_meeting(): void
    {
        $course = Course::factory()->create();
        $session = CourseSession::factory()->create(['course_id' => $course->id]);

        $payload = [
            'course_id' => $course->id,
            'course_session_id' => $session->id,
            'title' => 'Webinar Live: Architecture & Clean Code',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'duration_minutes' => 90,
        ];

        $response = $this->actingAs($this->teacher)->post(route('live-class.store'), $payload);

        $meeting = Meeting::where('title', 'Webinar Live: Architecture & Clean Code')->first();
        $this->assertNotNull($meeting);
        $response->assertRedirect(route('live-class.show', $meeting));

        $this->assertDatabaseHas('meetings', [
            'course_id' => $course->id,
            'course_session_id' => $session->id,
            'title' => 'Webinar Live: Architecture & Clean Code',
            'duration_minutes' => 90,
        ]);
        $this->assertNotEmpty($meeting->room_code);
    }

    public function test_teacher_can_start_and_end_live_meeting(): void
    {
        $meeting = Meeting::factory()->create();

        // 1. Start meeting
        $startResponse = $this->actingAs($this->teacher)->post(route('live-class.start', $meeting));
        $startResponse->assertRedirect(route('live-class.show', $meeting));

        $meeting->refresh();
        $this->assertNotNull($meeting->started_at);
        $this->assertEquals('live', $meeting->status);

        // 2. End meeting
        $endResponse = $this->actingAs($this->teacher)->post(route('live-class.end', $meeting));
        $endResponse->assertRedirect(route('live-class.index'));

        $meeting->refresh();
        $this->assertNotNull($meeting->ended_at);
        $this->assertEquals('ended', $meeting->status);
    }

    public function test_video_call_checkin_and_checkout_records_attendance_and_duration(): void
    {
        Carbon::setTestNow('2026-10-22 09:05:00');

        $course = Course::factory()->create();
        $session = CourseSession::factory()->create([
            'course_id' => $course->id,
            'session_date' => '2026-10-22',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);
        $course->students()->attach($this->student->id, ['enrolled_at' => now()]);

        $meeting = Meeting::factory()->create([
            'course_id' => $course->id,
            'course_session_id' => $session->id,
            'started_at' => now(),
        ]);

        // Student joins video call -> trigger check-in
        $checkInResponse = $this->actingAs($this->studentUser)->postJson(
            route('live-class.check-in', $meeting),
            ['student_id' => $this->student->id]
        );

        $checkInResponse->assertOk();
        $checkInResponse->assertJson(['recorded' => true]);

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $this->student->id,
            'course_session_id' => $session->id,
            'status' => 'present',
        ]);

        // Student leaves video call -> trigger check-out
        $checkOutResponse = $this->actingAs($this->studentUser)->postJson(
            route('live-class.check-out', $meeting),
            ['student_id' => $this->student->id]
        );

        $checkOutResponse->assertOk();
        $checkOutResponse->assertJson(['recorded' => true]);
    }

    public function test_meeting_chat_messages_can_be_sent_and_polled_via_json(): void
    {
        $meeting = Meeting::factory()->create();

        // Post chat message
        $sendResponse = $this->actingAs($this->studentUser)->postJson(
            route('live-class.messages.store', $meeting),
            [
                'sender_name' => $this->student->name,
                'message' => 'Halo pak, izin bertanya mengenai materi ini.',
            ]
        );

        $sendResponse->assertOk();
        $sendResponse->assertJson(['success' => true]);

        $this->assertDatabaseHas('meeting_messages', [
            'meeting_id' => $meeting->id,
            'sender_name' => $this->student->name,
            'message' => 'Halo pak, izin bertanya mengenai materi ini.',
        ]);

        // Poll chat messages
        $pollResponse = $this->actingAs($this->studentUser)->getJson(
            route('live-class.messages.index', $meeting)
        );

        $pollResponse->assertOk();
        $pollResponse->assertJsonFragment([
            'sender_name' => $this->student->name,
            'message' => 'Halo pak, izin bertanya mengenai materi ini.',
        ]);
    }
}
