<?php

/*
 * My Groups (Officer Trainee dashboard card).
 *
 * PR #334 F-005: sending SMS / email to group members goes out through the
 * Academy's own gateway and identity. Whether Officer Trainees may do that at all
 * is a Product owner decision that has not been recorded, so the send is OFF
 * unless MY_GROUPS_MESSAGING_ENABLED=true is set in the environment. Turning it on
 * is that decision; record it before setting the variable.
 *
 * When on, every send carries the sender's name and OT code, is limited by the
 * dedicated `my-groups-message` rate limiter (its own counter, not shared with any
 * other throttled route), and is written to my_group_message_log.
 */
return [
    'messaging_enabled' => (bool) env('MY_GROUPS_MESSAGING_ENABLED', false),

    // Sends allowed per sender per hour once messaging is enabled.
    'messages_per_hour' => (int) env('MY_GROUPS_MESSAGES_PER_HOUR', 5),
];
