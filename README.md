moodle-local_kent
=================

Various helpers for UKC's Moodles

### Course Notification API
Note: This will only work on Moodle 2015+

To create a new notification:
```
$course = new \local_kent\Course($courseid);
$course->add_notification($ctxid, 'rollover_failed', 'The rollover failed! :(', false);
```

To delete that:
```
$course = new \local_kent\Course($courseid);
$notification = $course->get_notification($ctxid, 'rollover_failed');
$notification->delete();
```

That's all you really need to know...
