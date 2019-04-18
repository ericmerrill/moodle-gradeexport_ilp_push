* Improve submitter checks to also check for banner assignment local/sis\_interface/enrol\_lmb
* Date/timezone issues
* Notifications!
* Checkbox style

* Filtering
* Course settings



Test immediate:
* Submitter checks
* Date/timezone issues


Things to be done:
* Different grading "schemes"
* Don't allow logged-in-as users to submit grades
* Use overridable class for user-banner mapping and enrollment-course mapping
* Use the Moodle date selector
* Check wording for default incomplete date.
* Add support for additional certs
* Filterting
* ClearFinalGradeExpirationDateFlag and ClearLastAttendanceDateFlag
* Start/end date (if missing enddate in particular)
* Additional grade history/error display

Things that need to be looked into:
* Default incomplete grade, and should changing that be a setting? (SHAINCG)

Possibly remove:
* saved\_grade\_revision? (??)
* resultstatus column


Date formats:
* Date only, short 1982-10-31 
* Date only, long 1982-10-31T00:00:00 
* Time only, short T10:30:00 
* Time only, long 0001-01-01T10:30:00 
* Date and Time 1982-10-31T10:30:00 
* Date and Time with timezone 1982-10-31T10:30:00+05:00 
