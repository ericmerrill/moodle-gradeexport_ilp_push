* Improve submitter checks to also check for banner assignment local/sis\_interface/enrol\_lmb
* Date/timezone issues
* Notifications!
* Incomplete dates seem... problematic.



Things to be done:
* Different grading "schemes"
* Don't allow logged-in-as users to submit grades
* Use overridable class for user-banner mapping and enrollment-course mapping
* Use the Moodle date selector
* Check wording for default incomplete date. (Extension Date, Incomplete Final Grade)
* Add support for additional certs
* Sorting
* ClearFinalGradeExpirationDateFlag and ClearLastAttendanceDateFlag
* Start/end date (if missing enddate in particular) (Probably even ask sis\_interface)
* Additional grade history/error display
* Paging
* Chunking of submissions
* Auto lock grades. Will need to split locking status.
* grade\_export\_update\_buffer
* Check if student is allowed (practice students shown...)
* Help messages for filters
* Additional grade submission, like midterms

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
