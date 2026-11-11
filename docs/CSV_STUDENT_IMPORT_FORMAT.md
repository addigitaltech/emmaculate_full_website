# Student CSV import format

Use this format for the student import/upload page in the administrator area. Save as a UTF-8 comma-separated file with the first row containing the exact headers below.

## Header row

```csv
student_id,first_name,last_name,other_names,gender,date_of_birth,class,arm,status
```

## Column rules

| Column | Required? | Example | Rules |
|---|---:|---|---|
| `student_id` | No for new students | `EAC/2627/0001` | Leave blank for a new student so the system generates the Student ID. Supply an existing ID to update that student. `student_number` is also accepted as an older header alias. |
| `first_name` | Yes | `Ada` | Student's first name. |
| `last_name` | Yes | `Okoro` | Student's surname/family name. |
| `other_names` | No | `Grace` | Middle or additional names. |
| `gender` | No | `Female` | Use `Male` or `Female`; other values are not stored as a valid gender. |
| `date_of_birth` | No | `2014-03-17` | Use `YYYY-MM-DD` or `DD/MM/YYYY`, for example `12/08/2013`. |
| `class` | No | `JSS1` | Must match an existing active class name when supplied. |
| `arm` | No | `A` | Must match an arm attached to the selected class when supplied. |
| `status` | No | `active` | Use the status offered by the current import form. New students should normally be `active`. |

## Example file

The complete example is also available as [`student-import-example.csv`](student-import-example.csv):

```csv
student_id,first_name,last_name,other_names,gender,date_of_birth,class,arm,status
,Ada,Okoro,Grace,Female,2014-03-17,JSS1,A,active
,Bola,Ajayi,,Male,12/08/2013,JSS1,B,active
```

## Import procedure

1. Create the academic session, classes and arms first.
2. Download the template from the student upload page if available, or copy the header above.
3. Use exact class and arm names from the admin area. Do not use a class code that only looks similar.
4. Keep one student per row. Do not merge cells or save the file as an Excel workbook; save it as CSV UTF-8.
5. Leave `student_id` blank for new records. The system generates the school's configured prefix, session code and sequence number.
6. To update an existing record, supply its current Student ID and keep the required name columns present.
7. Upload the file and read the import result. Correct rejected rows and upload them again; do not assume a partially rejected file was fully accepted.
8. Check **Students** for the class, arm, status and Student ID. Then run `php artisan school:sync-people` once if the operator guide or host procedure requires a backfill.
9. Create/link parents after the student records exist.

## Common mistakes

- Missing `first_name` or `last_name`.
- Header spelling changed to `surname`, `dob` or `class_name`.
- A date such as `2014/03/17` or an impossible date.
- A class or arm that has not been created.
- Duplicate Student IDs.
- Adding a formula or leading apostrophe in a spreadsheet and then exporting an unexpected value.
- Uploading a file containing passwords, payment keys or unrelated private information. Student CSV files should contain school-record fields only.
