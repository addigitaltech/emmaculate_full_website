# Connecting this website with a CBT (computer-based test) website

These are recommendations for when the CBT site is built. Nothing here needs to be done now; the Student ID is already the shared key.

## What the two systems share
| Information | Lives in | Used by CBT for |
|---|---|---|
| **Student ID** (`EAC/2627/0001`) | Results site (created automatically, never repeated) | The one identifier on both sides |
| Classes, arms, subjects, current session/term | Results site | Who may sit which exam |
| Student list per class/arm (name, Student ID, passport photo) | Results site | Exam registers and the photo on the exam screen |
| Exam scores | CBT site | Sent back to become the CA or exam mark in the results |

## Recommended design
1. **One school-owned source of truth for people:** keep students, classes and subjects in this website. The CBT reads them; it does not keep its own copies that can drift.
2. **A small, secure API (v1) on this website** using Laravel Sanctum (already installed): token per connected system, read-only endpoints for classes, arms, subjects, students, and a write endpoint for scores. Tokens are created in the admin area and can be revoked.
3. **Single sign-on (optional but nice):** a student signs in once here, taps "Take exam", and arrives at the CBT already signed in through a short-lived signed token containing the Student ID. This avoids a second password or PIN.
4. **Scores come back as proposals, not final marks:** the CBT posts `{student_id, subject, term, score, exam_id}`; this site stores them as *pending CBT scores* that a teacher approves into the CA or exam column. Teachers stay in control and every change is in the activity log.
5. **Same Cloudflare + Turnstile protection** on both sites, and rate limits on the API.

## What to decide before building the CBT
- Which marks come from CBT (a CA column, or the whole exam) and their maximums, so they match *Assessment & grading* here.
- Whether the CBT has its own admin or only teachers' accounts from this site (recommended: reuse these accounts through SSO).
- Where exams run: school computers on a local network (offline-capable) or the internet. For poor connectivity, plan an offline-first design that syncs scores afterwards.
- Question banks: owned by teachers per subject and class; versioned so an exam keeps the questions it was given.

## Ready for it today
Student IDs, class/arm/subject structure, class teachers, teacher assignments, the activity log and API-capable authentication are all in place, so the integration is an addition, not a rebuild.
