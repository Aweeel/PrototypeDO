# Hearing Scheduling with Time Range and Conflict Detection

## Overview
This implementation adds time range scheduling for hearings with automatic conflict detection to prevent double-booking.

## Features Implemented

### 1. **Time Range Scheduling**
   - Hearings can now be scheduled with both start and end times
   - Duration is automatically calculated and displayed
   - Time range displays in notifications and sanction lists

### 2. **Conflict Detection**
   - Backend validation prevents scheduling conflicts
   - Checks both calendar events and case sanctions
   - Returns clear error messages when conflicts are detected

### 3. **User Interface Enhancements**
   - Added "End Time" field with required validation
   - Real-time duration calculation display
   - Visual feedback for valid/invalid time ranges
   - Time ranges shown in confirmation dialogs and applied sanctions list

## Database Changes

### Modified Tables

#### `case_sanctions`
- **New Column**: `scheduled_end_time` (TIME NULL)
  - Stores the end time of scheduled hearings

#### `calendar_events`
- **New Column**: `event_end_time` (TIME NULL)
  - Stores the end time of calendar events

### Schema Location
Updated in: `database/schema.sql`

## Implementation Details

### Backend Changes

#### 1. **functions.php**
- Added `checkSchedulingConflicts()` function
  - Parameters: date, start time, end time, optional exclude event ID
  - Returns array of conflicting events
  - Checks both calendar_events and case_sanctions tables
  - Uses overlap algorithm: `(StartA < EndB) AND (EndA > StartB)`

- Updated `applySanctionToCase()` function
  - Added `$scheduleEndTime` parameter
  - Calls conflict check before inserting
  - Throws exception if conflicts found
  - Stores end time in database and calendar events

#### 2. **cases.php**
- Updated `applySanction` AJAX handler
  - Receives `scheduleEndTime` from frontend
  - Converts HH:MM to HH:MM:SS format
  - Wrapped in try-catch to handle conflict exceptions
  - Returns error message to frontend on conflict
  - Updated notification to show time range

### Frontend Changes

#### 1. **modals.js**
- **UI Updates**:
  - Added "End Time" input field (required)
  - Added duration display element
  - Updated labels to show "Start Time"

- **Validation**:
  - Required end time if date and start time provided
  - Validates end time is after start time
  - Displays helpful error messages

- **Time Calculation**:
  - `calculateScheduleDuration()`: Computes and displays duration
  - `initializeTimeCalculation()`: Attaches event listeners
  - Shows duration in hours and minutes
  - Color-coded feedback (green for valid, red for invalid)

- **Display Updates**:
  - Confirmation dialog shows time range
  - Applied sanctions list shows scheduled time range
  - Format: "9:00 AM - 10:30 AM"

## Usage

### Scheduling a Hearing

1. **Open Cases Management** and select a case
2. **Apply Sanction** and choose a sanction that requires scheduling
3. **Fill in Schedule Details**:
   - Date: Select the hearing date
   - Start Time: Enter the hearing start time
   - End Time: Enter the hearing end time (required)
   - Notes: Optional schedule notes

4. **Validation**:
   - End time must be after start time
   - Duration is automatically calculated and shown
   - System checks for conflicts before saving

5. **Conflict Handling**:
   - If a conflict exists, an error message appears
   - Shows which event conflicts with the schedule
   - User must choose a different time slot

### Example Conflict Detection

```
Scenario: Trying to schedule a hearing from 9:00 AM to 10:00 AM

Existing Events:
- Event A: 8:30 AM - 9:30 AM ❌ CONFLICT
- Event B: 10:00 AM - 11:00 AM ✓ No overlap
- Event C: 7:00 AM - 8:00 AM ✓ No overlap

Result: System prevents scheduling and shows:
"Scheduling conflict detected: Event A is already scheduled at this time."
```

## Validation Rules

1. **Start Time < End Time**: End time must be later than start time
2. **No Overlaps**: Time ranges cannot overlap with existing events
3. **Same Day**: Conflicts only checked for the same date
4. **Hearing Category**: Only checks conflicts with "Hearing" category events

## Default Behavior

- If end time is not provided in conflict check, system assumes 1-hour duration
- Calendar events without end time default to 1 hour for conflict checking

## Testing Checklist

- [ ] Schedule a hearing with valid time range
- [ ] Try to schedule conflicting events (should fail)
- [ ] Verify end time is after start time validation
- [ ] Check duration calculation displays correctly
- [ ] Confirm time range shows in notifications
- [ ] Verify time range appears in applied sanctions list
- [ ] Test with events on different dates (should allow)
- [ ] Test with non-overlapping events on same date (should allow)

## Error Messages

- **Frontend Validation**:
  - "Please enter an end time for the hearing"
  - "End time must be after start time"

- **Backend Validation**:
  - "Scheduling conflict detected: [Event Name] is already scheduled at this time."

## Future Enhancements

- Visual calendar view showing scheduled hearings
- Drag-and-drop rescheduling
- Suggested available time slots
- Recurring hearing schedules
- Email/SMS reminders before scheduled hearings
- Buffer time between events

## Files Modified

1. `database/schema.sql` - Added end time columns
2. `includes/functions.php` - Added conflict detection logic
3. `modules/do/cases.php` - Updated sanction application handler
4. `assets/js/cases/modals.js` - Added UI and validation

## Migration Notes

If you already have an existing database, you'll need to add the columns manually:

```sql
ALTER TABLE case_sanctions ADD scheduled_end_time TIME NULL;
ALTER TABLE calendar_events ADD event_end_time TIME NULL;
```

Alternatively, recreate the database using the updated `schema.sql` file.
