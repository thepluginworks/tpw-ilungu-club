# Event Menu Read Contract

## Status

Canonical shared read contract for iLungu Club Dining Menus consumers.

## Purpose

iLungu Club owns reusable dining-menu definitions and their association with
iLungu Events records. Consumer plugins may read the current menu for an event;
they must not query the Menu tables or write menu relationships directly.

## Public interface

```php
tpw_core_get_event_menu_payload_for_post( int $event_post_id ): ?array
```

`$event_post_id` is the WordPress post ID for a `tpw_event` post. It is not the
separate iLungu Events domain ID stored in `tpw_events.event_id`.

The function:

1. validates the WordPress event post;
2. resolves exactly one `tpw_events.event_id` by `tpw_events.post_id`;
3. resolves exactly one linked menu through `tpw_event_menu_relationship`;
4. returns `null` when the event, relationship, menu, or menu structure is
   missing, invalid, ambiguous, deleted, or incomplete.

An incomplete menu has a missing or blank course, a course-number gap, an
invalid dish, or no dish choices for one of its declared courses.

## Return value

On success the function returns:

```php
[
    'event_post_id' => 123,
    'event_id'      => 456,
    'menu'          => [
        'id'                => 789,
        'name'              => 'Annual Dinner',
        'description'       => '',
        'number_of_courses' => 3,
        'price'             => 39.50,
    ],
    'courses'       => [
        1 => [
            'course_number' => 1,
            'course_name'   => 'Starter',
            'choices'       => [
                [
                    'id'          => 999,
                    'label'       => 'Soup',
                    'description' => '',
                    'extra_cost'  => 0.00,
                ],
            ],
        ],
    ],
]
```

Courses are ordered by `course_number`; choices are ordered by their stable
source `id`. `extra_cost` is a float.

## Consumer responsibilities

- Treat this as a read-only current-definition contract.
- Re-read and validate the payload during server-side checkout submission.
- Store consumer-owned immutable snapshots of selected labels and prices for
  completed purchases.
- Show an explicit configuration error and prevent new sales when the function
  returns `null`.

## Compatibility

Existing APIs remain unchanged:

- `TPW_Menus::get_menu_payload( $event_id )`
- `tpw_core_get_menu_payload( $event_id )`
- `TPW_Event_Menu_Rel::get_menu_for_event( $event_id )`

They accept the iLungu Events domain `event_id`, preserve their existing return
shape, and remain available for current consumers such as Lodge Meetings.

## Club-owned assignment interface

```php
TPW_Event_Menu_Rel::assign_menu_to_event_post( int $event_post_id, int $menu_id ): array
```

Only Club-owned administration flows may write an event-menu relationship, and
they must use this interface. `$event_post_id` is the WordPress `tpw_event`
post ID; the method resolves exactly one iLungu Events domain `event_id`.
`$menu_id` must identify an existing Club menu.

The method returns an array with `success`, a machine-readable `code`, a
human-readable `message`, and the resolved `event_post_id`, `event_id`, and
`menu_id`. On success, `code` is `created` or `updated`.

The assignment behaviour is:

1. an unlinked event receives one relationship;
2. an event with exactly one relationship has that row's `menu_id` updated;
3. the selected menu may be linked to other events;
4. an event with multiple existing relationships fails with
   `ambiguous_relationship` and no rows are deleted or changed.

The relationship table has no unique `event_id` constraint. To retain this
legacy schema while preventing concurrent Club assignments from inserting
duplicates, the method locks only the relationship table for the brief
read-and-write critical section. A lock acquisition or release failure is
reported as a failed assignment; callers must not treat it as success.

The legacy `assign_menu_to_event( $event_id, $menu_id )` domain-ID method is
unchanged for compatibility. Consumers, including Lodge Meetings, must not
write this table directly.
