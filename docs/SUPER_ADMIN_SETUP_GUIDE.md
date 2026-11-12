

## 12. Results and people migration backups

Super Admins can use **Settings > Results & people backup**. Choose a term or all terms and download a Results ZIP. The archive contains a CSV matched by stable Student ID, subject code/name, academic session and term. It can be restored on a new domain or after accidental deletion; existing rows are updated and no rows are deleted by the importer.

Download a People ZIP for user login identifiers, roles, student/teacher/parent biodata and profile links. Password hashes, PINs, reset tokens and secrets are never exported. Before importing on a new site, create the user accounts and assign their roles; the importer matches existing accounts by email and does not silently create login accounts with unknown passwords.

When a term is marked current, the application automatically emails the previous term's results backup (or the selected current term when no previous term exists) together with the People ZIP to the school email in **Website > School profile**. This is the transition backup used when a completed/published term is replaced by a new current term. Ensure SMTP is configured and keep the attachments private.

These ZIP files contain confidential student results and personal data. Store them outside the public web root, restrict access to trusted administrators and test a restore on a non-production copy before using it for a live migration.
